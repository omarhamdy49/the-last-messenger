<?php

declare(strict_types=1);

namespace Tests\Feature\Locations;

use App\Domain\Shared\Models\Location;
use App\Domain\Shared\Services\LocationUsageRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The registry is what lets Shared stay a leaf: it counts references by table
 * name, so the Location model never has to name a domain model.
 */
class LocationUsageRegistryTest extends TestCase
{
    use RefreshDatabase;

    private function registry(): LocationUsageRegistry
    {
        return app(LocationUsageRegistry::class);
    }

    public function test_the_three_consumer_tables_are_registered(): void
    {
        $tables = array_column($this->registry()->all(), 'table');

        $this->assertContains('seerah_events', $tables);
        $this->assertContains('muslims', $tables);
        $this->assertContains('quran_surahs', $tables);
    }

    public function test_an_unreferenced_location_reports_no_references(): void
    {
        $location = Location::factory()->create();

        $this->assertSame([], $this->registry()->countReferences($location->id));
        $this->assertFalse($this->registry()->isReferenced($location->id));
    }

    public function test_references_are_counted_per_table(): void
    {
        $location = Location::factory()->create();

        // RefreshDatabase leaves the consumer tables empty, so the fixtures are
        // created here rather than assumed from a seeder.
        foreach ([1, 2, 3] as $n) {
            DB::table('quran_surahs')->insert([
                'id' => $n,
                'revelation_place' => 'makkah',
                'revelation_location_id' => $location->id,
                'revelation_order' => $n,
                'name_ar' => "سورة {$n}",
                'name_en' => "Surah {$n}",
                'verses_count' => 3,
            ]);
        }

        $counts = $this->registry()->countReferences($location->id);

        $this->assertSame(3, $counts['quran_surahs'] ?? 0);
        $this->assertArrayNotHasKey('muslims', $counts);
        $this->assertTrue($this->registry()->isReferenced($location->id));
    }

    public function test_unresolved_counts_ignore_rows_with_no_place_text(): void
    {
        // A row with neither a place nor any text describing one is not part of
        // the editor's queue — it is simply a record that names no place.
        $counts = $this->registry()->countUnresolved([
            'seerah_events' => 'location',
            'muslims' => 'birth_place',
        ]);

        $this->assertArrayHasKey('seerah_events', $counts);
        $this->assertArrayHasKey('muslims', $counts);
        $this->assertArrayNotHasKey('quran_surahs', $counts);
    }
}
