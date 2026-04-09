<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class OllamaService
{
    protected Client $client;

    protected array $config;

    public function __construct(string $host, array $config = [])
    {
        $this->config = $config;
        $this->client = new Client([
            'base_uri' => rtrim($host, '/'),
            'timeout' => $config['timeout'] ?? 120,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ]);
    }

    /**
     * Generate text using LLM.
     */
    public function generate(
        string $prompt,
        ?string $model = null,
        array $options = []
    ): string {
        $model = $model ?? $this->config['default_model'];

        try {
            $response = $this->client->post('/api/generate', [
                'json' => array_merge([
                    'model' => $model,
                    'prompt' => $prompt,
                    'stream' => false,
                    'options' => [
                        'temperature' => $options['temperature'] ?? $this->config['temperature'] ?? 0.7,
                        'num_predict' => $options['max_tokens'] ?? $this->config['max_tokens'] ?? 2000,
                    ],
                ], $options),
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            $this->logUsage('generate', $model, $body);

            return $body['response'] ?? '';
        } catch (GuzzleException $e) {
            $this->logError('generate', $e);
            throw new \RuntimeException("Failed to generate text: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Generate text with streaming support.
     */
    public function generateStream(
        string $prompt,
        callable $callback,
        ?string $model = null,
        array $options = []
    ): void {
        $model = $model ?? $this->config['default_model'];

        try {
            $response = $this->client->post('/api/generate', [
                'json' => array_merge([
                    'model' => $model,
                    'prompt' => $prompt,
                    'stream' => true,
                    'options' => [
                        'temperature' => $options['temperature'] ?? $this->config['temperature'] ?? 0.7,
                        'num_predict' => $options['max_tokens'] ?? $this->config['max_tokens'] ?? 2000,
                    ],
                ], $options),
                'stream' => true,
            ]);

            foreach ($this->streamLines($response->getBody()) as $line) {
                $data = json_decode($line, true);
                if (isset($data['response'])) {
                    $callback($data['response']);
                }

                if ($data['done'] ?? false) {
                    break;
                }
            }
        } catch (GuzzleException $e) {
            $this->logError('generateStream', $e);
            throw new \RuntimeException("Failed to stream text: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Create embeddings for text.
     */
    public function embed(string $text, ?string $model = null): array
    {
        return $this->embedBatch([$text], $model)[0] ?? [];
    }

    /**
     * Create embeddings for multiple texts in a single request.
     *
     * @param  string[]  $texts
     * @return array[]
     */
    public function embedBatch(array $texts, ?string $model = null): array
    {
        $model = $model ?? 'nomic-embed-text';

        try {
            $response = $this->client->post('/api/embed', [
                'json' => [
                    'model' => $model,
                    'input' => $texts,
                ],
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            $this->logUsage('embedBatch', $model, $body);

            return $body['embeddings'] ?? [];
        } catch (GuzzleException $e) {
            $this->logError('embedBatch', $e);
            throw new \RuntimeException("Failed to create embeddings: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * List available models.
     */
    public function listModels(): array
    {
        try {
            $response = $this->client->get('/api/tags');
            $body = json_decode($response->getBody()->getContents(), true);

            return $body['models'] ?? [];
        } catch (GuzzleException $e) {
            $this->logError('listModels', $e);
            throw new \RuntimeException("Failed to list models: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Pull a model from Ollama library.
     */
    public function pullModel(string $model, ?callable $progressCallback = null): bool
    {
        try {
            $response = $this->client->post('/api/pull', [
                'json' => ['name' => $model],
                'stream' => true,
            ]);

            foreach ($this->streamLines($response->getBody()) as $line) {
                $data = json_decode($line, true);

                if ($progressCallback && isset($data['status'])) {
                    $progressCallback($data);
                }

                if (isset($data['status']) && $data['status'] === 'success') {
                    return true;
                }
            }

            return false;
        } catch (GuzzleException $e) {
            $this->logError('pullModel', $e);
            throw new \RuntimeException("Failed to pull model: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Delete a model.
     */
    public function deleteModel(string $model): bool
    {
        try {
            $response = $this->client->delete('/api/delete', [
                'json' => ['name' => $model],
            ]);

            return $response->getStatusCode() === 200;
        } catch (GuzzleException $e) {
            $this->logError('deleteModel', $e);
            throw new \RuntimeException("Failed to delete model: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Check service health.
     */
    public function health(): array
    {
        try {
            $response = $this->client->get('/api/tags');

            return [
                'status' => 'healthy',
                'code' => $response->getStatusCode(),
            ];
        } catch (GuzzleException $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Yield lines from a stream using buffered reads (8 KB at a time).
     *
     * @return \Generator<string>
     */
    protected function streamLines($stream): \Generator
    {
        $buffer = '';

        while (! $stream->eof()) {
            $buffer .= $stream->read(8192);

            while (($newlinePos = strpos($buffer, "\n")) !== false) {
                $line = substr($buffer, 0, $newlinePos);
                $buffer = substr($buffer, $newlinePos + 1);

                if ($line !== '') {
                    yield $line;
                }
            }
        }

        if ($buffer !== '') {
            yield $buffer;
        }
    }

    /**
     * Log API usage.
     */
    protected function logUsage(string $operation, string $model, array $response): void
    {
        if (! config('local-ai.logging.enabled')) {
            return;
        }

        Log::channel(config('local-ai.logging.channel'))->info('Ollama API usage', [
            'operation' => $operation,
            'model' => $model,
            'total_duration' => $response['total_duration'] ?? null,
            'load_duration' => $response['load_duration'] ?? null,
            'prompt_eval_count' => $response['prompt_eval_count'] ?? null,
            'eval_count' => $response['eval_count'] ?? null,
        ]);
    }

    /**
     * Log errors.
     */
    protected function logError(string $operation, \Throwable $e): void
    {
        if (! config('local-ai.logging.enabled')) {
            return;
        }

        Log::channel(config('local-ai.logging.channel'))->error('Ollama API error', [
            'operation' => $operation,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }
}
