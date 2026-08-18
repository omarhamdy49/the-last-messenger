<?php

declare(strict_types=1);

namespace Tests\Feature\Locations;

use App\Domain\Shared\Models\Location;
use App\Domain\Shared\Models\LocationTranslation;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The MySQL-specific behaviour this schema relies on, asserted rather than
 * assumed (constitution VI): a self-referencing FK that nulls rather than
 * cascades, a cascading translation FK, JSON round-tripping Arabic unchanged,
 * and the per-locale uniqueness that stops a place having two Arabic names.
 */
class LocationSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_a_parent_nulls_the_child_rather_than_deleting_it(): void
    {
        $parent = Location::factory()->create();
        $child = Location::factory()->create(['parent_id' => $parent->id]);

        $parent->delete();

        $this->assertDatabaseHas('locations', ['id' => $child->id, 'parent_id' => null]);
    }

    public function test_deleting_a_location_cascades_its_translations(): void
    {
        $location = Location::factory()->create();
        $location->syncTranslations(['name' => ['ar' => 'مكة المكرمة', 'en' => 'Makkah']]);

        $this->assertSame(2, LocationTranslation::query()->where('location_id', $location->id)->count());

        $location->delete();

        $this->assertSame(0, LocationTranslation::query()->where('location_id', $location->id)->count());
    }

    public function test_a_place_cannot_have_two_translations_in_the_same_locale(): void
    {
        $location = Location::factory()->create();

        LocationTranslation::create([
            'location_id' => $location->id,
            'locale' => 'ar',
            'name' => 'المدينة المنورة',
        ]);

        $this->expectException(QueryException::class);

        LocationTranslation::create([
            'location_id' => $location->id,
            'locale' => 'ar',
            'name' => 'طيبة',
        ]);
    }

    public function test_aliases_round_trip_arabic_with_diacritics_unchanged(): void
    {
        $aliases = ['شِعب أبي طالب', 'أُحُد', 'بُصرى الشام', 'Medinah'];

        $location = Location::factory()->withAliases($aliases)->create();

        $this->assertSame($aliases, $location->fresh()->aliases);
        $this->assertSame($aliases, $location->fresh()->aliasList());
    }

    public function test_slug_is_unique(): void
    {
        Location::factory()->create(['slug' => 'makkah']);

        $this->expectException(QueryException::class);

        Location::factory()->create(['slug' => 'makkah']);
    }

    public function test_coordinate_precision_matches_seerah_events(): void
    {
        // Both columns are decimal(10,7). A coordinate must survive the round
        // trip between them without rounding, because the map gate reads whichever
        // of the two is present.
        $location = Location::factory()->create([
            'latitude' => '21.4224779',
            'longitude' => '39.8251832',
        ]);

        $this->assertSame('21.4224779', (string) $location->fresh()->latitude);
        $this->assertSame('39.8251832', (string) $location->fresh()->longitude);
    }

    public function test_the_three_consumer_tables_carry_a_nullable_location_key(): void
    {
        foreach ([
            'seerah_events' => 'location_id',
            'muslims' => 'birth_place_id',
            'quran_surahs' => 'revelation_location_id',
        ] as $table => $column) {
            $this->assertTrue(
                DB::getSchemaBuilder()->hasColumn($table, $column),
                "{$table}.{$column} is missing"
            );
        }
    }

    public function test_no_existing_place_column_was_dropped(): void
    {
        // The load-bearing invariant of this feature: nothing is replaced.
        $this->assertTrue(DB::getSchemaBuilder()->hasColumn('seerah_events', 'location'));
        $this->assertTrue(DB::getSchemaBuilder()->hasColumn('muslims', 'birth_place'));
        $this->assertTrue(DB::getSchemaBuilder()->hasColumn('quran_surahs', 'revelation_place'));
    }
}
