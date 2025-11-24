<?php

namespace Tests\Unit;

use App\Http\Controllers\ChapterController;
use App\Models\Character;
use App\Models\Chapter;
use App\Models\Location;
use App\Models\Novel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoreContextTest extends TestCase
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
            'description' => 'A test description',
        ]);
        $this->chapter = Chapter::factory()->create([
            'novel_id' => $this->novel->id,
        ]);
    }

    public function test_lore_context_includes_characters(): void
    {
        // Create test characters
        Character::factory()->create([
            'novel_id' => $this->novel->id,
            'name' => 'Hero',
            'role' => 'Protagonist',
            'description' => 'Brave warrior',
        ]);

        Character::factory()->create([
            'novel_id' => $this->novel->id,
            'name' => 'Villain',
            'role' => 'Antagonist',
            'description' => 'Evil sorcerer',
        ]);

        // Use reflection to access private method
        $controller = new ChapterController();
        $reflector = new \ReflectionClass($controller);
        $method = $reflector->getMethod('getLoreContext');
        $method->setAccessible(true);

        $context = $method->invoke($controller, $this->novel);

        // Assert context includes character information
        $this->assertStringContainsString('Characters:', $context);
        $this->assertStringContainsString('Hero (Protagonist): Brave warrior', $context);
        $this->assertStringContainsString('Villain (Antagonist): Evil sorcerer', $context);
    }

    public function test_lore_context_includes_locations(): void
    {
        // Create test locations
        Location::factory()->create([
            'novel_id' => $this->novel->id,
            'name' => 'Castle',
            'description' => 'Stone fortress',
        ]);

        Location::factory()->create([
            'novel_id' => $this->novel->id,
            'name' => 'Forest',
            'description' => 'Dark woods',
        ]);

        // Use reflection to access private method
        $controller = new ChapterController();
        $reflector = new \ReflectionClass($controller);
        $method = $reflector->getMethod('getLoreContext');
        $method->setAccessible(true);

        $context = $method->invoke($controller, $this->novel);

        // Assert context includes location information
        $this->assertStringContainsString('Locations:', $context);
        $this->assertStringContainsString('Castle: Stone fortress', $context);
        $this->assertStringContainsString('Forest: Dark woods', $context);
    }

    public function test_lore_context_with_both_characters_and_locations(): void
    {
        Character::factory()->create([
            'novel_id' => $this->novel->id,
            'name' => 'Hero',
            'role' => 'Protagonist',
            'description' => 'Brave warrior',
        ]);

        Location::factory()->create([
            'novel_id' => $this->novel->id,
            'name' => 'Castle',
            'description' => 'Stone fortress',
        ]);

        $controller = new ChapterController();
        $reflector = new \ReflectionClass($controller);
        $method = $reflector->getMethod('getLoreContext');
        $method->setAccessible(true);

        $context = $method->invoke($controller, $this->novel);

        // Assert both sections are present
        $this->assertStringContainsString('Characters:', $context);
        $this->assertStringContainsString('Locations:', $context);
        $this->assertStringContainsString('Hero', $context);
        $this->assertStringContainsString('Castle', $context);
    }

    public function test_lore_context_empty_when_no_data(): void
    {
        $controller = new ChapterController();
        $reflector = new \ReflectionClass($controller);
        $method = $reflector->getMethod('getLoreContext');
        $method->setAccessible(true);

        $context = $method->invoke($controller, $this->novel);

        // Context should be empty string
        $this->assertEquals('', $context);
    }

    public function test_lore_context_handles_null_descriptions(): void
    {
        Character::factory()->create([
            'novel_id' => $this->novel->id,
            'name' => 'Hero',
            'role' => 'Protagonist',
            'description' => null,
        ]);

        $controller = new ChapterController();
        $reflector = new \ReflectionClass($controller);
        $method = $reflector->getMethod('getLoreContext');
        $method->setAccessible(true);

        $context = $method->invoke($controller, $this->novel);

        // Should still include character but with empty description
        $this->assertStringContainsString('Hero (Protagonist):', $context);
    }
}
