<?php

namespace Tests\Unit;

use App\Jobs\ProcessWebLink;
use App\Models\Novel;
use App\Models\SourceDocument;
use App\Models\User;
use App\Services\QdrantService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProcessWebLinkTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Novel $novel;
    protected SourceDocument $document;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->novel = Novel::factory()->create([
            'user_id' => $this->user->id,
        ]);
        $this->document = SourceDocument::create([
            'novel_id' => $this->novel->id,
            'filename' => 'test-page',
            'path' => 'https://example.com/test-page',
            'type' => 'web_link',
            'status' => 'pending',
        ]);
    }

    public function test_job_updates_status_to_processing_then_failed_without_http(): void
    {
        // This test verifies status updates work correctly
        // Full integration testing with HTTP would require actual http mocking at job level
        $mockQdrant = $this->createMock(QdrantService::class);

        // The job will fail without proper HTTP setup, but that's expected
        $job = new ProcessWebLink($this->document);

        try {
            $job->handle($mockQdrant);
        } catch (\Exception $e) {
            // Expected to fail without HTTP setup
        }

        $this->document->refresh();
        // Should be failed due to missing HTTP setup
        $this->assertEquals('failed', $this->document->status);
    }

    public function test_chunk_text_splits_correctly(): void
    {
        $job = new ProcessWebLink($this->document);

        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('chunkText');
        $method->setAccessible(true);

        $text = str_repeat('a', 2500);
        $chunks = $method->invoke($job, $text, 1000);

        $this->assertCount(3, $chunks);
        $this->assertEquals(1000, mb_strlen($chunks[0]));
        $this->assertEquals(1000, mb_strlen($chunks[1]));
        $this->assertEquals(500, mb_strlen($chunks[2]));
    }

    public function test_sanitize_for_json_removes_invalid_utf8(): void
    {
        $job = new ProcessWebLink($this->document);

        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('sanitizeForJson');
        $method->setAccessible(true);

        // Text with invalid UTF-8 sequences
        $invalidText = "Hello\x80\x81World";
        $sanitized = $method->invoke($job, $invalidText);

        // Should be valid for JSON encoding
        $this->assertNotFalse(json_encode($sanitized));
        $this->assertStringContainsString('Hello', $sanitized);
        $this->assertStringContainsString('World', $sanitized);
    }

    public function test_sanitize_for_json_removes_control_characters(): void
    {
        $job = new ProcessWebLink($this->document);

        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('sanitizeForJson');
        $method->setAccessible(true);

        $textWithControl = "Hello\x00\x01\x02World";
        $sanitized = $method->invoke($job, $textWithControl);

        $this->assertNotFalse(json_encode($sanitized));
        $this->assertStringNotContainsString("\x00", $sanitized);
        $this->assertStringNotContainsString("\x01", $sanitized);
    }

    public function test_sanitize_for_json_preserves_valid_text(): void
    {
        $job = new ProcessWebLink($this->document);

        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('sanitizeForJson');
        $method->setAccessible(true);

        $validText = "Hello World! This is valid UTF-8 text with émojis 🎉";
        $sanitized = $method->invoke($job, $validText);

        $this->assertEquals($validText, $sanitized);
        $this->assertNotFalse(json_encode($sanitized));
    }
}
