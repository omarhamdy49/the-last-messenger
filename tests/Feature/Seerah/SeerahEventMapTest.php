<?php

declare(strict_types=1);

namespace Tests\Feature\Seerah;

use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Shared\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Map coordinates come only from the linked place — events carry no
 * coordinates of their own.
 */
class SeerahEventMapTest extends TestCase
{
    use RefreshDatabase;

    private function event(array $attributes = []): SeerahEvent
    {
        return SeerahEvent::factory()->create($attributes);
    }

    public function test_the_places_coordinates_are_used(): void
    {
        $place = Location::factory()->create([
            'latitude' => '21.3891000',
            'longitude' => '39.8579000',
        ]);

        $event = $this->event(['location_id' => $place->id])->load('place');

        $coordinates = $event->resolvedCoordinates();

        $this->assertSame(21.3891, $coordinates['latitude']);
        $this->assertSame(39.8579, $coordinates['longitude']);
        $this->assertTrue($event->hasResolvableCoordinates());
    }

    public function test_a_place_without_coordinates_yields_no_map(): void
    {
        $place = Location::factory()->withoutCoordinates()->create();

        $event = $this->event(['location_id' => $place->id])->load('place');

        $this->assertNull($event->resolvedCoordinates());
        $this->assertFalse($event->hasResolvableCoordinates());
    }

    public function test_an_event_with_no_place_yields_no_map_and_no_error(): void
    {
        $event = $this->event(['location_id' => null])->load('place');

        $this->assertNull($event->resolvedCoordinates());
        $this->assertFalse($event->hasResolvableCoordinates());
    }

    public function test_coordinates_are_not_taken_from_an_unloaded_relation(): void
    {
        // Guards against a silent N+1: resolvedCoordinates() must not lazy-load.
        // If the relation was not eager-loaded, the event simply has no map —
        // it must never issue a query per row on a list page.
        $place = Location::factory()->create(['latitude' => '21.0', 'longitude' => '39.0']);
        $event = $this->event(['location_id' => $place->id]);

        $this->assertFalse($event->relationLoaded('place'));
        $this->assertNull($event->resolvedCoordinates());
    }
}
