<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Shared\Enums\LocationType;
use App\Domain\Shared\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The three write rules the command must never break:
 *   1. writes only into a NULL key, so it is re-runnable forever;
 *   2. never overwrites what an editor assigned by hand;
 *   3. leaves ambiguous text alone rather than guessing.
 */
class BackfillLocationsCommandTest extends TestCase
{
    use RefreshDatabase;

    private Location $makkah;

    private Location $madinah;

    protected function setUp(): void
    {
        parent::setUp();

        $hijaz = Location::factory()
            ->withAliases(['الحجاز', 'Hijaz'])
            ->create(['slug' => 'hijaz', 'type' => LocationType::REGION->value]);

        $this->makkah = Location::factory()
            ->withAliases(['مكة المكرمة', 'مكة', 'بمكة', 'Makkah'])
            ->create(['slug' => 'makkah', 'type' => LocationType::CITY->value, 'parent_id' => $hijaz->id]);

        $this->madinah = Location::factory()
            ->withAliases(['المدينة المنورة', 'المدينة', 'بالمدينة', 'Medinah', 'Medina', 'Madinah'])
            ->create(['slug' => 'al-madinah', 'type' => LocationType::CITY->value, 'parent_id' => $hijaz->id]);
    }

    public function test_it_fails_loudly_when_the_vocabulary_is_missing(): void
    {
        DB::table('locations')->update(['is_active' => false]);

        $this->artisan('locations:backfill')->assertExitCode(1);
    }

    public function test_dry_run_writes_nothing(): void
    {
        $event = SeerahEvent::factory()->create(['location' => 'مكة المكرمة', 'location_id' => null]);

        $this->artisan('locations:backfill', ['--dry-run' => true])->assertExitCode(0);

        $this->assertNull($event->fresh()->location_id);
    }

    public function test_it_resolves_and_is_idempotent(): void
    {
        $event = SeerahEvent::factory()->create(['location' => 'حصون بني قريظة جنوب المدينة', 'location_id' => null]);

        $this->artisan('locations:backfill')->assertExitCode(0);
        $this->assertSame($this->madinah->id, $event->fresh()->location_id);

        // A second run must change nothing at all.
        $before = DB::table('seerah_events')->orderBy('id')->pluck('location_id', 'id')->toArray();
        $this->artisan('locations:backfill')->assertExitCode(0);
        $after = DB::table('seerah_events')->orderBy('id')->pluck('location_id', 'id')->toArray();

        $this->assertSame($before, $after);
    }

    public function test_a_manual_assignment_is_never_overwritten(): void
    {
        // The text says Makkah, but an editor decided otherwise. The editor wins.
        $event = SeerahEvent::factory()->create([
            'location' => 'مكة المكرمة',
            'location_id' => $this->madinah->id,
        ]);

        $this->artisan('locations:backfill')->assertExitCode(0);

        $this->assertSame($this->madinah->id, $event->fresh()->location_id);
    }

    public function test_ambiguous_text_is_left_null_rather_than_guessed(): void
    {
        $event = SeerahEvent::factory()->create(['location' => 'من مكة إلى المدينة', 'location_id' => null]);

        $this->artisan('locations:backfill')->assertExitCode(0);

        $this->assertNull($event->fresh()->location_id);
        $this->assertSame('من مكة إلى المدينة', $event->fresh()->location);
    }

    public function test_text_naming_no_known_place_is_left_null(): void
    {
        $event = SeerahEvent::factory()->create(['location' => 'المنطقة المحايدة بين الجيشين', 'location_id' => null]);

        $this->artisan('locations:backfill')->assertExitCode(0);

        $this->assertNull($event->fresh()->location_id);
    }

    public function test_an_event_with_no_place_text_is_not_examined(): void
    {
        $event = SeerahEvent::factory()->create(['location' => null, 'location_id' => null]);

        $this->artisan('locations:backfill')->assertExitCode(0);

        $this->assertNull($event->fresh()->location_id);
    }

    public function test_the_three_madinah_spellings_collapse_to_one_place(): void
    {
        foreach (['Medinah', 'Medina', 'Madinah'] as $spelling) {
            DB::table('muslims')->insert([
                'slug' => 'test-'.strtolower($spelling),
                'name_ar' => 'اختبار',
                'birth_place' => json_encode(['en' => $spelling], JSON_UNESCAPED_UNICODE),
            ]);
        }

        $this->artisan('locations:backfill')->assertExitCode(0);

        $ids = DB::table('muslims')->where('slug', 'LIKE', 'test-%')->pluck('birth_place_id')->unique();

        $this->assertCount(1, $ids, 'all three spellings must resolve to the same place');
        $this->assertSame($this->madinah->id, $ids->first());
    }

    public function test_surah_classification_is_untouched_by_the_backfill(): void
    {
        DB::table('quran_surahs')->insert([
            'id' => 1, 'revelation_place' => 'makkah', 'revelation_order' => 5,
            'name_ar' => 'الفاتحة', 'name_en' => 'Al-Fatihah', 'verses_count' => 7,
        ]);

        $this->artisan('locations:backfill')->assertExitCode(0);

        $surah = DB::table('quran_surahs')->find(1);

        $this->assertSame('makkah', $surah->revelation_place, 'the مكية/مدنية classification must never be rewritten');
        $this->assertSame($this->makkah->id, $surah->revelation_location_id);
    }
}
