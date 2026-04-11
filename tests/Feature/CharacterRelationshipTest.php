<?php

namespace Tests\Feature;

use App\Models\Character;
use App\Models\CharacterRelationship;
use App\Models\Novel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CharacterRelationshipTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Novel $novel;

    protected Character $elena;

    protected Character $marcus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->novel = Novel::factory()->create(['user_id' => $this->user->id]);
        $this->elena = Character::factory()->create(['novel_id' => $this->novel->id, 'name' => 'Elena']);
        $this->marcus = Character::factory()->create(['novel_id' => $this->novel->id, 'name' => 'Marcus']);
    }

    public function test_can_add_relationship_between_characters(): void
    {
        $response = $this->actingAs($this->user)
            ->post("/novels/{$this->novel->id}/characters/{$this->elena->id}/relationships", [
                'related_character_id' => $this->marcus->id,
                'type' => 'rival',
                'description' => 'Competing journalists',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('character_relationships', [
            'character_id' => $this->elena->id,
            'related_character_id' => $this->marcus->id,
            'type' => 'rival',
            'description' => 'Competing journalists',
        ]);
    }

    public function test_relationship_type_must_be_valid(): void
    {
        $response = $this->actingAs($this->user)
            ->post("/novels/{$this->novel->id}/characters/{$this->elena->id}/relationships", [
                'related_character_id' => $this->marcus->id,
                'type' => 'frenemy',
            ]);

        $response->assertSessionHasErrors('type');
    }

    public function test_related_character_must_exist(): void
    {
        $response = $this->actingAs($this->user)
            ->post("/novels/{$this->novel->id}/characters/{$this->elena->id}/relationships", [
                'related_character_id' => 99999,
                'type' => 'friend',
            ]);

        $response->assertSessionHasErrors('related_character_id');
    }

    public function test_relationship_description_is_optional(): void
    {
        $response = $this->actingAs($this->user)
            ->post("/novels/{$this->novel->id}/characters/{$this->elena->id}/relationships", [
                'related_character_id' => $this->marcus->id,
                'type' => 'friend',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('character_relationships', [
            'character_id' => $this->elena->id,
            'related_character_id' => $this->marcus->id,
            'description' => null,
        ]);
    }

    public function test_can_delete_relationship(): void
    {
        $relationship = CharacterRelationship::create([
            'novel_id' => $this->novel->id,
            'character_id' => $this->elena->id,
            'related_character_id' => $this->marcus->id,
            'type' => 'rival',
        ]);

        $response = $this->actingAs($this->user)
            ->delete("/novels/{$this->novel->id}/characters/{$this->elena->id}/relationships/{$relationship->id}");

        $response->assertRedirect();

        $this->assertDatabaseMissing('character_relationships', ['id' => $relationship->id]);
    }

    public function test_updating_relationship_replaces_existing(): void
    {
        $this->actingAs($this->user)
            ->post("/novels/{$this->novel->id}/characters/{$this->elena->id}/relationships", [
                'related_character_id' => $this->marcus->id,
                'type' => 'rival',
                'description' => 'Initial description',
            ]);

        // Update with same related_character_id — should upsert
        $this->actingAs($this->user)
            ->post("/novels/{$this->novel->id}/characters/{$this->elena->id}/relationships", [
                'related_character_id' => $this->marcus->id,
                'type' => 'enemy',
                'description' => 'Now full enemies',
            ]);

        $this->assertDatabaseCount('character_relationships', 1);
        $this->assertDatabaseHas('character_relationships', [
            'character_id' => $this->elena->id,
            'type' => 'enemy',
            'description' => 'Now full enemies',
        ]);
    }

    public function test_other_user_cannot_add_relationship(): void
    {
        $other = User::factory()->create();

        $response = $this->actingAs($other)
            ->post("/novels/{$this->novel->id}/characters/{$this->elena->id}/relationships", [
                'related_character_id' => $this->marcus->id,
                'type' => 'friend',
            ]);

        $response->assertForbidden();
    }

    public function test_character_relationships_cascade_deleted_with_character(): void
    {
        CharacterRelationship::create([
            'novel_id' => $this->novel->id,
            'character_id' => $this->elena->id,
            'related_character_id' => $this->marcus->id,
            'type' => 'rival',
        ]);

        $this->elena->delete();

        $this->assertDatabaseEmpty('character_relationships');
    }
}
