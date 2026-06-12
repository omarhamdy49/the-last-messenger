<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Quran\Models\Surah;
use App\Domain\Seerah\Models\SeerahEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NoNPlusOneQueriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_seerah_index_does_not_cause_n_plus_one(): void
    {
        // Create a set of seerah events to trigger potential N+1 scenarios.
        // featured_image is set to a URL so the model's getFirstMediaUrl() override
        // returns early, avoiding Spatie media queries incompatible with SQLite.
        for ($i = 1; $i <= 5; $i++) {
            SeerahEvent::query()->create([
                'slug' => "event-{$i}",
                'event_type' => 'other',
                'importance' => 'medium',
                'is_featured' => false,
                'display_order' => $i,
                'featured_image' => "https://example.com/event-{$i}.jpg",
                'title' => ['ar' => "عنوان {$i}", 'en' => "Title {$i}"],
                'description' => ['ar' => "وصف {$i}", 'en' => "Description {$i}"],
            ]);
        }

        $queryCount = 0;
        DB::listen(function () use (&$queryCount): void {
            $queryCount++;
        });

        $this->getJson('/api/v1/seerah');

        // A well-optimized list endpoint should not exceed ~5 queries
        // (1 for count, 1 for data, maybe 1-2 for eager loads)
        $this->assertLessThanOrEqual(
            5,
            $queryCount,
            "Seerah index uses too many queries: {$queryCount}. Check for N+1."
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
}
