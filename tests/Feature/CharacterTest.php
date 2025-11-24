<?php

namespace Tests\Feature;

use App\Models\Character;
use App\Models\Novel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CharacterTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Novel $novel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->novel = Novel::factory()->create([
            'user_id' => $this->user->id,
        ]);
    }

    public function test_can_create_character(): void
    {
        $response = $this->actingAs($this->user)
            ->post("/novels/{$this->novel->id}/characters", [
                'name' => 'Hero',
                'description' => 'Brave warrior with a troubled past',
                'role' => 'Protagonist',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('characters', [
            'novel_id' => $this->novel->id,
            'name' => 'Hero',
            'description' => 'Brave warrior with a troubled past',
            'role' => 'Protagonist',
        ]);
    }

    public function test_character_name_is_required(): void
    {
        $response = $this->actingAs($this->user)
            ->post("/novels/{$this->novel->id}/characters", [
                'description' => 'Some description',
                'role' => 'Protagonist',
            ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_can_update_character(): void
    {
        $character = Character::factory()->create([
            'novel_id' => $this->novel->id,
            'name' => 'Old Name',
        ]);

        $response = $this->actingAs($this->user)
            ->put("/novels/{$this->novel->id}/characters/{$character->id}", [
                'name' => 'New Name',
                'description' => 'Updated description',
                'role' => 'Antagonist',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('characters', [
            'id' => $character->id,
            'name' => 'New Name',
            'description' => 'Updated description',
            'role' => 'Antagonist',
        ]);
    }

    public function test_can_delete_character(): void
    {
        $character = Character::factory()->create([
            'novel_id' => $this->novel->id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete("/novels/{$this->novel->id}/characters/{$character->id}");

        $response->assertRedirect();

        $this->assertDatabaseMissing('characters', [
            'id' => $character->id,
        ]);
    }

    public function test_character_belongs_to_novel(): void
    {
        $character = Character::factory()->create([
            'novel_id' => $this->novel->id,
        ]);

        $this->assertEquals($this->novel->id, $character->novel->id);
    }

    public function test_novel_has_many_characters(): void
    {
        Character::factory()->count(3)->create([
            'novel_id' => $this->novel->id,
        ]);

        $this->assertCount(3, $this->novel->characters);
    }

    public function test_deleting_novel_deletes_characters(): void
    {
        $character = Character::factory()->create([
            'novel_id' => $this->novel->id,
        ]);

        $this->novel->delete();

        $this->assertDatabaseMissing('characters', [
            'id' => $character->id,
        ]);
    }

    public function test_guest_cannot_create_character(): void
    {
        $response = $this->post("/novels/{$this->novel->id}/characters", [
            'name' => 'Hero',
        ]);

        $response->assertRedirect('/login');
    }

    public function test_description_and_role_are_optional(): void
    {
        $response = $this->actingAs($this->user)
            ->post("/novels/{$this->novel->id}/characters", [
                'name' => 'Hero',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('characters', [
            'name' => 'Hero',
            'description' => null,
            'role' => null,
        ]);
    }
}
