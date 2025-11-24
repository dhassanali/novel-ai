<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Character;
use App\Models\Location;
use App\Models\Novel;
use App\Models\SourceDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NovelControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_index_displays_user_novels(): void
    {
        $novel1 = Novel::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'First Novel',
        ]);

        $novel2 = Novel::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Second Novel',
        ]);

        // Create a novel for another user
        $otherUser = User::factory()->create();
        Novel::factory()->create([
            'user_id' => $otherUser->id,
            'title' => 'Other User Novel',
        ]);

        $response = $this->actingAs($this->user)->get('/novels');

        $response->assertOk();
        $response->assertInertia(
            fn($page) => $page
                ->component('Novels/Index')
                ->has('novels', 2)
                ->where('novels.0.title', 'First Novel') // Creation order may vary
                ->where('novels.1.title', 'Second Novel')
        );
    }

    public function test_guest_cannot_access_index(): void
    {
        $response = $this->get('/novels');

        $response->assertRedirect('/login');
    }

    public function test_can_create_novel(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/novels', [
                'title' => 'My New Novel',
                'description' => 'An exciting story',
                'genre' => 'Science Fiction',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('novels', [
            'user_id' => $this->user->id,
            'title' => 'My New Novel',
            'description' => 'An exciting story',
            'genre' => 'Science Fiction',
        ]);
    }

    public function test_novel_title_is_required(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/novels', [
                'description' => 'A story',
            ]);

        $response->assertSessionHasErrors('title');
    }

    public function test_description_and_genre_are_optional(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/novels', [
                'title' => 'Minimal Novel',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('novels', [
            'title' => 'Minimal Novel',
            'description' => null,
            'genre' => null,
        ]);
    }

    public function test_novel_redirects_to_show_after_creation(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/novels', [
                'title' => 'Test Novel',
            ]);

        $novel = Novel::latest()->first();
        $response->assertRedirect("/novels/{$novel->id}");
    }

    public function test_show_displays_novel_with_relationships(): void
    {
        $novel = Novel::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Test Novel',
        ]);

        Chapter::factory()->create([
            'novel_id' => $novel->id,
            'title' => 'Chapter 1',
        ]);

        Character::factory()->create([
            'novel_id' => $novel->id,
            'name' => 'Hero',
        ]);

        Location::factory()->create([
            'novel_id' => $novel->id,
            'name' => 'Castle',
        ]);

        SourceDocument::create([
            'novel_id' => $novel->id,
            'filename' => 'doc.pdf',
            'path' => '/path/to/doc.pdf',
            'type' => 'document',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->user)
            ->get("/novels/{$novel->id}");

        $response->assertOk();
        $response->assertInertia(
            fn($page) => $page
                ->component('Novels/Show')
                ->where('novel.title', 'Test Novel')
                ->has('novel.chapters', 1)
                ->has('novel.characters', 1)
                ->has('novel.locations', 1)
                ->has('novel.source_documents', 1)
        );
    }

    public function test_user_cannot_view_another_users_novel(): void
    {
        $otherUser = User::factory()->create();
        $novel = Novel::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get("/novels/{$novel->id}");

        $response->assertForbidden();
    }

    public function test_can_update_novel(): void
    {
        $novel = Novel::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Original Title',
        ]);

        $response = $this->actingAs($this->user)
            ->put("/novels/{$novel->id}", [
                'title' => 'Updated Title',
                'description' => 'Updated description',
                'genre' => 'Fantasy',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('novels', [
            'id' => $novel->id,
            'title' => 'Updated Title',
            'description' => 'Updated description',
            'genre' => 'Fantasy',
        ]);
    }

    public function test_user_cannot_update_another_users_novel(): void
    {
        $otherUser = User::factory()->create();
        $novel = Novel::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->put("/novels/{$novel->id}", [
                'title' => 'Hacked Title',
            ]);

        $response->assertForbidden();

        $this->assertDatabaseMissing('novels', [
            'id' => $novel->id,
            'title' => 'Hacked Title',
        ]);
    }

    public function test_can_delete_novel(): void
    {
        $novel = Novel::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete("/novels/{$novel->id}");

        $response->assertRedirect('/novels');

        $this->assertDatabaseMissing('novels', [
            'id' => $novel->id,
        ]);
    }

    public function test_deleting_novel_cascades_to_chapters(): void
    {
        $novel = Novel::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $chapter = Chapter::factory()->create([
            'novel_id' => $novel->id,
        ]);

        $this->actingAs($this->user)->delete("/novels/{$novel->id}");

        $this->assertDatabaseMissing('chapters', [
            'id' => $chapter->id,
        ]);
    }

    public function test_deleting_novel_cascades_to_characters(): void
    {
        $novel = Novel::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $character = Character::factory()->create([
            'novel_id' => $novel->id,
        ]);

        $this->actingAs($this->user)->delete("/novels/{$novel->id}");

        $this->assertDatabaseMissing('characters', [
            'id' => $character->id,
        ]);
    }

    public function test_deleting_novel_cascades_to_locations(): void
    {
        $novel = Novel::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $location = Location::factory()->create([
            'novel_id' => $novel->id,
        ]);

        $this->actingAs($this->user)->delete("/novels/{$novel->id}");

        $this->assertDatabaseMissing('locations', [
            'id' => $location->id,
        ]);
    }

    public function test_user_cannot_delete_another_users_novel(): void
    {
        $otherUser = User::factory()->create();
        $novel = Novel::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete("/novels/{$novel->id}");

        $response->assertForbidden();

        $this->assertDatabaseHas('novels', [
            'id' => $novel->id,
        ]);
    }

    public function test_guest_cannot_create_novel(): void
    {
        $response = $this->post('/novels', [
            'title' => 'Guest Novel',
        ]);

        $response->assertRedirect('/login');
    }
}
