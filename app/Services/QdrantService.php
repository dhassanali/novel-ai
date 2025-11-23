<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class QdrantService
{
    protected Client $client;
    protected array $config;
    protected string $collection;

    public function __construct(string $host, array $config = [])
    {
        $this->config = $config;
        $this->collection = $config['collection'] ?? 'documents';

        $this->client = new Client([
            'base_uri' => rtrim($host, '/'),
            'timeout' => $config['timeout'] ?? 30,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ]);

        // Create collection if it doesn't exist
        $this->ensureCollectionExists();
    }

    /**
     * Ensure collection exists, create if not.
     */
    protected function ensureCollectionExists(): void
    {
        try {
            $this->client->get("/collections/{$this->collection}");
        } catch (GuzzleException $e) {
            if ($e->getCode() === 404) {
                $this->createCollection();
            }
        }
    }

    /**
     * Create a collection.
     */
    public function createCollection(
        ?string $collection = null,
        int $vectorSize = null,
        string $distance = null
    ): bool {
        $collection = $collection ?? $this->collection;
        $vectorSize = $vectorSize ?? $this->config['vector_size'] ?? 384;
        $distance = $distance ?? $this->config['distance'] ?? 'Cosine';

        try {
            $this->client->put("/collections/{$collection}", [
                'json' => [
                    'vectors' => [
                        'size' => $vectorSize,
                        'distance' => $distance,
                    ],
                ],
            ]);

            Log::info("Created Qdrant collection: {$collection}");
            return true;
        } catch (GuzzleException $e) {
            $this->logError('createCollection', $e);
            return false;
        }
    }

    /**
     * Upsert a point (document with vector).
     */
    public function upsert(array $point, ?string $collection = null): string
    {
        $collection = $collection ?? $this->collection;
        $id = $point['id'] ?? Str::uuid()->toString();

        try {
            $this->client->put("/collections/{$collection}/points", [
                'json' => [
                    'points' => [
                        [
                            'id' => $id,
                            'vector' => $point['vector'],
                            'payload' => $point['payload'] ?? [],
                        ],
                    ],
                ],
            ]);

            return $id;
        } catch (GuzzleException $e) {
            $this->logError('upsert', $e);
            throw new \RuntimeException("Failed to upsert point: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Batch upsert multiple points.
     */
    public function batchUpsert(array $points, ?string $collection = null): array
    {
        $collection = $collection ?? $this->collection;
        $formattedPoints = [];

        foreach ($points as $point) {
            $id = $point['id'] ?? Str::uuid()->toString();
            $formattedPoints[] = [
                'id' => $id,
                'vector' => $point['vector'],
                'payload' => $point['payload'] ?? [],
            ];
        }

        try {
            $this->client->put("/collections/{$collection}/points", [
                'json' => ['points' => $formattedPoints],
            ]);

            return array_column($formattedPoints, 'id');
        } catch (GuzzleException $e) {
            $this->logError('batchUpsert', $e);
            throw new \RuntimeException("Failed to batch upsert: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Search for similar vectors.
     */
    public function search(
        array $queryVector,
        int $limit = 5,
        float $scoreThreshold = 0.7,
        ?string $collection = null,
        array $filter = []
    ): array {
        $collection = $collection ?? $this->collection;

        try {
            $payload = [
                'vector' => $queryVector,
                'limit' => $limit,
                'score_threshold' => $scoreThreshold,
                'with_payload' => true,
            ];

            if (!empty($filter)) {
                $payload['filter'] = $filter;
            }

            $response = $this->client->post("/collections/{$collection}/points/search", [
                'json' => $payload,
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            return $body['result'] ?? [];
        } catch (GuzzleException $e) {
            if ($e->hasResponse()) {
                $responseBody = $e->getResponse()->getBody()->getContents();
                Log::error('Qdrant search error response: ' . $responseBody);
            }
            $this->logError('search', $e);
            throw new \RuntimeException("Failed to search: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Get a point by ID.
     */
    public function get(string $id, ?string $collection = null): ?array
    {
        $collection = $collection ?? $this->collection;

        try {
            $response = $this->client->get("/collections/{$collection}/points/{$id}");
            $body = json_decode($response->getBody()->getContents(), true);

            return $body['result'] ?? null;
        } catch (GuzzleException $e) {
            if ($e->getCode() === 404) {
                return null;
            }
            $this->logError('get', $e);
            throw new \RuntimeException("Failed to get point: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Delete a point by ID.
     */
    public function delete(string $id, ?string $collection = null): bool
    {
        $collection = $collection ?? $this->collection;

        try {
            $this->client->post("/collections/{$collection}/points/delete", [
                'json' => [
                    'points' => [$id],
                ],
            ]);

            return true;
        } catch (GuzzleException $e) {
            $this->logError('delete', $e);
            return false;
        }
    }

    /**
     * Delete points by filter.
     */
    public function deleteByFilter(array $filter, ?string $collection = null): bool
    {
        $collection = $collection ?? $this->collection;

        try {
            $this->client->post("/collections/{$collection}/points/delete", [
                'json' => ['filter' => $filter],
            ]);

            return true;
        } catch (GuzzleException $e) {
            $this->logError('deleteByFilter', $e);
            return false;
        }
    }

    /**
     * Count points in collection.
     */
    public function count(?string $collection = null, array $filter = []): int
    {
        $collection = $collection ?? $this->collection;

        try {
            $payload = empty($filter) ? [] : ['filter' => $filter];

            $response = $this->client->post("/collections/{$collection}/points/count", [
                'json' => $payload,
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            return $body['result']['count'] ?? 0;
        } catch (GuzzleException $e) {
            $this->logError('count', $e);
            return 0;
        }
    }

    /**
     * Delete collection.
     */
    public function deleteCollection(?string $collection = null): bool
    {
        $collection = $collection ?? $this->collection;

        try {
            $this->client->delete("/collections/{$collection}");
            return true;
        } catch (GuzzleException $e) {
            $this->logError('deleteCollection', $e);
            return false;
        }
    }

    /**
     * List all collections.
     */
    public function listCollections(): array
    {
        try {
            $response = $this->client->get('/collections');
            $body = json_decode($response->getBody()->getContents(), true);

            return $body['result']['collections'] ?? [];
        } catch (GuzzleException $e) {
            $this->logError('listCollections', $e);
            return [];
        }
    }

    /**
     * Get collection info.
     */
    public function getCollectionInfo(?string $collection = null): ?array
    {
        $collection = $collection ?? $this->collection;

        try {
            $response = $this->client->get("/collections/{$collection}");
            $body = json_decode($response->getBody()->getContents(), true);

            return $body['result'] ?? null;
        } catch (GuzzleException $e) {
            $this->logError('getCollectionInfo', $e);
            return null;
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
     * Log errors.
     */
    protected function logError(string $operation, \Throwable $e): void
    {
        if (!config('local-ai.logging.enabled')) {
            return;
        }

        Log::channel(config('local-ai.logging.channel'))->error('Qdrant error', [
            'operation' => $operation,
            'error' => $e->getMessage(),
        ]);
    }
}
