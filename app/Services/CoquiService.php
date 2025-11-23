<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CoquiService
{
    protected Client $client;
    protected array $config;

    public function __construct(string $host, array $config = [])
    {
        $this->config = $config;
        $this->client = new Client([
            'base_uri' => rtrim($host, '/'),
            'timeout' => $config['timeout'] ?? 60,
        ]);
    }

    /**
     * Generate speech from text.
     */
    public function speak(
        string $text,
        ?string $voice = null,
        float $speed = 1.0
    ): string {
        $voice = $voice ?? $this->config['default_voice'] ?? 'en-us-female';

        try {
            $response = $this->client->post('/api/tts', [
                'json' => [
                    'text' => $text,
                    'speaker_id' => $voice,
                    'style_wav' => '',
                    'language_id' => $this->extractLanguage($voice),
                ],
            ]);

            $audioData = $response->getBody()->getContents();

            $this->logUsage('speak', strlen($text), strlen($audioData));

            return $audioData;
        } catch (GuzzleException $e) {
            $this->logError('speak', $e);
            throw new \RuntimeException("Failed to generate speech: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Generate speech and save to file.
     */
    public function speakToFile(
        string $text,
        ?string $filename = null,
        ?string $voice = null,
        float $speed = 1.0
    ): string {
        $audioData = $this->speak($text, $voice, $speed);

        $filename = $filename ?? $this->generateFilename();
        $disk = config('local-ai.storage.disk', 'local');
        $path = config('local-ai.storage.audio_path', 'local-ai/audio');
        $fullPath = "{$path}/{$filename}";

        Storage::disk($disk)->put($fullPath, $audioData);

        return $fullPath;
    }

    /**
     * Get available voices.
     */
    public function listVoices(): array
    {
        try {
            $response = $this->client->get('/api/speakers');
            $body = json_decode($response->getBody()->getContents(), true);

            return $body['speakers'] ?? [];
        } catch (GuzzleException $e) {
            $this->logError('listVoices', $e);
            return [];
        }
    }

    /**
     * Get available languages.
     */
    public function listLanguages(): array
    {
        try {
            $response = $this->client->get('/api/languages');
            $body = json_decode($response->getBody()->getContents(), true);

            return $body['languages'] ?? [];
        } catch (GuzzleException $e) {
            $this->logError('listLanguages', $e);
            return [];
        }
    }

    /**
     * Get server info.
     */
    public function getInfo(): array
    {
        try {
            $response = $this->client->get('/api/info');
            $body = json_decode($response->getBody()->getContents(), true);

            return $body ?? [];
        } catch (GuzzleException $e) {
            $this->logError('getInfo', $e);
            return [];
        }
    }

    /**
     * Clone voice from audio sample (if supported).
     */
    public function cloneVoice(string $audioPath, string $voiceName): array
    {
        if (!file_exists($audioPath)) {
            throw new \InvalidArgumentException("Audio file not found: {$audioPath}");
        }

        try {
            $response = $this->client->post('/api/clone', [
                'multipart' => [
                    [
                        'name' => 'audio',
                        'contents' => fopen($audioPath, 'r'),
                        'filename' => basename($audioPath),
                    ],
                    [
                        'name' => 'name',
                        'contents' => $voiceName,
                    ],
                ],
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            return $body ?? [];
        } catch (GuzzleException $e) {
            $this->logError('cloneVoice', $e);
            throw new \RuntimeException("Failed to clone voice: {$e->getMessage()}", 0, $e);
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
     * Extract language code from voice ID.
     */
    protected function extractLanguage(string $voice): string
    {
        // Extract language from voice ID like "en-us-female"
        $parts = explode('-', $voice);
        return $parts[0] ?? 'en';
    }

    /**
     * Generate unique filename for audio.
     */
    protected function generateFilename(): string
    {
        return Str::uuid()->toString() . '.wav';
    }

    /**
     * Log API usage.
     */
    protected function logUsage(string $operation, int $textLength, int $audioSize): void
    {
        if (!config('local-ai.logging.enabled')) {
            return;
        }

        Log::channel(config('local-ai.logging.channel'))->info('Coqui TTS usage', [
            'operation' => $operation,
            'text_length' => $textLength,
            'audio_size_bytes' => $audioSize,
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

        Log::channel(config('local-ai.logging.channel'))->error('Coqui TTS error', [
            'operation' => $operation,
            'error' => $e->getMessage(),
        ]);
    }
}
