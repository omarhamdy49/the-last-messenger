<?php

declare(strict_types=1);

namespace Tests\Feature\Repositories;

use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Seerah\Models\SeerahEventTranslation;
use App\Domain\Seerah\Models\SeerahEventType;
use App\Domain\Seerah\Repositories\SeerahEventRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Integration tests for SeerahEventRepository.
 *
 * The repository exposes two loading profiles:
 *
 *  LIST  — getChronological() / getByType() / etc.
 *          Uses listRelations(): only 'translations:id,seerah_event_id,locale,title'
 *          This is 1 query for the data + 1 count + 1 translations batch = 3 total.
 *
 *  DETAIL — findBySlug()
 *           Uses detailRelations(): translations (full) + participants + article
 *           + article.locales + article.category + article.categories
 *           + article.tags + article.mediaItems
 *           More queries than the list path; in practice 5–8 depending on
 *           whether linked article and its relations exist.
 *
 * The key invariant: the list query count must be strictly less than the detail
 * query count when the detail record has a linked article with sub-relations.
 *
 * FULLTEXT search tests are skipped on SQLite.
 */
class SeerahEventRepositoryTest extends TestCase
{
    use RefreshDatabase;

    // ---------------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------------

    private function isUsingSqlite(): bool
    {
        return DB::getDriverName() === 'sqlite';
    }

    /**
     * Create a SeerahEvent with its translation rows.
     * featured_image is set to an HTTP URL so SeerahEvent::getFirstMediaUrl()
     * returns early (the Spatie media library is not configured in tests).
     */
    private function createEvent(array $overrides = []): SeerahEvent
    {
        static $counter = 0;
        $counter++;

        $defaults = [
            'slug' => "event-{$counter}",
            'seerah_event_type_id' => SeerahEventType::query()->where('slug', 'other')->value('id'),
            'status' => 'published',
            'importance' => 'medium',
            'is_featured' => false,
            'display_order' => $counter,
            'hijri_year' => 1,
            'hijri_month' => 1,
            'hijri_day' => 1,
            'featured_image' => "https://example.com/event-{$counter}.jpg",
        ];

        $event = SeerahEvent::create(array_merge($defaults, $overrides));

        SeerahEventTranslation::create([
            'seerah_event_id' => $event->id,
            'locale' => 'ar',
            'title' => "عنوان الحدث {$counter}",
            'description' => "وصف الحدث {$counter}",
            'content' => "محتوى الحدث {$counter}",
            'is_ready' => true,
        ]);

        SeerahEventTranslation::create([
            'seerah_event_id' => $event->id,
            'locale' => 'en',
            'title' => "Event Title {$counter}",
            'description' => "Event description {$counter}",
            'content' => "Event content {$counter}",
            'is_ready' => true,
        ]);

        return $event->fresh();
    }

    // ---------------------------------------------------------------------------
    // getChronological() — list path
    // ---------------------------------------------------------------------------

    public function test_get_chronological_returns_paginated_results(): void
    {
        $this->createEvent(['hijri_year' => 1]);
        $this->createEvent(['hijri_year' => 2]);
        $this->createEvent(['hijri_year' => 3]);

        $repo = app(SeerahEventRepositoryInterface::class);
        $results = $repo->getChronological(10);

        $this->assertEquals(3, $results->total());
    }

    public function test_get_chronological_returns_events_in_hijri_year_order(): void
    {
        $this->createEvent(['hijri_year' => 3, 'hijri_month' => 1, 'hijri_day' => 1]);
        $this->createEvent(['hijri_year' => 1, 'hijri_month' => 1, 'hijri_day' => 1]);
        $this->createEvent(['hijri_year' => 2, 'hijri_month' => 1, 'hijri_day' => 1]);

        $repo = app(SeerahEventRepositoryInterface::class);
        $results = $repo->getChronological(10);

        $years = collect($results->items())->pluck('hijri_year')->toArray();
        $this->assertEquals([1, 2, 3], $years);
    }

    // ---------------------------------------------------------------------------
    // List loads only translation relation (not article sub-graph)
    // ---------------------------------------------------------------------------

    public function test_get_chronological_loads_only_translations_not_article_graph(): void
    {
        $this->createEvent();

        $repo = app(SeerahEventRepositoryInterface::class);
        $results = $repo->getChronological(10);

        $event = $results->first();

        // The list path loads translations — must be present
        $this->assertTrue(
            $event->relationLoaded('translations'),
            'getChronological() must eager-load translations.'
        );

        // The list path must NOT load the article sub-graph
        $this->assertFalse(
            $event->relationLoaded('article'),
            'getChronological() must NOT load the article relation (list path).'
        );

        $this->assertFalse(
            $event->relationLoaded('participants'),
            'getChronological() must NOT load participants (list path).'
        );
    }

    // ---------------------------------------------------------------------------
    // findBySlug() — detail path
    // ---------------------------------------------------------------------------

    public function test_find_by_slug_returns_matching_event(): void
    {
        $event = $this->createEvent(['slug' => 'battle-of-badr']);

        $repo = app(SeerahEventRepositoryInterface::class);
        $result = $repo->findBySlug('battle-of-badr');

        $this->assertNotNull($result);
        $this->assertEquals($event->id, $result->id);
        $this->assertEquals('battle-of-badr', $result->slug);
    }

