<?php

declare(strict_types=1);

namespace Tests\Feature\Seerah;

use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Shared\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The load-bearing invariant of this whole feature: NOTHING IS REPLACED.
 *
 * `seerah_events.location` holds editorial prose that no place record can
 * reconstruct — "حمراء الأسد على نحو 8 أميال جنوب المدينة", "المنطقة المحايدة بين
 * الجيشين". Assigning a canonical place must leave every character of it intact.
 */
class SeerahEventLocationPreservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigning_a_place_does_not_touch_the_descriptive_note(): void
    {
        $prose = 'حمراء الأسد على نحو 8 أميال جنوب المدينة';
        $place = Location::factory()->create();

        $event = SeerahEvent::factory()->create(['location' => $prose, 'location_id' => null]);

        $event->location_id = $place->id;
        $event->save();

        $this->assertSame($prose, $event->fresh()->location);
        $this->assertDatabaseHas('seerah_events', ['id' => $event->id, 'location' => $prose]);
    }

    public function test_prose_that_names_no_place_is_kept_with_a_null_key(): void
    {
        $prose = 'المنطقة المحايدة بين الجيشين';

        $event = SeerahEvent::factory()->create(['location' => $prose, 'location_id' => null]);

        $this->assertSame($prose, $event->fresh()->location);
        $this->assertNull($event->fresh()->location_id);
    }

    public function test_the_note_column_still_exists_and_is_still_a_plain_string(): void
    {
        // A column type change would break the exporter, importer and search,
        // all of which read it as text.
        $this->assertTrue(DB::getSchemaBuilder()->hasColumn('seerah_events', 'location'));

        $event = SeerahEvent::factory()->create(['location' => 'مكة المكرمة — المسجد الحرام']);

        $this->assertIsString($event->fresh()->location);
    }

    public function test_the_note_is_still_searchable_by_a_plain_like(): void
    {
        // SearchModule::SEERAH still lists `location` in searchableFields();
        // this asserts the column continues to answer that query shape.
        SeerahEvent::factory()->create(['location' => 'حصون بني قريظة جنوب المدينة']);

        $found = DB::table('seerah_events')->where('location', 'LIKE', '%قريظة%')->count();

        $this->assertSame(1, $found);
    }

    public function test_deleting_a_place_leaves_the_event_and_its_note_intact(): void
    {
        $prose = 'مكة المكرمة — الرمضاء والبطحاء';
        $place = Location::factory()->create();
        $event = SeerahEvent::factory()->create(['location' => $prose, 'location_id' => $place->id]);

        $place->delete();

        $fresh = $event->fresh();

        $this->assertNotNull($fresh, 'the event must survive its place being deleted');
        $this->assertSame($prose, $fresh->location);
        $this->assertNull($fresh->location_id);
    }
}
