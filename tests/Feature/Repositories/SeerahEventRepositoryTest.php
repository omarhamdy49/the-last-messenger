<?php

declare(strict_types=1);

namespace Tests\Feature\Repositories;

use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Seerah\Models\SeerahEventGalleryItem;
use App\Domain\Seerah\Models\SeerahEventMedia;
use App\Domain\Seerah\Models\SeerahEventTranslation;
use App\Domain\Seerah\Models\SeerahEventType;
use App\Domain\Seerah\Models\SeerahMilestone;
use App\Domain\Seerah\Repositories\SeerahEventRepositoryInterface;
use App\Domain\Shared\Models\Location;
use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Integration tests for SeerahEventRepository.
 *
 * The repository exposes two loading profiles:
 *
 *  LIST   — getChronological() / getByType() / search() / paginatePublicFiltered():
 *           translations (no content), type, place + place translations.
 *
 *  DETAIL — findBySlug(): full translations (content, metadata, sources), type,
 *           milestone, place, gallery and media links.
 *
 * Chronology is `order`, then the Hijri parts, then id.
 */
class SeerahEventRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private int $counter = 0;

    // ---------------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------------

    private function repo(): SeerahEventRepositoryInterface
    {
        return app(SeerahEventRepositoryInterface::class);
    }

    private function type(string $slug = 'other'): SeerahEventType
    {
        return SeerahEventType::query()->firstOrCreate(
            ['slug' => $slug],
            ['locale' => ['ar' => $slug, 'en' => $slug], 'is_active' => true],
        );
    }

    private function milestone(string $slug, int $order = 1): SeerahMilestone
    {
        return SeerahMilestone::query()->create([
            'slug' => $slug, 'order' => $order, 'metadata' => ['icon' => 'x'],
            'hijri_from' => -13, 'hijri_to' => 1, 'is_active' => true,
        ]);
    }

    /**
     * Create a SeerahEvent with ready ar/en translation rows.
     *
     * @param  array<string, mixed>  $translation  extra columns for both locales
     */
    private function createEvent(array $overrides = [], array $translation = []): SeerahEvent
    {
        $counter = ++$this->counter;

        $event = SeerahEvent::query()->create(array_merge([
            'slug' => "event-{$counter}",
            'event_type' => $this->type()->id,
            'status' => 'published',
            'order' => $counter,
            'hijri_year' => 1,
            'hijri_month' => 1,
            'hijri_day' => 1,
        ], $overrides));

        foreach (['ar' => "عنوان الحدث {$counter}", 'en' => "Event Title {$counter}"] as $locale => $title) {
            SeerahEventTranslation::query()->create(array_merge([
                'event_id' => $event->id,
                'locale' => $locale,
                'title' => $title,
                'description' => "Event description {$counter}",
                'content' => "Event content {$counter}",
                'is_ready' => true,
            ], $translation));
        }

        return $event->fresh();
    }

    // ---------------------------------------------------------------------------
    // Chronology
    // ---------------------------------------------------------------------------

    public function test_get_chronological_returns_published_paginated_results(): void
    {
        $this->createEvent();
        $this->createEvent();
        $this->createEvent(['status' => 'draft']);

        $this->assertEquals(2, $this->repo()->getChronological(10)->total());
    }

    public function test_get_chronological_uses_hijri_dates_for_equal_order(): void
    {
        $this->createEvent(['order' => 0, 'hijri_year' => 3]);
        $this->createEvent(['order' => 0, 'hijri_year' => 1]);
        $this->createEvent(['order' => 0, 'hijri_year' => 2]);

        $years = collect($this->repo()->getChronological(10)->items())->pluck('hijri_year')->all();

        $this->assertEquals([1, 2, 3], $years);
    }

    public function test_order_keeps_undated_events_between_dated_events_on_every_list(): void
    {
        $last = $this->createEvent(['order' => 30, 'hijri_year' => 9, 'hijri_month' => 7]);
        $undated = $this->createEvent([
            'order' => 20, 'hijri_year' => null, 'hijri_month' => null, 'hijri_day' => null,
        ]);
        $first = $this->createEvent(['order' => 10, 'hijri_year' => -13]);
        $draft = $this->createEvent(['order' => 15, 'status' => 'draft']);
        $repo = $this->repo();
        $expected = [$first->id, $undated->id, $last->id];

        $this->assertSame($expected, $repo->getChronological(10)->pluck('id')->all());
        $this->assertSame($expected, $repo->paginatePublicFiltered(['sort' => 'asc'], 'en', 10)->pluck('id')->all());
        $this->assertSame(array_reverse($expected), $repo->paginatePublicFiltered(['sort' => 'desc'], 'en', 10)->pluck('id')->all());
        $this->assertSame([$first->id, $draft->id, $undated->id, $last->id], $repo->paginateWithFilters([], 10)->pluck('id')->all());

        foreach ([$first, $undated, $last] as $index => $event) {
            $page = $repo->getPublicPage($event->slug, 'en');
            $this->assertSame($expected[$index - 1] ?? null, $page['prev']?->id);
            $this->assertSame($expected[$index + 1] ?? null, $page['next']?->id);
        }
    }

    public function test_equal_order_and_dates_have_stable_navigation_including_null_and_pre_hijra_dates(): void
    {
        $dated = $this->createEvent(['order' => 0, 'hijri_year' => -13]);
        $undatedFirst = $this->createEvent([
            'order' => 0, 'hijri_year' => null, 'hijri_month' => null, 'hijri_day' => null,
        ]);
        $undatedSecond = $this->createEvent([
            'order' => 0, 'hijri_year' => null, 'hijri_month' => null, 'hijri_day' => null,
        ]);
        $partiallyDated = $this->createEvent([
            'order' => 0, 'hijri_year' => -13, 'hijri_month' => null, 'hijri_day' => null,
        ]);
        $ranked = $this->createEvent(['order' => 10, 'hijri_year' => -53]);
        $repo = $this->repo();
        $events = [$undatedFirst, $undatedSecond, $partiallyDated, $dated, $ranked];
        $expected = array_map(fn (SeerahEvent $event) => $event->id, $events);

        $this->assertSame($expected, $repo->getChronological(10)->pluck('id')->all());
        $this->assertSame(array_reverse($expected), $repo->paginatePublicFiltered(['sort' => 'desc'], 'en', 10)->pluck('id')->all());

        foreach ($events as $index => $event) {
            $page = $repo->getPublicPage($event->slug, 'en');
            $this->assertSame($expected[$index - 1] ?? null, $page['prev']?->id);
            $this->assertSame($expected[$index + 1] ?? null, $page['next']?->id);
        }
    }

    // ---------------------------------------------------------------------------
    // List relations
    // ---------------------------------------------------------------------------

    public function test_list_loads_translations_type_and_place_only(): void
    {
        $this->createEvent();

        $event = $this->repo()->getChronological(10)->first();

        foreach (['translations', 'type', 'place'] as $relation) {
            $this->assertTrue($event->relationLoaded($relation), "The list path must eager-load {$relation}.");
        }

        foreach (['milestone', 'gallery', 'mediaLinks'] as $relation) {
            $this->assertFalse($event->relationLoaded($relation), "The list path must NOT load {$relation}.");
        }

        $this->assertNull($event->translations->first()->getAttribute('content'));
    }

    // ---------------------------------------------------------------------------
    // findBySlug() — detail path
    // ---------------------------------------------------------------------------

    public function test_find_by_slug_returns_matching_event(): void
    {
        $event = $this->createEvent(['slug' => 'battle-of-badr']);

        $result = $this->repo()->findBySlug('battle-of-badr');

        $this->assertNotNull($result);
        $this->assertEquals($event->id, $result->id);
    }

    public function test_find_by_slug_returns_null_for_non_existent_slug_or_draft(): void
    {
        $this->createEvent(['slug' => 'a-draft', 'status' => 'draft']);

        $this->assertNull($this->repo()->findBySlug('does-not-exist-xyz'));
        $this->assertNull($this->repo()->findBySlug('a-draft'));
    }

    public function test_find_by_slug_loads_detail_translations_gallery_and_media_links(): void
    {
        $event = $this->createEvent(['slug' => 'conquest-of-mecca'], [
            'metadata' => [['key' => 'k', 'value' => 'v']],
            'sources' => [['title' => 't', 'author' => 'a', 'reference' => 'r']],
        ]);
        SeerahEventGalleryItem::query()->create(['event_id' => $event->id, 'image' => 'g.jpg', 'order' => 0]);
        $media = Media::query()->create([
            'filename' => 'm.jpg', 'original_filename' => 'm.jpg', 'path' => 'media/m.jpg',
            'mime_type' => 'image/jpeg', 'size' => 1, 'disk' => 'public', 'type' => 'image',
        ]);
        SeerahEventMedia::query()->create(['event_id' => $event->id, 'media_id' => $media->id]);

        $result = $this->repo()->findBySlug('conquest-of-mecca');

        foreach (['translations', 'type', 'milestone', 'place', 'gallery', 'mediaLinks'] as $relation) {
            $this->assertTrue($result->relationLoaded($relation), "findBySlug() must eager-load {$relation}.");
        }

        $this->assertSame('Event content 1', $result->translations->firstWhere('locale', 'ar')->content);
        $this->assertSame([['key' => 'k', 'value' => 'v']], $result->metadataFor('ar'));
        $this->assertSame('a', $result->sourcesFor('ar')[0]['author']);
        $this->assertCount(1, $result->gallery);
        $this->assertSame($media->id, $result->mediaLinks->first()->media_id);
    }

    // ---------------------------------------------------------------------------
    // getByType() / search()
    // ---------------------------------------------------------------------------

    public function test_get_by_type_returns_only_matching_event_type(): void
    {
        $battle = $this->type('battle');
        $revelation = $this->type('revelation');

        $this->createEvent(['event_type' => $battle->id]);
        $this->createEvent(['event_type' => $battle->id]);
        $this->createEvent(['event_type' => $revelation->id]);

        $bySlug = $this->repo()->getByType('battle');

        $this->assertEquals(2, $bySlug->total());
        foreach ($bySlug->items() as $event) {
            $this->assertEquals('battle', $event->type->slug);
        }

        $this->assertEquals(1, $this->repo()->getByType($revelation->id)->total());
    }

    public function test_search_returns_matching_published_events(): void
    {
        // A 2-byte term takes the LIKE path: FULLTEXT cannot see rows that
        // RefreshDatabase never commits.
        $this->createEvent([], ['title' => 'Qx Badr']);
        $this->createEvent(['status' => 'draft'], ['title' => 'Qx Uhud']);
        $this->createEvent();

        $this->assertEquals(1, $this->repo()->search('Qx')->total());
    }

    // ---------------------------------------------------------------------------
    // Public directory filters
    // ---------------------------------------------------------------------------

    public function test_paginate_public_filtered_by_era(): void
    {
        $mecca = $this->createEvent(['era' => 'mecca']);
        $this->createEvent(['era' => 'medina']);

        $this->assertSame([$mecca->id], $this->repo()->paginatePublicFiltered(['era' => 'mecca'], 'en')->pluck('id')->all());
    }

    public function test_paginate_public_filtered_by_disputed_and_facet_count(): void
    {
        $disputed = $this->createEvent([], ['dispute_note' => 'Dates differ']);
        $this->createEvent([], ['dispute_note' => '']);
        $this->createEvent();

        $this->assertSame([$disputed->id], $this->repo()->paginatePublicFiltered(['disputed' => 1], 'en')->pluck('id')->all());
        $this->assertSame(['disputed' => 1], $this->repo()->publicFilterFacets('en'));
    }

    public function test_related_events_prefer_same_milestone_then_same_era(): void
    {
        $milestone = $this->milestone('makkah');
        $other = $this->milestone('madinah', 2);

        $event = $this->createEvent(['sm_id' => $milestone->id, 'era' => 'mecca']);
        $sameEra = $this->createEvent(['sm_id' => $other->id, 'era' => 'mecca', 'order' => 0]);
        $sameMilestone = $this->createEvent(['sm_id' => $milestone->id, 'era' => 'medina']);
        $this->createEvent(['sm_id' => $other->id, 'era' => 'medina']);

        $related = $this->repo()->getPublicPage($event->slug, 'en')['related'];

        $this->assertSame([$sameMilestone->id, $sameEra->id], $related->pluck('id')->all());
    }

    // ---------------------------------------------------------------------------
    // Query counts
    // ---------------------------------------------------------------------------

    public function test_list_query_count_is_less_than_detail_query_count(): void
    {
        $this->createEvent(['slug' => 'migration-to-medina', 'sm_id' => $this->milestone('m')->id]);
        $repo = $this->repo();

        DB::enableQueryLog();
        $repo->getChronological(10);
        $listCount = count(DB::getQueryLog());
        DB::flushQueryLog();

        $repo->findBySlug('migration-to-medina');
        $detailCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThan($detailCount, $listCount);
    }

    public function test_get_chronological_does_not_cause_n_plus_one(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $place = Location::factory()->withNames(['ar' => "مكان {$i}"])->create();
            $this->createEvent(['location_id' => $place->id, 'event_type' => $this->type("type-{$i}")->id]);
        }

        $queryCount = 0;
        DB::listen(static function () use (&$queryCount): void {
            $queryCount++;
        });

        $this->repo()->getChronological(5);

        // count + data + translations + type + place + place.translations — all batched.
        $this->assertLessThanOrEqual(6, $queryCount, "getChronological() issued {$queryCount} queries for 5 events.");
    }
}
