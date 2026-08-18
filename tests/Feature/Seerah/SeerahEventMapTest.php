<?php

declare(strict_types=1);

namespace Tests\Feature\Seerah;

use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Shared\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The map coordinate precedence.
 *
 * Before this feature the event page gated its OpenStreetMap embed on the
 * event's OWN coordinates, and only 2 of 578 events had any — so 576 pages
 * rendered the map region blank. The fallback to the resolved place is what
 * fixes that, and the precedence has to stay in this order: an event may happen
 * at a specific spot inside a place, and that spot must win.
 */
class SeerahEventMapTest extends TestCase
{
    use RefreshDatabase;

    private function event(array $attributes = []): SeerahEvent
    {
        return SeerahEvent::factory()->create($attributes);
    }

    public function test_the_events_own_coordinates_win_over_the_places(): void
    {
        $place = Location::factory()->create([
            'latitude' => '21.3891000',
            'longitude' => '39.8579000',
        ]);

        $event = $this->event([
            'location_id' => $place->id,
            'latitude' => '24.4672000',
            'longitude' => '39.6111000',
        ])->load('place');

        $coordinates = $event->resolvedCoordinates();

        $this->assertSame(24.4672, $coordinates['latitude']);
        $this->assertSame(39.6111, $coordinates['longitude']);
    }

    public function test_the_places_coordinates_are_used_when_the_event_has_none(): void
    {
        $place = Location::factory()->create([
            'latitude' => '21.3891000',
            'longitude' => '39.8579000',
        ]);

        $event = $this->event([
            'location_id' => $place->id,
            'latitude' => null,
            'longitude' => null,
        ])->load('place');

        $coordinates = $event->resolvedCoordinates();

        $this->assertSame(21.3891, $coordinates['latitude']);
        $this->assertSame(39.8579, $coordinates['longitude']);
        $this->assertTrue($event->hasResolvableCoordinates());
    }

    public function test_no_coordinates_anywhere_yields_no_map_and_no_error(): void
    {
        $place = Location::factory()->withoutCoordinates()->create();

        $event = $this->event([
            'location_id' => $place->id,
            'latitude' => null,
            'longitude' => null,
        ])->load('place');

        $this->assertNull($event->resolvedCoordinates());
        $this->assertFalse($event->hasResolvableCoordinates());
    }

    public function test_an_event_with_no_place_at_all_behaves_as_before(): void
    {
        $event = $this->event([
            'location_id' => null,
            'latitude' => null,
            'longitude' => null,
        ])->load('place');

        $this->assertNull($event->resolvedCoordinates());
        $this->assertFalse($event->hasResolvableCoordinates());
    }

    public function test_coordinates_are_not_taken_from_an_unloaded_relation(): void
    {
        // Guards against a silent N+1: resolvedCoordinates() must not lazy-load.
        // If the relation was not eager-loaded, the event simply has no map —
        // it must never issue a query per row on a list page.
        $place = Location::factory()->create(['latitude' => '21.0', 'longitude' => '39.0']);
        $event = $this->event(['location_id' => $place->id, 'latitude' => null, 'longitude' => null]);

        $this->assertFalse($event->relationLoaded('place'));
        $this->assertNull($event->resolvedCoordinates());
    }
}
