<?php

namespace Tests\Unit;

use App\Services\OllamaService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;

class OllamaServiceTest extends TestCase
{
    protected array $requestHistory = [];

    protected function createMockService(array $responses): OllamaService
    {
        $this->requestHistory = [];
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);

        // Track requests
        $history = Middleware::history($this->requestHistory);
        $handlerStack->push($history);

        $service = new OllamaService('http://localhost:11434', [
            'default_model' => 'llama2',
            'temperature' => 0.7,
            'max_tokens' => 2000,
        ]);

        // Use reflection to inject mock client
        $reflection = new \ReflectionClass($service);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($service, new Client([
            'handler' => $handlerStack,
            'base_uri' => 'http://localhost:11434',
        ]));

        return $service;
    }

    public function test_generate_returns_text(): void
    {
        $service = $this->createMockService([
            new Response(200, [], json_encode([
                'response' => 'Generated text response',
                'done' => true,
                'total_duration' => 1000000,
            ])),
        ]);

        $result = $service->generate('Test prompt');

        $this->assertEquals('Generated text response', $result);
        $this->assertCount(1, $this->requestHistory);

        $request = $this->requestHistory[0]['request'];
        $this->assertEquals('POST', $request->getMethod());
        $this->assertStringContainsString('/api/generate', $request->getUri()->getPath());
    }

    public function test_generate_uses_custom_model(): void
    {
        $service = $this->createMockService([
            new Response(200, [], json_encode([
                'response' => 'Custom model response',
                'done' => true,
            ])),
        ]);

        $result = $service->generate('Test prompt', 'custom-model');

        $this->assertEquals('Custom model response', $result);

        $body = json_decode($this->requestHistory[0]['request']->getBody()->getContents(), true);
        $this->assertEquals('custom-model', $body['model']);
    }

    public function test_generate_handles_temperature_option(): void
    {
        $service = $this->createMockService([
            new Response(200, [], json_encode([
                'response' => 'Response',
                'done' => true,
            ])),
        ]);

        $service->generate('Test prompt', null, ['temperature' => 0.9]);

        $body = json_decode($this->requestHistory[0]['request']->getBody()->getContents(), true);
        $this->assertEquals(0.9, $body['options']['temperature']);
    }

    public function test_generate_throws_exception_on_error(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to generate text');

        $service = $this->createMockService([
            new RequestException(
                'Error Communicating with Server',
                new Request('POST', '/api/generate')
            ),
        ]);

        $service->generate('Test prompt');
    }

    public function test_embed_returns_vector(): void
    {
        $expectedEmbedding = array_fill(0, 768, 0.1);

        $service = $this->createMockService([
            new Response(200, [], json_encode([
                'embeddings' => [$expectedEmbedding],
            ])),
        ]);

        $result = $service->embed('Test text');

        $this->assertEquals($expectedEmbedding, $result);
        $this->assertCount(1, $this->requestHistory);

        $request = $this->requestHistory[0]['request'];
        $this->assertStringContainsString('/api/embed', $request->getUri()->getPath());
    }

    public function test_embed_sends_input_field(): void
    {
        $service = $this->createMockService([
            new Response(200, [], json_encode([
                'embeddings' => [[0.1, 0.2]],
            ])),
        ]);

        $service->embed('Test text');

        $body = json_decode($this->requestHistory[0]['request']->getBody()->getContents(), true);
        $this->assertArrayHasKey('input', $body);
        $this->assertArrayNotHasKey('prompt', $body);
        $this->assertEquals('Test text', $body['input']);
    }

    public function test_embed_uses_custom_model(): void
    {
        $service = $this->createMockService([
            new Response(200, [], json_encode([
                'embeddings' => [[0.1, 0.2]],
            ])),
        ]);

        $service->embed('Test text', 'custom-embed-model');

        $body = json_decode($this->requestHistory[0]['request']->getBody()->getContents(), true);
        $this->assertEquals('custom-embed-model', $body['model']);
    }

    public function test_embed_throws_exception_on_error(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to create embedding');

        $service = $this->createMockService([
            new RequestException(
                'Error',
                new Request('POST', '/api/embed')
            ),
        ]);

        $service->embed('Test text');
    }

    public function test_list_models_returns_array(): void
    {
        $service = $this->createMockService([
            new Response(200, [], json_encode([
                'models' => [
                    ['name' => 'llama2', 'size' => 1234567],
                    ['name' => 'mistral', 'size' => 2345678],
                ],
            ])),
        ]);

        $result = $service->listModels();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('llama2', $result[0]['name']);
        $this->assertEquals('mistral', $result[1]['name']);
    }

    public function test_list_models_throws_exception_on_error(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to list models');

        $service = $this->createMockService([
            new RequestException(
                'Error',
                new Request('GET', '/api/tags')
            ),
        ]);

        $service->listModels();
    }

    public function test_delete_model_returns_true_on_success(): void
    {
        $service = $this->createMockService([
            new Response(200, [], ''),
        ]);

        $result = $service->deleteModel('test-model');

        $this->assertTrue($result);
        $this->assertStringContainsString('/api/delete', $this->requestHistory[0]['request']->getUri()->getPath());
    }

    public function test_health_returns_healthy_status(): void
    {
        $service = $this->createMockService([
            new Response(200, [], json_encode(['models' => []])),
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
                new Request('GET', '/api/tags')
            ),
        ]);

        $result = $service->health();

        $this->assertEquals('unhealthy', $result['status']);
        $this->assertArrayHasKey('error', $result);
    }
}
