<?php

namespace App\Providers;

use App\Services\CoquiService;
use App\Services\OllamaService;
use App\Services\QdrantService;
use App\Services\WhisperService;

class LocalAI
{
    public function __construct(
        protected OllamaService $ollama,
        protected QdrantService $qdrant,
        protected WhisperService $whisper,
        protected CoquiService $coqui
    ) {
    }

    /**
     * Generate text using LLM.
     */
    public function generate(string $prompt, ?string $model = null): string
    {
        return $this->ollama->generate($prompt, $model);
    }

    /**
     * Create a fluent text generation builder.
     */
    public function text(string $prompt): TextGenerationBuilder
    {
        return new TextGenerationBuilder($this->ollama, $prompt);
    }

    /**
     * Create embeddings for text.
     */
    public function embed(string $text, ?string $model = null): array
    {
        return $this->ollama->embed($text, $model);
    }

    /**
     * Store document with embeddings in vector database.
     */
    public function storeDocument(string $content, array $metadata = []): string
    {
        $embedding = $this->embed($content);

        return $this->qdrant->upsert([
            'vector' => $embedding,
            'payload' => array_merge($metadata, ['content' => $content]),
        ]);
    }

    /**
     * Search documents semantically.
     */
    public function searchDocuments(string $query, int $limit = 5, float $scoreThreshold = 0.7, array $filter = []): array
    {
        $queryEmbedding = $this->embed($query);

        return $this->qdrant->search($queryEmbedding, $limit, $scoreThreshold, null, $filter);
    }

    /**
     * Ask a question about your documents (RAG).
     */
    public function askDocuments(string $question, int $contextLimit = 5, array $filter = []): string
    {
        // Search for relevant documents
        $results = $this->searchDocuments($question, $contextLimit, 0.7, $filter);

        // Build context from search results
        $context = collect($results)->map(function ($result) {
            return $result['payload']['content'] ?? '';
        })->join("\n\n");

        // Generate answer with context
        $prompt = "Based on the following context, answer the question.\n\n";
        $prompt .= "Context:\n{$context}\n\n";
        $prompt .= "Question: {$question}\n\n";
        $prompt .= "Answer:";

        return $this->generate($prompt);
    }

    /**
     * Ingest documents from directory.
     */
    public function ingestDocuments(string $path, ?string $collection = null): int
    {
        $files = glob($path);
        $count = 0;

        foreach ($files as $file) {
            if (is_file($file)) {
                $content = $this->extractTextFromFile($file);
                $chunks = $this->chunkText($content);

                foreach ($chunks as $chunk) {
                    $this->storeDocument($chunk, [
                        'file' => basename($file),
                        'path' => $file,
                        'collection' => $collection ?? 'default',
                    ]);
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Transcribe audio to text.
     */
    public function transcribe(string $audioPath, ?string $language = null): string
    {
        return $this->whisper->transcribe($audioPath, $language);
    }

    /**
     * Generate speech from text.
     */
    public function speak(string $text): SpeechBuilder
    {
        return new SpeechBuilder($this->coqui, $text);
    }

    /**
     * Health check for all services.
     */
    public function healthCheck(): array
    {
        return [
            'ollama' => $this->ollama->health(),
            'qdrant' => $this->qdrant->health(),
            'whisper' => $this->whisper->health(),
             'coqui' => $this->coqui->health(),
        ];
    }

    /**
     * Get Ollama service instance.
     */
    public function ollama(): OllamaService
    {
        return $this->ollama;
    }

    /**
     * Get Qdrant service instance.
     */
    public function qdrant(): QdrantService
    {
        return $this->qdrant;
    }

    /**
     * Get Whisper service instance.
     */
    public function whisper(): WhisperService
    {
        return $this->whisper;
    }

    /**
     * Get Coqui service instance.
     */
    public function coqui(): CoquiService
    {
        return $this->coqui;
    }

    /**
     * Extract text from file based on extension.
     */
    protected function extractTextFromFile(string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'txt', 'md' => file_get_contents($path),
            'pdf' => $this->extractPdfText($path),
            'docx' => $this->extractDocxText($path),
            default => throw new \RuntimeException("Unsupported file type: {$extension}"),
        };
    }

    /**
     * Extract text from PDF.
     */
    protected function extractPdfText(string $path): string
    {
        // This is a placeholder - you'll need a PDF library like smalot/pdfparser
        // composer require smalot/pdfparser
        throw new \RuntimeException('PDF parsing not yet implemented. Install smalot/pdfparser');
    }

    /**
     * Extract text from DOCX.
     */
    protected function extractDocxText(string $path): string
    {
        // This is a placeholder - you'll need a DOCX library
        throw new \RuntimeException('DOCX parsing not yet implemented');
    }

    /**
     * Chunk text into smaller pieces for better embedding.
     */
    protected function chunkText(string $text, int $chunkSize = 1000, int $overlap = 200): array
    {
        $chunks = [];
        $length = strlen($text);

        for ($i = 0; $i < $length; $i += ($chunkSize - $overlap)) {
            $chunk = substr($text, $i, $chunkSize);
            if (strlen(trim($chunk)) > 0) {
                $chunks[] = $chunk;
            }
        }

        return $chunks;
    }

    /**
     * Ingest CSV file into vector database.
     */
    public function ingestCsv(
        string $filePath,
        ?string $collection = null,
        array $options = []
    ): int {
        $csv = new \App\Services\CsvService();

        // Prepare documents with smart chunking
        $documents = $csv->prepareForEmbedding($filePath, $options);

        $count = 0;
        foreach ($documents as $doc) {
            $this->storeDocument($doc['text'], array_merge(
                $doc['metadata'],
                ['collection' => $collection ?? 'default']
            ));
            $count++;
        }

        return $count;
    }

    /**
     * Search CSV data semantically.
     */
    public function searchCsv(string $query, int $limit = 5): array
    {
        return $this->searchDocuments($query, $limit);
    }

    /**
     * Ask questions about CSV data.
     */
    public function askCsv(string $question, int $contextLimit = 10): string
    {
        return $this->askDocuments($question, $contextLimit);
    }

    /**
     * Get CSV service instance.
     */
    public function csv(): \App\Services\CsvService
    {
        return new \App\Services\CsvService();
    }

    /**
     * Generate with web search capability.
     */
    public function generateWithWebSearch(string $prompt, ?string $model = null): string
    {
        $search = new \App\Services\WebSearchService();
        $webOllama = new \App\Services\OllamaWithWebSearch($this->ollama, $search);
        
        return $webOllama->generateWithSearch($prompt, $model);
    }

    /**
     * Search web and generate answer.
     */
    public function webSearch(string $query): array
    {
        $search = new \App\Services\WebSearchService();
        return $search->search($query);
    }

    /**
     * Research a topic thoroughly with multiple web searches.
     */
    public function research(string $topic, ?string $model = null): string
    {
        $search = new \App\Services\WebSearchService();
        $webOllama = new \App\Services\OllamaWithWebSearch($this->ollama, $search);
        
        return $webOllama->research($topic, $model);
    }

    /**
     * Get web search service instance.
     */
    public function webSearchService(): \App\Services\WebSearchService
    {
        return new \App\Services\WebSearchService();
    }
}
