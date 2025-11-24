<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Character;
use App\Models\Location;
use App\Models\Novel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChapterControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Novel $novel;
    protected Chapter $chapter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->novel = Novel::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Test Novel',
            'genre' => 'Fantasy',
            'description' => 'A test novel',
        ]);
        $this->chapter = Chapter::factory()->create([
            'novel_id' => $this->novel->id,
            'title' => 'Chapter 1',
            'order' => 1,
        ]);
    }

    public function test_can_create_chapter(): void
    {
        $response = $this->actingAs($this->user)
            ->post("/novels/{$this->novel->id}/chapters", [
                'title' => 'New Chapter',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('chapters', [
            'novel_id' => $this->novel->id,
            'title' => 'New Chapter',
        ]);
    }

    public function test_chapter_title_is_required(): void
    {
        $response = $this->actingAs($this->user)
            ->post("/novels/{$this->novel->id}/chapters", []);

        $response->assertSessionHasErrors('title');
    }

    public function test_new_chapter_gets_correct_order(): void
    {
        $this->actingAs($this->user)
            ->post("/novels/{$this->novel->id}/chapters", [
                'title' => 'Chapter 2',
            ]);

        $this->assertDatabaseHas('chapters', [
            'novel_id' => $this->novel->id,
            'title' => 'Chapter 2',
            'order' => 2,
        ]);
    }

    public function test_can_update_chapter_title(): void
    {
        $response = $this->actingAs($this->user)
            ->put("/novels/{$this->novel->id}/chapters/{$this->chapter->id}", [
                'title' => 'Updated Title',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('chapters', [
            'id' => $this->chapter->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_can_update_chapter_content(): void
    {
        $response = $this->actingAs($this->user)
            ->put("/novels/{$this->novel->id}/chapters/{$this->chapter->id}", [
                'content' => 'Updated chapter content',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('chapters', [
            'id' => $this->chapter->id,
            'content' => 'Updated chapter content',
        ]);
    }

    public function test_update_returns_json_when_requested(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson("/novels/{$this->novel->id}/chapters/{$this->chapter->id}", [
                'content' => 'New content',
            ]);

        $response->assertOk();
        $response->assertJson(['status' => 'saved']);
    }

    public function test_generate_requires_prompt(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/novels/{$this->novel->id}/chapters/{$this->chapter->id}/generate", []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('prompt');
    }

    public function test_generate_returns_text(): void
    {
        \App\Facades\LocalAI::shouldReceive('askDocuments')
            ->once()
            ->andReturn('Generated text from AI');

        $response = $this->actingAs($this->user)
            ->postJson("/novels/{$this->novel->id}/chapters/{$this->chapter->id}/generate", [
                'prompt' => 'Write something interesting',
                'mode' => 'context',
            ]);

        $response->assertOk();
        $response->assertJson(['text' => 'Generated text from AI']);
    }

    public function test_generate_with_web_mode(): void
    {
        \App\Facades\LocalAI::shouldReceive('generateWithWebSearch')
            ->once()
            ->with('Test prompt')
            ->andReturn('Web-enhanced generated text');

        $response = $this->actingAs($this->user)
            ->postJson("/novels/{$this->novel->id}/chapters/{$this->chapter->id}/generate", [
                'prompt' => 'Test prompt',
                'mode' => 'web',
            ]);

        $response->assertOk();
        $response->assertJson(['text' => 'Web-enhanced generated text']);
    }

    public function test_analyze_requires_selection(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/novels/{$this->novel->id}/chapters/{$this->chapter->id}/analyze", []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('selection');
    }

    public function test_analyze_returns_analysis(): void
    {
        \App\Facades\LocalAI::shouldReceive('generate')
            ->once()
            ->andReturn('Analysis results here');

        $response = $this->actingAs($this->user)
            ->postJson("/novels/{$this->novel->id}/chapters/{$this->chapter->id}/analyze", [
                'selection' => 'Text to analyze',
            ]);

        $response->assertOk();
        $response->assertJson(['analysis' => 'Analysis results here']);
    }

    public function test_suggest_requires_context(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/novels/{$this->novel->id}/chapters/{$this->chapter->id}/suggest", []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('context');
    }

    public function test_suggest_includes_previous_chapters(): void
    {
        // Create previous chapters
        Chapter::factory()->create([
            'novel_id' => $this->novel->id,
            'order' => 0,
            'content' => 'Previous chapter content',
        ]);

        \App\Facades\LocalAI::shouldReceive('generate')
            ->once()
            ->withArgs(function ($prompt) {
                return str_contains($prompt, 'Previous chapter content');
            })
            ->andReturn('Story continuation');

        $response = $this->actingAs($this->user)
            ->postJson("/novels/{$this->novel->id}/chapters/{$this->chapter->id}/suggest", [
                'context' => 'Current context',
            ]);

        $response->assertOk();
    }

    public function test_suggest_includes_lore_context(): void
    {
        // Create character and location
        Character::factory()->create([
            'novel_id' => $this->novel->id,
            'name' => 'Hero',
            'role' => 'Protagonist',
            'description' => 'Main character',
        ]);

        Location::factory()->create([
            'novel_id' => $this->novel->id,
            'name' => 'Castle',
            'description' => 'Royal castle',
        ]);

        \App\Facades\LocalAI::shouldReceive('generate')
            ->once()
            ->withArgs(function ($prompt) {
                return str_contains($prompt, 'Hero') && str_contains($prompt, 'Castle');
            })
            ->andReturn('Contextualized story');

        $response = $this->actingAs($this->user)
            ->postJson("/novels/{$this->novel->id}/chapters/{$this->chapter->id}/suggest", [
                'context' => 'Current context',
            ]);

        $response->assertOk();
        $response->assertJson(['suggestion' => 'Contextualized story']);
    }

    public function test_rewrite_requires_selection_and_instruction(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/novels/{$this->novel->id}/chapters/{$this->chapter->id}/rewrite", [
                'selection' => 'Some text',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('instruction');

        $response = $this->actingAs($this->user)
            ->postJson("/novels/{$this->novel->id}/chapters/{$this->chapter->id}/rewrite", [
                'instruction' => 'Make it better',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('selection');
    }

    public function test_rewrite_returns_rewritten_text(): void
    {
        \App\Facades\LocalAI::shouldReceive('generate')
            ->once()
            ->andReturn('Rewritten version of the text');

        $response = $this->actingAs($this->user)
            ->postJson("/novels/{$this->novel->id}/chapters/{$this->chapter->id}/rewrite", [
                'selection' => 'Original text',
                'instruction' => 'Make it more dramatic',
            ]);

        $response->assertOk();
        $response->assertJson(['rewritten' => 'Rewritten version of the text']);
    }

    public function test_rewrite_includes_lore_context(): void
    {
        Character::factory()->create([
            'novel_id' => $this->novel->id,
            'name' => 'Villain',
            'role' => 'Antagonist',
        ]);

        \App\Facades\LocalAI::shouldReceive('generate')
            ->once()
            ->withArgs(function ($prompt) {
                return str_contains($prompt, 'Villain');
            })
            ->andReturn('Rewritten text');

        $response = $this->actingAs($this->user)
            ->postJson("/novels/{$this->novel->id}/chapters/{$this->chapter->id}/rewrite", [
                'selection' => 'Text to rewrite',
                'instruction' => 'Add more tension',
            ]);

        $response->assertOk();
    }

    public function test_expand_requires_selection(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/novels/{$this->novel->id}/chapters/{$this->chapter->id}/expand", []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('selection');
    }

    public function test_expand_returns_expanded_text(): void
    {
        \App\Facades\LocalAI::shouldReceive('generate')
            ->once()
            ->andReturn('Fully expanded scene with dialogue and details');

        $response = $this->actingAs($this->user)
            ->postJson("/novels/{$this->novel->id}/chapters/{$this->chapter->id}/expand", [
                'selection' => 'Short summary',
            ]);

        $response->assertOk();
        $response->assertJson(['expanded' => 'Fully expanded scene with dialogue and details']);
    }

    public function test_expand_includes_lore_context(): void
    {
        Location::factory()->create([
            'novel_id' => $this->novel->id,
            'name' => 'Tavern',
            'description' => 'Busy tavern',
        ]);

        \App\Facades\LocalAI::shouldReceive('generate')
            ->once()
            ->withArgs(function ($prompt) {
                return str_contains($prompt, 'Tavern');
            })
            ->andReturn('Expanded scene');

        $response = $this->actingAs($this->user)
            ->postJson("/novels/{$this->novel->id}/chapters/{$this->chapter->id}/expand", [
                'selection' => 'They met at a tavern',
            ]);

        $response->assertOk();
    }

    public function test_guest_cannot_access_chapter_endpoints(): void
    {
        $this->postJson("/novels/{$this->novel->id}/chapters/{$this->chapter->id}/generate", [
            'prompt' => 'Test',
        ])->assertUnauthorized();

        $this->postJson("/novels/{$this->novel->id}/chapters/{$this->chapter->id}/analyze", [
            'selection' => 'Test',
        ])->assertUnauthorized();

        $this->postJson("/novels/{$this->novel->id}/chapters/{$this->chapter->id}/suggest", [
            'context' => 'Test',
        ])->assertUnauthorized();
    }
}
