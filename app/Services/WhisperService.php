<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class WhisperService
{
    protected Client $client;
    protected array $config;

    public function __construct(string $host, array $config = [])
    {
        $this->config = $config;
        $this->client = new Client([
            'base_uri' => rtrim($host, '/'),
            'timeout' => $config['timeout'] ?? 300,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);
    }

    /**
     * Transcribe audio file to text.
     */
    public function transcribe(
        string  $audioPath,
        ?string $language = null,
        ?string $model = null
    ): string
    {
        if (!file_exists($audioPath)) {
            throw new \InvalidArgumentException("Audio file not found: {$audioPath}");
        }

        $language = $language ?? $this->config['language'] ?? 'en';
        $model = $model ?? $this->config['model'] ?? 'base';

        try {
            // Try new faster-whisper-server API format first
            $response = $this->client->post('/v1/audio/transcriptions', [
                'multipart' => [
                    [
                        'name' => 'file',
                        'contents' => fopen($audioPath, 'r'),
                        'filename' => basename($audioPath),
                    ],
                    [
                        'name' => 'language',
                        'contents' => $language,
                    ],
                ],
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            $this->logUsage('transcribe', $model, strlen(file_get_contents($audioPath)));

            return $body['text'] ?? '';
        } catch (GuzzleException $e) {
            // Fallback to old API format if new format fails
            try {
                $response = $this->client->post('/asr', [
                    'multipart' => [
                        [
                            'name' => 'audio_file',
                            'contents' => fopen($audioPath, 'r'),
                            'filename' => basename($audioPath),
                        ],
                        [
                            'name' => 'task',
                            'contents' => 'transcribe',
                        ],
                        [
                            'name' => 'language',
                            'contents' => $language,
                        ],
                        [
                            'name' => 'output',
                            'contents' => 'json',
                        ],
                    ],
                ]);

                $body = json_decode($response->getBody()->getContents(), true);
                return $body['text'] ?? '';
            } catch (GuzzleException $fallbackError) {
                $this->logError('transcribe', $fallbackError);
                throw new \RuntimeException("Failed to transcribe audio: {$fallbackError->getMessage()}", 0, $fallbackError);
            }
        }
    }

    /**
     * Transcribe audio with timestamps.
     */
    public function transcribeWithTimestamps(
        string  $audioPath,
        ?string $language = null
    ): array
    {
        if (!file_exists($audioPath)) {
            throw new \InvalidArgumentException("Audio file not found: {$audioPath}");
        }

        $language = $language ?? $this->config['language'] ?? 'en';

        try {
            $response = $this->client->post('/asr', [
                'multipart' => [
                    [
                        'name' => 'audio_file',
                        'contents' => fopen($audioPath, 'r'),
                        'filename' => basename($audioPath),
                    ],
                    [
                        'name' => 'task',
                        'contents' => 'transcribe',
                    ],
                    [
                        'name' => 'language',
                        'contents' => $language,
                    ],
                    [
                        'name' => 'output',
                        'contents' => 'json',
                    ],
                    [
                        'name' => 'word_timestamps',
                        'contents' => 'true',
                    ],
                ],
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            return [
                'text' => $body['text'] ?? '',
                'segments' => $body['segments'] ?? [],
                'language' => $body['language'] ?? $language,
            ];
        } catch (GuzzleException $e) {
            $this->logError('transcribeWithTimestamps', $e);
            throw new \RuntimeException("Failed to transcribe audio: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Translate audio to English.
     */
    public function translate(string $audioPath): string
    {
        if (!file_exists($audioPath)) {
            throw new \InvalidArgumentException("Audio file not found: {$audioPath}");
        }

        try {
            $response = $this->client->post('/asr', [
                'multipart' => [
                    [
                        'name' => 'audio_file',
                        'contents' => fopen($audioPath, 'r'),
                        'filename' => basename($audioPath),
                    ],
                    [
                        'name' => 'task',
                        'contents' => 'translate',
                    ],
                    [
                        'name' => 'output',
                        'contents' => 'json',
                    ],
                ],
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            return $body['text'] ?? '';
        } catch (GuzzleException $e) {
            $this->logError('translate', $e);
            throw new \RuntimeException("Failed to translate audio: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Detect language in audio.
     */
    public function detectLanguage(string $audioPath): array
    {
        if (!file_exists($audioPath)) {
            throw new \InvalidArgumentException("Audio file not found: {$audioPath}");
        }

        try {
            $response = $this->client->post('/asr', [
                'multipart' => [
                    [
                        'name' => 'audio_file',
                        'contents' => fopen($audioPath, 'r'),
                        'filename' => basename($audioPath),
                    ],
                    [
                        'name' => 'task',
                        'contents' => 'transcribe',
                    ],
                    [
                        'name' => 'output',
                        'contents' => 'json',
                    ],
                ],
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            return [
                'language' => $body['language'] ?? 'unknown',
                'probability' => $body['language_probability'] ?? 0.0,
            ];
        } catch (GuzzleException $e) {
            $this->logError('detectLanguage', $e);
            throw new \RuntimeException("Failed to detect language: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Check service health.
     */
    public function health(): array
    {
        try {
            $response = $this->client->get('/');

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
     * Log API usage.
     */
    protected function logUsage(string $operation, string $model, int $fileSize): void
    {
        if (!config('local-ai.logging.enabled')) {
            return;
        }

        Log::channel(config('local-ai.logging.channel'))->info('Whisper API usage', [
            'operation' => $operation,
            'model' => $model,
            'file_size_bytes' => $fileSize,
        ]);
    }

    /**
     * Log errors.
     */
    protected function logError(string $operation, \Throwable $e): void
    {
        if (!config('local-ai.logging.enabled')) {
            return;
        }

        Log::channel(config('local-ai.logging.channel'))->error('Whisper error', [
            'operation' => $operation,
            'error' => $e->getMessage(),
        ]);
    }
}
