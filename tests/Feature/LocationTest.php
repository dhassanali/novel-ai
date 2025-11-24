<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Novel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationTest extends TestCase
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

    public function test_can_create_location(): void
    {
        $response = $this->actingAs($this->user)
            ->post("/novels/{$this->novel->id}/locations", [
                'name' => 'Castle',
                'description' => 'Stone fortress on a hill',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('locations', [
            'novel_id' => $this->novel->id,
            'name' => 'Castle',
            'description' => 'Stone fortress on a hill',
        ]);
    }

    public function test_location_name_is_required(): void
    {
        $response = $this->actingAs($this->user)
            ->post("/novels/{$this->novel->id}/locations", [
                'description' => 'Some description',
            ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_can_update_location(): void
    {
        $location = Location::factory()->create([
            'novel_id' => $this->novel->id,
            'name' => 'Old Location',
        ]);

        $response = $this->actingAs($this->user)
            ->put("/novels/{$this->novel->id}/locations/{$location->id}", [
                'name' => 'New Location',
                'description' => 'Updated description',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('locations', [
            'id' => $location->id,
            'name' => 'New Location',
            'description' => 'Updated description',
        ]);
    }

    public function test_can_delete_location(): void
    {
        $location = Location::factory()->create([
            'novel_id' => $this->novel->id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete("/novels/{$this->novel->id}/locations/{$location->id}");

        $response->assertRedirect();

        $this->assertDatabaseMissing('locations', [
            'id' => $location->id,
        ]);
    }

    public function test_location_belongs_to_novel(): void
    {
        $location = Location::factory()->create([
            'novel_id' => $this->novel->id,
        ]);

        $this->assertEquals($this->novel->id, $location->novel->id);
    }

    public function test_novel_has_many_locations(): void
    {
        Location::factory()->count(3)->create([
            'novel_id' => $this->novel->id,
        ]);

        $this->assertCount(3, $this->novel->locations);
    }

    public function test_deleting_novel_deletes_locations(): void
    {
        $location = Location::factory()->create([
            'novel_id' => $this->novel->id,
        ]);

        $this->novel->delete();

        $this->assertDatabaseMissing('locations', [
            'id' => $location->id,
        ]);
    }

    public function test_guest_cannot_create_location(): void
    {
        $response = $this->post("/novels/{$this->novel->id}/locations", [
            'name' => 'Castle',
        ]);

        $response->assertRedirect('/login');
    }

    public function test_description_is_optional(): void
    {
        $response = $this->actingAs($this->user)
            ->post("/novels/{$this->novel->id}/locations", [
                'name' => 'Castle',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('locations', [
            'name' => 'Castle',
            'description' => null,
        ]);
    }
}
