<?php

namespace Tests\Feature;

use App\Facades\LocalAI;
use App\Models\Character;
use App\Models\Location;
use App\Models\Novel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsistencyCheckTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Novel $novel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->novel = Novel::factory()->create(['user_id' => $this->user->id]);
    }

    public function test_consistency_check_returns_issues_and_summary(): void
    {
        LocalAI::shouldReceive('generate')
            ->once()
            ->andReturn('{"issues":[{"severity":"high","category":"character","description":"Elena acts cowardly in chapter 3, contradicting her bold personality."}],"summary":"1 issue found."}');

        $response = $this->actingAs($this->user)
            ->postJson("/novels/{$this->novel->id}/consistency-check");

        $response->assertOk();
        $response->assertJsonStructure(['issues', 'summary']);
        $this->assertCount(1, $response->json('issues'));
        $this->assertEquals('high', $response->json('issues.0.severity'));
        $this->assertEquals('character', $response->json('issues.0.category'));
    }

    public function test_consistency_check_with_no_issues(): void
    {
        LocalAI::shouldReceive('generate')
            ->once()
            ->andReturn('{"issues":[],"summary":"No consistency issues found."}');

        $response = $this->actingAs($this->user)
            ->postJson("/novels/{$this->novel->id}/consistency-check");

        $response->assertOk();
        $this->assertEmpty($response->json('issues'));
        $this->assertEquals('No consistency issues found.', $response->json('summary'));
    }

    public function test_consistency_check_handles_malformed_ai_response_gracefully(): void
    {
        LocalAI::shouldReceive('generate')
            ->once()
            ->andReturn('Sorry, I cannot check that right now. Here are some thoughts...');

        $response = $this->actingAs($this->user)
            ->postJson("/novels/{$this->novel->id}/consistency-check");

        $response->assertOk();
        $response->assertJsonStructure(['issues', 'summary']);
        $this->assertIsArray($response->json('issues'));
    }

    public function test_consistency_check_handles_llm_with_markdown_fences(): void
    {
        LocalAI::shouldReceive('generate')
            ->once()
            ->andReturn("```json\n{\"issues\":[],\"summary\":\"All good.\"}\n```");

        $response = $this->actingAs($this->user)
            ->postJson("/novels/{$this->novel->id}/consistency-check");

        $response->assertOk();
        $this->assertEmpty($response->json('issues'));
        $this->assertEquals('All good.', $response->json('summary'));
    }

    public function test_other_user_cannot_run_consistency_check(): void
    {
        $other = User::factory()->create();

        $response = $this->actingAs($other)
            ->postJson("/novels/{$this->novel->id}/consistency-check");

        $response->assertForbidden();
    }

    public function test_guest_cannot_run_consistency_check(): void
    {
        $response = $this->postJson("/novels/{$this->novel->id}/consistency-check");

        $response->assertUnauthorized();
    }

    public function test_consistency_check_includes_character_data(): void
    {
        Character::factory()->create([
            'novel_id' => $this->novel->id,
            'name' => 'Elena',
            'personality_traits' => 'Bold',
        ]);
        Location::factory()->create([
            'novel_id' => $this->novel->id,
            'name' => 'The City',
        ]);

        LocalAI::shouldReceive('generate')
            ->once()
            ->withArgs(function (string $prompt) {
                return str_contains($prompt, 'Elena') && str_contains($prompt, 'The City');
            })
            ->andReturn('{"issues":[],"summary":"No issues."}');

        $this->actingAs($this->user)
            ->postJson("/novels/{$this->novel->id}/consistency-check");
    }
}
