<?php

namespace Tests\Unit;

use App\Services\QdrantService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;

class QdrantServiceTest extends TestCase
{
    protected array $requestHistory = [];

    protected function createMockService(array $responses, string $collection = 'test_collection'): QdrantService
    {
        $this->requestHistory = [];
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);

        $history = Middleware::history($this->requestHistory);
        $handlerStack->push($history);

        $service = new QdrantService('http://localhost:6333', [
            'collection' => $collection,
            'vector_size' => 768,
            'distance' => 'Cosine',
        ]);

        $reflection = new \ReflectionClass($service);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($service, new Client([
            'handler' => $handlerStack,
            'base_uri' => 'http://localhost:6333',
        ]));

        return $service;
    }

    public function test_upsert_successfully_stores_point(): void
    {
        $service = $this->createMockService([
            new Response(200, [], json_encode([
                'result' => ['operation_id' => 123, 'status' => 'completed'],
                'status' => 'ok',
            ])),
        ]);

        $point = [
            'id' => 'test-id-123',
            'vector' => array_fill(0, 768, 0.1),
            'payload' => [
                'content' => 'Test content',
                'novel_id' => 1,
            ],
        ];

        $result = $service->upsert($point);

        $this->assertIsString($result);
        $this->assertEquals('test-id-123', $result);
        $this->assertCount(1, $this->requestHistory);

        $request = $this->requestHistory[0]['request'];
        $this->assertEquals('PUT', $request->getMethod());
        $this->assertStringContainsString('/collections/test_collection/points', $request->getUri()->getPath());
    }

    public function test_batch_upsert_stores_multiple_points(): void
    {
        $service = $this->createMockService([
            new Response(200, [], json_encode([
                'result' => ['operation_id' => 456, 'status' => 'completed'],
                'status' => 'ok',
            ])),
        ]);

        $points = [
            [
                'id' => 'id-1',
                'vector' => array_fill(0, 768, 0.1),
                'payload' => ['content' => 'Content 1'],
            ],
            [
                'id' => 'id-2',
                'vector' => array_fill(0, 768, 0.2),
                'payload' => ['content' => 'Content 2'],
            ],
        ];

        $result = $service->batchUpsert($points);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        $body = json_decode($this->requestHistory[0]['request']->getBody()->getContents(), true);
        $this->assertCount(2, $body['points']);
    }

    public function test_search_returns_matching_results(): void
    {
        $service = $this->createMockService([
            new Response(200, [], json_encode([
                'result' => [
                    [
                        'id' => 'result-1',
                        'score' => 0.95,
                        'payload' => ['content' => 'Matching content'],
                    ],
                    [
                        'id' => 'result-2',
                        'score' => 0.85,
                        'payload' => ['content' => 'Another match'],
                    ],
                ],
                'status' => 'ok',
            ])),
        ]);

        $queryVector = array_fill(0, 768, 0.1);
        $results = $service->search($queryVector, 5, 0.7);

        $this->assertIsArray($results);
        $this->assertCount(2, $results);
        $this->assertEquals('result-1', $results[0]['id']);
        $this->assertEquals(0.95, $results[0]['score']);

        $request = $this->requestHistory[0]['request'];
        $this->assertEquals('POST', $request->getMethod());
        $this->assertStringContainsString('/collections/test_collection/points/search', $request->getUri()->getPath());
    }

    public function test_search_with_filter(): void
    {
        $service = $this->createMockService([
            new Response(200, [], json_encode([
                'result' => [],
                'status' => 'ok',
            ])),
        ]);

        $queryVector = array_fill(0, 768, 0.1);
        $filter = [
            'must' => [
                ['key' => 'novel_id', 'match' => ['value' => 123]],
            ],
        ];

        $service->search($queryVector, 5, 0.7, null, $filter);

        $body = json_decode($this->requestHistory[0]['request']->getBody()->getContents(), true);
        $this->assertArrayHasKey('filter', $body);
        $this->assertEquals($filter, $body['filter']);
    }

    public function test_get_retrieves_point_by_id(): void
    {
        $service = $this->createMockService([
            new Response(200, [], json_encode([
                'result' => [
                    'id' => 'test-id',
                    'vector' => array_fill(0, 768, 0.1),
                    'payload' => ['content' => 'Retrieved content'],
                ],
                'status' => 'ok',
            ])),
        ]);

        $result = $service->get('test-id');

        $this->assertIsArray($result);
        $this->assertEquals('test-id', $result['id']);

        $request = $this->requestHistory[0]['request'];
        $this->assertStringContainsString('/collections/test_collection/points/test-id', $request->getUri()->getPath());
    }

    public function test_delete_removes_point(): void
    {
        $service = $this->createMockService([
            new Response(200, [], json_encode([
                'result' => ['operation_id' => 789, 'status' => 'completed'],
                'status' => 'ok',
            ])),
        ]);

        $result = $service->delete('test-id');

        $this->assertTrue($result);

        $request = $this->requestHistory[0]['request'];
        $this->assertEquals('POST', $request->getMethod());
        $this->assertStringContainsString('/collections/test_collection/points/delete', $request->getUri()->getPath());
    }

    public function test_delete_by_filter(): void
    {
        $service = $this->createMockService([
            new Response(200, [], json_encode([
                'result' => ['operation_id' => 890, 'status' => 'completed'],
                'status' => 'ok',
            ])),
        ]);

        $filter = [
            'must' => [
                ['key' => 'novel_id', 'match' => ['value' => 123]],
            ],
        ];

        $result = $service->deleteByFilter($filter);

        $this->assertTrue($result);

        $body = json_decode($this->requestHistory[0]['request']->getBody()->getContents(), true);
        $this->assertEquals($filter, $body['filter']);
    }

    public function test_count_returns_number_of_points(): void
    {
        $service = $this->createMockService([
            new Response(200, [], json_encode([
                'result' => ['count' => 42],
                'status' => 'ok',
            ])),
        ]);

        $count = $service->count();

        $this->assertEquals(42, $count);

        $request = $this->requestHistory[0]['request'];
        $this->assertStringContainsString('/collections/test_collection/points/count', $request->getUri()->getPath());
    }

    public function test_count_with_filter(): void
    {
        $service = $this->createMockService([
            new Response(200, [], json_encode([
                'result' => ['count' => 15],
                'status' => 'ok',
            ])),
        ]);

        $filter = [
            'must' => [
                ['key' => 'type', 'match' => ['value' => 'web_link']],
            ],
        ];

        $count = $service->count(null, $filter);

        $this->assertEquals(15, $count);

        $body = json_decode($this->requestHistory[0]['request']->getBody()->getContents(), true);
        $this->assertEquals($filter, $body['filter']);
    }

    public function test_list_collections_returns_array(): void
    {
        $service = $this->createMockService([
            new Response(200, [], json_encode([
                'result' => [
                    'collections' => [
                        ['name' => 'novel_1'],
                        ['name' => 'novel_2'],
                    ],
                ],
                'status' => 'ok',
            ])),
        ]);

        $result = $service->listCollections();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('novel_1', $result[0]['name']);
    }

    public function test_delete_collection_removes_collection(): void
    {
        $service = $this->createMockService([
            new Response(200, [], json_encode([
                'result' => true,
                'status' => 'ok',
            ])),
        ]);

        $result = $service->deleteCollection();

        $this->assertTrue($result);

        $request = $this->requestHistory[0]['request'];
        $this->assertEquals('DELETE', $request->getMethod());
        $this->assertStringContainsString('/collections/test_collection', $request->getUri()->getPath());
    }

    public function test_health_returns_healthy_status(): void
    {
        $service = $this->createMockService([
            new Response(200, [], ''),
        ]);

        $result = $service->health();

        $this->assertEquals('healthy', $result['status']);
        $this->assertEquals(200, $result['code']);
    }

    public function test_health_returns_unhealthy_on_error(): void
    {
        $service = $this->createMockService([
            new RequestException(
                'Connection refused',
                new Request('GET', '/')
            ),
        ]);

        $result = $service->health();

        $this->assertEquals('unhealthy', $result['status']);
        $this->assertArrayHasKey('error', $result);
    }

    public function test_upsert_throws_exception_on_error(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to upsert point');

        $service = $this->createMockService([
            new RequestException(
                'Server error',
                new Request('PUT', '/collections/test_collection/points')
            ),
        ]);

        $point = [
            'id' => 'test-id',
            'vector' => array_fill(0, 768, 0.1),
            'payload' => ['content' => 'Test'],
        ];

        $service->upsert($point);
    }
}
