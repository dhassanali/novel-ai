<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string generate(string $prompt, ?string $model = null)
 * @method static \App\TextGenerationBuilder text(string $prompt)
 * @method static array embed(string $text, ?string $model = null)
 * @method static array[] embedBatch(array $texts, ?string $model = null)
 * @method static string storeDocument(string $content, array $metadata = [])
 * @method static array searchDocuments(string $query, int $limit = 5, float $scoreThreshold = 0.7)
 * @method static string askDocuments(string $question, int $contextLimit = 5)
 * @method static int ingestDocuments(string $path, ?string $collection = null)
 * @method static int ingestCsv(string $filePath, ?string $collection = null, array $options = [])
 * @method static string transcribe(string $audioPath, ?string $language = null)
 * @method static \App\Services\SpeechBuilder speak(string $text)
 * @method static string generateWithWebSearch(string $prompt, ?string $model = null)
 * @method static array webSearch(string $query)
 * @method static string research(string $topic, ?string $model = null)
 * @method static \App\Services\WebSearchService webSearchService()
 * @method static array healthCheck()
 * @method static \App\Services\OllamaService ollama()
 * @method static \App\Services\QdrantService qdrant()
 * @method static \App\Services\WhisperService whisper()
 * @method static \App\Services\CoquiService coqui()
 *
 * @see \App\LocalAI
 */
class LocalAI extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'local-ai';
    }
}
