<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser;

class DocumentController extends Controller
{
    public function showUploadForm()
    {
        return view('upload');
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:pdf|max:10000',
        ]);

        $sourceName = $request->file('file')->getClientOriginalName();
        $fileSize = round($request->file('file')->getSize() / 1024, 1);

        Log::info("═══════════════════════════════════════════════════");
        Log::info("📄 UPLOAD STARTED: {$sourceName} ({$fileSize} KB)");
        Log::info("═══════════════════════════════════════════════════");

        try {
            // ── Step 1: Parse PDF ──
            Log::info("🔍 [Step 1/4] Parsing PDF text...");
            $startTime = microtime(true);

            $parser = new Parser();
            $pdf = $parser->parseFile($request->file('file')->path());
            $text = $pdf->getText();

            $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
            $textLength = strlen($text);
            $parseTime = round(microtime(true) - $startTime, 2);

            Log::info("✅ [Step 1/4] PDF parsed in {$parseTime}s — extracted {$textLength} characters");

            // ── Step 2: Chunk text ──
            Log::info("✂️  [Step 2/4] Chunking text into 1000-char pieces...");
            $chunks = str_split($text, 1000);
            $totalChunks = count($chunks);
            Log::info("✅ [Step 2/4] Created {$totalChunks} chunks");

            // ── Step 3 & 4: Embed + Store each chunk ──
            Log::info("🧠 [Step 3/4] Starting embedding + indexing for {$totalChunks} chunks...");
            $successCount = 0;
            $failCount = 0;
            $totalEmbedTime = 0;

            foreach ($chunks as $index => $chunk) {
                $chunk = mb_convert_encoding($chunk, 'UTF-8', 'UTF-8');
                $chunkNum = $index + 1;
                $chunkPreview = substr(trim($chunk), 0, 50);

                Log::info("  📦 Chunk {$chunkNum}/{$totalChunks}: \"{$chunkPreview}...\"");

                // Embed via HuggingFace
                Log::info("    🌐 Calling HuggingFace API for embedding...");
                $embedStart = microtime(true);

                $response = Http::withToken(env('HUGGINGFACE_API_KEY'))
                    ->timeout(60)
                    ->post('https://router.huggingface.co/hf-inference/models/BAAI/bge-small-en-v1.5', [
                        'inputs' => $chunk,
                        'options' => ['wait_for_model' => true]
                    ]);

                $embedTime = round(microtime(true) - $embedStart, 2);
                $totalEmbedTime += $embedTime;

                if ($response->successful()) {
                    $embedding = $response->json();
                    $vector = is_array($embedding[0]) ? $embedding[0] : $embedding;
                    $vectorDim = count($vector);

                    Log::info("    ✅ Embedding received in {$embedTime}s — {$vectorDim} dimensions");

                    // Store in Supabase
                    Log::info("    💾 Storing in Supabase vector DB...");
                    $storeStart = microtime(true);

                    DB::table('document_chunks')->insert([
                        'content' => $chunk,
                        'metadata' => json_encode([
                            'source' => $sourceName,
                            'chunk_index' => $index
                        ]),
                        'embedding' => '[' . implode(',', $vector) . ']'
                    ]);

                    $storeTime = round(microtime(true) - $storeStart, 2);
                    Log::info("    ✅ Stored in DB in {$storeTime}s");

                    $successCount++;
                } else {
                    $failCount++;
                    Log::error("    ❌ HuggingFace API failed for chunk {$chunkNum}: " . $response->body());
                }
            }

            $totalTime = round(microtime(true) - $startTime, 2);

            Log::info("═══════════════════════════════════════════════════");
            Log::info("🎉 UPLOAD COMPLETE: {$sourceName}");
            Log::info("   📊 Chunks: {$successCount}/{$totalChunks} indexed, {$failCount} failed");
            Log::info("   ⏱️  Total time: {$totalTime}s (embedding: {$totalEmbedTime}s)");
            Log::info("═══════════════════════════════════════════════════");

            return back()->with('status', "✅ Success! \"{$sourceName}\" indexed — {$successCount}/{$totalChunks} chunks stored in {$totalTime}s.");

        } catch (\Exception $e) {
            Log::error("💥 UPLOAD FAILED: {$sourceName}");
            Log::error("   Error: " . $e->getMessage());
            Log::error("   File: " . $e->getFile() . ":" . $e->getLine());
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }
}