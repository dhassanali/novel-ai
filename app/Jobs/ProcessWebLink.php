<?php

namespace App\Jobs;

use App\Models\SourceDocument;
use App\Services\QdrantService;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWebLink implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public SourceDocument $document
    ) {}

    /**
     * Execute the job.
     */
    public function handle(QdrantService $qdrant): void
    {
        $this->document->update(['status' => 'processing']);

        try {
            // 1. Fetch content
            $client = new Client(['timeout' => 30]);
            $response = $client->get($this->document->path);
            $html = (string) $response->getBody();

            // 2. Extract text (simple strip_tags for now)
            // Remove scripts and styles first
            $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', "", $html);
            $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', "", $html);
            $text = strip_tags($html);
            $text = html_entity_decode($text);
            $text = preg_replace('/\s+/', ' ', $text); // Normalize whitespace
            $text = trim($text);

            // Aggressively sanitize UTF-8
            // 1. Convert to UTF-8, ignoring invalid characters
            $text = iconv('UTF-8', 'UTF-8//IGNORE', $text);
            
            // 2. Remove control characters (except newlines if we kept them, but we normalized to space above)
            // We use a regex that doesn't depend on /u flag initially to be safe, then ensure valid UTF-8
            $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);
            
            // 3. Remove any remaining non-printable characters
            $text = preg_replace('/[^\P{C}\s]/u', '', $text);
            
            // 4. Final check to ensure json_encode will work
            if (!mb_check_encoding($text, 'UTF-8')) {
                 $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
            }

            if (empty($text)) {
                throw new \Exception("No content found at URL");
            }

            // 3. Chunk text
            $chunks = $this->chunkText($text, 1000);

            // 4. Embed and store
            $points = [];
            foreach ($chunks as $index => $chunk) {
                // Additional sanitization before embedding to ensure json_encode compatibility
                $chunk = $this->sanitizeForJson($chunk);
                
                if (empty(trim($chunk))) {
                    continue; // Skip empty chunks
                }
                
                $embedding = \App\Facades\LocalAI::embed($chunk);
                
                $points[] = [
                    'id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
                    'vector' => $embedding,
                    'payload' => [
                        'novel_id' => $this->document->novel_id,
                        'source_id' => $this->document->id,
                        'content' => $chunk,
                        'type' => 'web_link',
                        'url' => $this->document->path,
                        'collection' => 'novel_' . $this->document->novel_id,
                    ],
                ];
            }

            if (!empty($points)) {
                foreach ($points as $point) {
                    $qdrant->upsert($point);
                }
            }

            $this->document->update(['status' => 'completed']);

        } catch (\Exception $e) {
            Log::error("Failed to process web link: " . $e->getMessage());
            $this->document->update(['status' => 'failed']);
            $this->fail($e);
        }
    }

    private function chunkText(string $text, int $chunkSize): array
    {
        $chunks = [];
        $len = mb_strlen($text, 'UTF-8');
        for ($i = 0; $i < $len; $i += $chunkSize) {
            $chunks[] = mb_substr($text, $i, $chunkSize, 'UTF-8');
        }
        return $chunks;
    }

    /**
     * Sanitize text to ensure it can be JSON encoded without errors
     */
    private function sanitizeForJson(string $text): string
    {
        // Remove any invalid UTF-8 sequences
        $text = iconv('UTF-8', 'UTF-8//IGNORE', $text);
        
        // Remove control characters except tab, newline, and carriage return
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);
        
        // Ensure valid UTF-8
        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        }
        
        // Test if it can be JSON encoded
        $test = json_encode($text);
        if ($test === false) {
            // If still failing, use a more aggressive approach
            $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
            // Remove any remaining problematic characters
            $text = preg_replace('/[^\P{C}\s]/u', '', $text);
        }
        
        return $text;
    }
}
