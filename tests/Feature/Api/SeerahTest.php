<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Domain\Seerah\Models\SeerahEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SeerahTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create());
    }

    /**
     * Create a SeerahEvent with a featured_image URL to prevent Spatie media
     * library fallback queries that are incompatible with the SQLite test driver.
     */
    private function createSeerahEvent(array $overrides = []): SeerahEvent
    {
        $defaults = [
            'slug' => 'birth-of-the-prophet',
            'seerah_event_type_id' => \App\Domain\Seerah\Models\SeerahEventType::query()->where('slug', 'birth')->value('id'),
            'status' => 'published',
            'importance' => 'high',
            'hijri_year' => -53,
            'is_featured' => false,
            'display_order' => 0,
            'featured_image' => 'https://example.com/image.jpg', // Prevents Spatie fallback query
            'title' => ['ar' => 'مولد النبي', 'en' => 'Birth of the Prophet'],
            'description' => ['ar' => 'وصف الحدث', 'en' => 'Event description'],
        ];

        return SeerahEvent::query()->create(array_merge($defaults, $overrides));
    }

    public function test_seerah_index_returns_list(): void
    {
        $this->createSeerahEvent();

        $response = $this->getJson('/api/v1/seerah');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data',
            ])
            ->assertJsonPath('success', true);
    }

    public function test_seerah_index_is_available_to_authenticated_clients(): void
    {
        $response = $this->getJson('/api/v1/seerah');

        $response->assertOk();
    }

    public function test_seerah_show_returns_single_event_by_slug(): void
    {
        $this->createSeerahEvent(['slug' => 'hijra-to-madinah']);

        $response = $this->getJson('/api/v1/seerah/hijra-to-madinah');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data',
            ])
            ->assertJsonPath('success', true);
    }

    public function test_seerah_show_returns_404_for_nonexistent_slug(): void
    {
        $response = $this->getJson('/api/v1/seerah/this-event-does-not-exist');

        $response->assertNotFound()
            ->assertJsonPath('success', false);
    }

    public function test_seerah_featured_returns_featured_events(): void
    {
        $this->createSeerahEvent(['slug' => 'featured-event', 'is_featured' => true]);
        $this->createSeerahEvent(['slug' => 'non-featured-event', 'is_featured' => false]);

        $response = $this->getJson('/api/v1/seerah/featured');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data',
            ])
            ->assertJsonPath('success', true);
    }

    public function test_seerah_response_structure_is_consistent(): void
    {
        $response = $this->getJson('/api/v1/seerah');

        $response->assertJsonStructure(['success', 'data']);
    }
}