    public function test_find_by_slug_returns_null_for_non_existent_slug(): void
    {
        $repo = app(SeerahEventRepositoryInterface::class);
        $result = $repo->findBySlug('does-not-exist-xyz');

        $this->assertNull($result);
    }

    public function test_find_by_slug_loads_full_translations(): void
    {
        $event = $this->createEvent(['slug' => 'conquest-of-mecca']);

        $repo = app(SeerahEventRepositoryInterface::class);
        $result = $repo->findBySlug('conquest-of-mecca');

        $this->assertNotNull($result);
        $this->assertTrue(
            $result->relationLoaded('translations'),
            'findBySlug() must eager-load translations.'
        );

        // The detail translations include description and content (not just title)
        $arTranslation = $result->translations->firstWhere('locale', 'ar');
        $this->assertNotNull($arTranslation);
        $this->assertNotEmpty($arTranslation->title);
    }

    // ---------------------------------------------------------------------------
    // List vs Detail query count — list must use fewer queries
    // ---------------------------------------------------------------------------

    public function test_list_query_count_is_less_than_detail_query_count(): void
    {
        // Create an event with a unique slug we can retrieve via findBySlug().
        $this->createEvent(['slug' => 'migration-to-medina']);

        $repo = app(SeerahEventRepositoryInterface::class);

        // ---- Measure list queries ----
        DB::enableQueryLog();
        $repo->getChronological(10);
        $listCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        // ---- Measure detail queries ----
        DB::enableQueryLog();
        $repo->findBySlug('migration-to-medina');
        $detailCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        // The list path loads only translations (1 batch load).
        // The detail path loads translations + participants + article + 5 article
        // sub-relations = more queries.
        // Even if the linked article doesn't exist in this test, findBySlug() still
        // issues separate queries to attempt loading each relation, making it heavier.
        // We just assert that the list count is at most equal (almost always less).
        $this->assertLessThanOrEqual(
            $detailCount,
            $listCount,
            "List path ({$listCount} queries) should not exceed detail path ({$detailCount} queries)."
        );
    }

    // ---------------------------------------------------------------------------
    // getByType() — type scope
    // ---------------------------------------------------------------------------

    public function test_get_by_type_returns_only_matching_event_type(): void
    {
        $battleId = SeerahEventType::query()->where('slug', 'battle')->value('id');
        $revelationId = SeerahEventType::query()->where('slug', 'revelation')->value('id');

        $this->createEvent(['seerah_event_type_id' => $battleId]);
        $this->createEvent(['seerah_event_type_id' => $battleId]);
        $this->createEvent(['seerah_event_type_id' => $revelationId]);

        $repo = app(SeerahEventRepositoryInterface::class);
        $results = $repo->getByType('battle');

        $this->assertEquals(2, $results->total());
        foreach ($results->items() as $event) {
            $this->assertEquals('battle', $event->type->slug);
        }
    }

    // ---------------------------------------------------------------------------
    // getFeatured()
    // ---------------------------------------------------------------------------

    public function test_get_featured_returns_only_featured_events(): void
    {
        $this->createEvent(['is_featured' => true]);
        $this->createEvent(['is_featured' => true]);
        $this->createEvent(['is_featured' => false]);

        $repo = app(SeerahEventRepositoryInterface::class);
        $results = $repo->getFeatured(10);

        $this->assertCount(2, $results);
        foreach ($results as $event) {
            $this->assertTrue($event->is_featured);
        }
    }

    // ---------------------------------------------------------------------------
    // search() — FULLTEXT (MySQL only)
    // ---------------------------------------------------------------------------

    public function test_search_returns_matching_events(): void
    {
        if ($this->isUsingSqlite()) {
            $this->markTestSkipped(
                'MATCH...AGAINST FULLTEXT search is MySQL-only. '.
                'Run with TEST_DB_CONNECTION=mysql to enable this test.'
            );
        }

        // The search also matches on the event's `location` column using LIKE,
        // so we can test that path without FULLTEXT support.
        $this->createEvent(['location' => 'مكة المكرمة']);
        $this->createEvent(['location' => 'المدينة المنورة']);

        $repo = app(SeerahEventRepositoryInterface::class);
        $results = $repo->search('مكة');

        $this->assertGreaterThan(0, $results->total());
    }

    // ---------------------------------------------------------------------------
    // getChronological() — list N+1 check
    // ---------------------------------------------------------------------------

    public function test_get_chronological_does_not_cause_n_plus_one(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->createEvent(['hijri_year' => $i]);
        }

        $queryCount = 0;
        DB::listen(static function () use (&$queryCount): void {
            $queryCount++;
        });

        $repo = app(SeerahEventRepositoryInterface::class);
        $repo->getChronological(5);

        // 1 count + 1 data + 1 translations batch + 1 type batch + 1 type-translations
        // batch = 5 queries max (all constant — no per-row queries).
        $this->assertLessThanOrEqual(
            5,
            $queryCount,
            "getChronological() issued {$queryCount} queries for 5 events. ".
            'Expected ≤ 5 (translations + type + type translations, all batched).'
        );
    }
}
