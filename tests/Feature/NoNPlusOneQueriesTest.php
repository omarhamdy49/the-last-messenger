<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Quran\Models\Surah;
use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Seerah\Models\SeerahEventType;
use App\Domain\Shared\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NoNPlusOneQueriesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create(), ['api:access']);
    }

    public function test_seerah_index_does_not_cause_n_plus_one(): void
    {
        // Every row gets its own type and place so a per-row lazy load would show.
        for ($i = 1; $i <= 5; $i++) {
            $type = SeerahEventType::query()->create(['slug' => "type-{$i}", 'locale' => ['ar' => "نوع {$i}"], 'is_active' => true]);
            $place = Location::factory()->withNames(['ar' => "مكان {$i}"])->create();
            SeerahEvent::factory()->create([
                'slug' => "event-{$i}",
                'event_type' => $type->id,
                'location_id' => $place->id,
                'order' => $i,
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->getJson('/api/v1/seerah')->assertOk();

        $queries = array_column(DB::getQueryLog(), 'query');
        DB::disableQueryLog();

        // Constant: 3 language lookups (request locale), count, page, then one batch
        // each for translations, type, place and place.translations.
        $this->assertLessThanOrEqual(
            9,
            count($queries),
            'Seerah index uses too many queries: '.count($queries).'. Check for N+1.'
        );
    }

    public function test_quran_surahs_does_not_cause_n_plus_one(): void
    {
        // Create a set of surahs to trigger potential N+1 scenarios
        for ($i = 1; $i <= 5; $i++) {
            Surah::query()->create([
                'revelation_place' => 'makkah',
                'revelation_order' => $i,
                'bismillah_pre' => true,
                'name_ar' => "سورة {$i}",
                'name_en' => "Surah {$i}",
                'transliteration' => "Surah-{$i}",
                'verses_count' => 7,
            ]);
        }

        $queryCount = 0;
        DB::listen(function () use (&$queryCount): void {
            $queryCount++;
        });

        $this->getJson('/api/v1/quran/surahs');

        // A well-optimized list endpoint should not exceed ~5 queries
        $this->assertLessThanOrEqual(
            5,
            $queryCount,
            "Quran surahs uses too many queries: {$queryCount}. Check for N+1."
        );
    }

    public function test_categories_index_does_not_cause_n_plus_one(): void
    {
        $queryCount = 0;
        DB::listen(function () use (&$queryCount): void {
            $queryCount++;
        });

        $this->getJson('/api/v1/categories');

        // Categories index should complete in very few queries
        $this->assertLessThanOrEqual(
            5,
            $queryCount,
            "Categories index uses too many queries: {$queryCount}. Check for N+1."
        );
    }

    public function test_the_admin_locations_index_does_not_cause_n_plus_one(): void
    {
        // A tree of 20 places, each with names in two locales and a parent —
        // exactly the shape that produces one query per row if the eager loads
        // or the parent select are wrong.
        $root = Location::factory()->withNames(['ar' => 'الحجاز', 'en' => 'Hijaz'])->create(['slug' => 'root']);

        for ($i = 1; $i <= 20; $i++) {
            Location::factory()
                ->withNames(['ar' => "مكان {$i}", 'en' => "Place {$i}"])
                ->create(['slug' => "place-{$i}", 'parent_id' => $root->id]);
        }

        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        $queryCount = 0;
        DB::listen(function () use (&$queryCount): void {
            $queryCount++;
        });

        $this->get(route('admin.locations.index'))->assertOk();

        // Constant regardless of row count: pagination count, the page itself,
        // the translations eager load, the parents eager load, plus the session
        // and permission reads the admin guard performs on every request.
        $this->assertLessThanOrEqual(
            15,
            $queryCount,
            "Admin locations index uses too many queries: {$queryCount}. Check for N+1."
        );
    }

    public function test_resolving_a_place_name_adds_no_query_per_row(): void
    {
        // The point of eager-loading the place with named columns: rendering the
        // place name for 20 events must not issue 20 queries.
        $place = Location::factory()->withNames(['ar' => 'المدينة المنورة'])->create([
            'slug' => 'al-madinah', 'latitude' => '24.5247000', 'longitude' => '39.5692000',
        ]);

        for ($i = 1; $i <= 20; $i++) {
            SeerahEvent::factory()->create([
                'slug' => "placed-event-{$i}",
                'location_id' => $place->id,
                'order' => $i,
            ]);
        }

        $events = SeerahEvent::query()
            ->select(['id', 'slug', 'location_id'])
            ->with(['place:id,slug,latitude,longitude', 'place.translations:id,location_id,locale,name'])
            ->get();

        $queryCount = 0;
        DB::listen(function () use (&$queryCount): void {
            $queryCount++;
        });

        foreach ($events as $event) {
            $event->place?->name('ar');
            $event->resolvedCoordinates();
        }

        $this->assertSame(0, $queryCount, "Rendering place names issued {$queryCount} queries; it must issue none.");
    }
}
