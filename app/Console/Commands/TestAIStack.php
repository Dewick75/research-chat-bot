<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestAIStack extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-a-i-stack';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify all AI stack integrations (Database, HuggingFace, Groq)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("🚀 Starting AI Stack Verification...");

        // 1. Verify Database (Supabase)
        try {
            \DB::connection()->getPdo();
            $this->info("✅ Database: Connected to Supabase.");
        } catch (\Exception $e) {
            $this->error("❌ Database: " . $e->getMessage());
            $this->comment("👉 Tip: Enable 'extension=pdo_pgsql' in your php.ini file.");
        }

// 2. Verify Hugging Face (Embeddings) - USING BETTER RETRIEVAL MODEL
try {
    $response = \Http::withToken(env('HUGGINGFACE_API_KEY'))
        ->timeout(30)
        ->post('https://router.huggingface.co/hf-inference/models/BAAI/bge-small-en-v1.5', [
            'inputs' => 'Hello World',
            'options' => ['wait_for_model' => true]
        ]);

    $data = $response->json();

    // BGE model returns a nested array, we need the first inner array
    if ($response->successful() && isset($data[0])) {
        $this->info("✅ Hugging Face: BGE model is active and returning vectors.");
    } else {
        $this->error("❌ Hugging Face: " . $response->body());
    }
} catch (\Exception $e) {
    $this->error("❌ Hugging Face Error: " . $e->getMessage());
}

        // 3. Verify Groq (LLM)
        try {
            $response = \Http::withToken(env('GROQ_API_KEY'))
                ->timeout(30)
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => 'llama-3.1-8b-instant',
                    'messages' => [['role' => 'user', 'content' => 'Say hello!']],
                ]);

            if ($response->successful()) {
                $message = $response->json()['choices'][0]['message']['content'] ?? 'No content';
                $this->info("✅ Groq: LLM is responding. Message: " . $message);
            } else {
                $this->error("❌ Groq: " . $response->body());
            }
        } catch (\Exception $e) {
            $this->error("❌ Groq Error: " . $e->getMessage());
        }

        $this->info("--- Verification Complete ---");
    }
}
