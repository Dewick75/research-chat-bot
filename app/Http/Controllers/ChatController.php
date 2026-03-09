<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    /**
     * Show the chat interface.
     */
    public function showChat()
    {
        $documents = DB::table('document_chunks')
            ->selectRaw("DISTINCT metadata->>'source' as source_name")
            ->get()
            ->pluck('source_name')
            ->filter()
            ->values();

        Log::info("💬 Chat page loaded — {$documents->count()} documents available");

        return view('chat', compact('documents'));
    }

    /**
     * Handle a chat question using RAG (Retrieval-Augmented Generation).
     */
    public function ask(Request $request)
    {
        $request->validate([
            'question' => 'required|string|max:2000',
        ]);

        $question = $request->input('question');
        $questionPreview = \Str::limit($question, 80);
        $totalStart = microtime(true);

        Log::info("═══════════════════════════════════════════════════");
        Log::info("🤖 CHAT QUESTION: \"{$questionPreview}\"");
        Log::info("═══════════════════════════════════════════════════");

        try {
            // ── Step 1: Embed the question ──
            Log::info("🧠 [Step 1/4] Embedding question via HuggingFace...");
            $embedStart = microtime(true);

            $embeddingResponse = Http::withToken(env('HUGGINGFACE_API_KEY'))
                ->timeout(30)
                ->post('https://router.huggingface.co/hf-inference/models/BAAI/bge-small-en-v1.5', [
                    'inputs' => $question,
                    'options' => ['wait_for_model' => true],
                ]);

            $embedTime = round(microtime(true) - $embedStart, 2);

            if (!$embeddingResponse->successful()) {
                Log::error("❌ [Step 1/4] HuggingFace embedding failed: " . $embeddingResponse->body());
                return response()->json([
                    'error' => 'Failed to generate embedding for your question.',
                    'details' => $embeddingResponse->body(),
                ], 500);
            }

            $embedding = $embeddingResponse->json();
            $vector = is_array($embedding[0]) ? $embedding[0] : $embedding;
            $vectorString = '[' . implode(',', $vector) . ']';
            $vectorDim = count($vector);

            Log::info("✅ [Step 1/4] Question embedded in {$embedTime}s — {$vectorDim} dimensions");

            // ── Step 2: Vector similarity search ──
            Log::info("🔍 [Step 2/4] Searching vector DB for similar chunks...");
            $searchStart = microtime(true);

            $chunks = DB::select("
                SELECT
                    content,
                    metadata,
                    1 - (embedding <=> ?::vector) AS similarity
                FROM document_chunks
                ORDER BY similarity DESC
                LIMIT 5
            ", [$vectorString]);

            $searchTime = round(microtime(true) - $searchStart, 2);

            if (empty($chunks)) {
                Log::warning("⚠️  [Step 2/4] No chunks found in vector DB — DB is empty");
                return response()->json([
                    'answer' => "I don't have any research papers to reference yet. Please upload a PDF first!",
                    'sources' => [],
                ]);
            }

            Log::info("✅ [Step 2/4] Found " . count($chunks) . " relevant chunks in {$searchTime}s");
            foreach ($chunks as $i => $chunk) {
                $meta = json_decode($chunk->metadata, true);
                $sim = round($chunk->similarity * 100, 1);
                $preview = \Str::limit($chunk->content, 60);
                Log::info("   📄 #{$i}: [{$sim}% match] from \"{$meta['source']}\" — \"{$preview}\"");
            }

            // ── Step 3: Build context ──
            Log::info("📝 [Step 3/4] Building context prompt from retrieved chunks...");
            $context = "";
            $sources = [];
            $totalContextChars = 0;

            foreach ($chunks as $i => $chunk) {
                $meta = json_decode($chunk->metadata, true);
                $context .= "--- Excerpt " . ($i + 1) . " (from: " . ($meta['source'] ?? 'Unknown') . ") ---\n";
                $context .= $chunk->content . "\n\n";
                $totalContextChars += strlen($chunk->content);

                $sources[] = [
                    'content' => \Str::limit($chunk->content, 200),
                    'source' => $meta['source'] ?? 'Unknown',
                    'chunk_index' => $meta['chunk_index'] ?? null,
                    'similarity' => round($chunk->similarity, 4),
                ];
            }

            Log::info("✅ [Step 3/4] Context built — {$totalContextChars} chars from " . count($sources) . " sources");

            // ── Step 4: Send to Groq LLM ──
            Log::info("🚀 [Step 4/4] Sending to Groq LLM (llama-3.1-8b-instant)...");
            $llmStart = microtime(true);

            $systemPrompt = <<<PROMPT
You are an expert research assistant. Your job is to answer questions based ONLY on the provided research paper excerpts below.

RULES:
1. Answer the question using ONLY information from the provided excerpts.
2. If the excerpts don't contain enough information to answer, say so clearly.
3. Cite which excerpt(s) you are referencing (e.g., "According to Excerpt 1...").
4. Be thorough but concise. Use bullet points and structure when appropriate.
5. If the user asks for a summary, provide a structured summary with key points.

RESEARCH PAPER EXCERPTS:
{$context}
PROMPT;

            $groqResponse = Http::withToken(env('GROQ_API_KEY'))
                ->timeout(60)
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => 'llama-3.1-8b-instant',
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $question],
                    ],
                    'temperature' => 0.3,
                    'max_tokens' => 2048,
                ]);

            $llmTime = round(microtime(true) - $llmStart, 2);

            if (!$groqResponse->successful()) {
                Log::error("❌ [Step 4/4] Groq LLM failed: " . $groqResponse->body());
                return response()->json([
                    'error' => 'LLM failed to generate a response.',
                    'details' => $groqResponse->body(),
                ], 500);
            }

            $answer = $groqResponse->json()['choices'][0]['message']['content'] ?? 'No response generated.';
            $tokensUsed = $groqResponse->json()['usage'] ?? [];
            $promptTokens = $tokensUsed['prompt_tokens'] ?? '?';
            $completionTokens = $tokensUsed['completion_tokens'] ?? '?';

            Log::info("✅ [Step 4/4] LLM responded in {$llmTime}s — tokens: {$promptTokens} prompt + {$completionTokens} completion");

            $totalTime = round(microtime(true) - $totalStart, 2);

            Log::info("═══════════════════════════════════════════════════");
            Log::info("🎉 CHAT COMPLETE in {$totalTime}s");
            Log::info("   ⏱️  Embed: {$embedTime}s | Search: {$searchTime}s | LLM: {$llmTime}s");
            Log::info("   📊 Sources: " . count($sources) . " | Answer length: " . strlen($answer) . " chars");
            Log::info("═══════════════════════════════════════════════════");

            return response()->json([
                'answer' => $answer,
                'sources' => $sources,
            ]);

        } catch (\Exception $e) {
            Log::error("💥 CHAT FAILED for question: \"{$questionPreview}\"");
            Log::error("   Error: " . $e->getMessage());
            Log::error("   File: " . $e->getFile() . ":" . $e->getLine());
            return response()->json([
                'error' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List all unique uploaded documents.
     */
    public function documents()
    {
        $documents = DB::table('document_chunks')
            ->selectRaw("metadata->>'source' as source_name, COUNT(*) as chunk_count")
            ->groupByRaw("metadata->>'source'")
            ->get();

        Log::info("📚 Documents API called — returned " . $documents->count() . " documents");

        return response()->json($documents);
    }
}
