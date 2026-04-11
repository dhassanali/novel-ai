<?php

namespace Tests\Feature;

use App\Models\Character;
use App\Models\Novel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CharacterProfileTest extends TestCase
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

    public function test_can_create_character_with_profile_fields(): void
    {
        $response = $this->actingAs($this->user)
            ->post("/novels/{$this->novel->id}/characters", [
                'name' => 'Elena',
                'role' => 'Protagonist',
                'description' => 'A brave journalist.',
                'personality_traits' => 'Bold, sarcastic, deeply curious',
                'backstory' => 'Grew up in poverty, earned her way through journalism school.',
                'goals' => 'Expose government corruption',
                'speech_patterns' => 'Short sentences, asks rhetorical questions',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('characters', [
            'novel_id' => $this->novel->id,
            'name' => 'Elena',
            'personality_traits' => 'Bold, sarcastic, deeply curious',
            'backstory' => 'Grew up in poverty, earned her way through journalism school.',
            'goals' => 'Expose government corruption',
            'speech_patterns' => 'Short sentences, asks rhetorical questions',
        ]);
    }

    public function test_profile_fields_are_optional(): void
    {
        $response = $this->actingAs($this->user)
            ->post("/novels/{$this->novel->id}/characters", [
                'name' => 'Nameless',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('characters', [
            'name' => 'Nameless',
            'personality_traits' => null,
            'backstory' => null,
            'goals' => null,
            'speech_patterns' => null,
        ]);
    }

    public function test_can_update_character_profile_fields(): void
    {
        $character = Character::factory()->create([
            'novel_id' => $this->novel->id,
            'name' => 'Marcus',
        ]);

        $response = $this->actingAs($this->user)
            ->put("/novels/{$this->novel->id}/characters/{$character->id}", [
                'name' => 'Marcus',
                'role' => 'Antagonist',
                'description' => 'A ruthless villain.',
                'personality_traits' => 'Cold, calculating, charming',
                'backstory' => 'Former soldier disillusioned by war.',
                'goals' => 'Seize political power',
                'speech_patterns' => 'Speaks slowly, never raises his voice',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('characters', [
            'id' => $character->id,
            'personality_traits' => 'Cold, calculating, charming',
            'backstory' => 'Former soldier disillusioned by war.',
            'goals' => 'Seize political power',
            'speech_patterns' => 'Speaks slowly, never raises his voice',
        ]);
    }

    public function test_other_user_cannot_update_character_profile(): void
    {
        $other = User::factory()->create();
        $character = Character::factory()->create([
            'novel_id' => $this->novel->id,
            'name' => 'Elena',
        ]);

        $response = $this->actingAs($other)
            ->put("/novels/{$this->novel->id}/characters/{$character->id}", [
                'name' => 'Elena',
                'personality_traits' => 'Malicious update',
            ]);

        $response->assertForbidden();
    }
}
