<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Seerah\Models\SeerahEventGalleryItem;
use App\Domain\Seerah\Models\SeerahEventType;
use App\Domain\Seerah\Models\SeerahMilestone;
use App\Domain\Seerah\Repositories\SeerahEventRepositoryInterface;
use App\Domain\Shared\Models\Location;
use Database\Factories\SeerahEventFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Covers the public Seerah pages: the interactive timeline index, the events
 * directory, and the single-event page in both styles (article + timeline),
 * plus the localization of UI chrome (KI-14).
 */
final class SeerahPublicPagesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create a Seerah event with translations via the factory.
     */
    private function makeEvent(string $slug, array $attributes = []): SeerahEvent
    {
        return SeerahEventFactory::new()->create(array_merge([
            'slug' => $slug,
            // Use an equal editorial rank unless a test specifies its order.
            'order' => 0,
        ], $attributes));
    }

    private function setEnglishTitle(SeerahEvent $event, string $title): void
    {
        $event->translations()->where('locale', 'en')->update(['title' => $title]);
    }

    /**
     * Update one locale row's translation fields (arrays are cast by the model).
     */
    private function setTranslation(SeerahEvent $event, string $locale, array $fields): void
    {
        $event->translations()->where('locale', $locale)->firstOrFail()->update($fields);
    }

    private function makeMilestone(string $slug, int $order = 1): SeerahMilestone
    {
        $milestone = SeerahMilestone::query()->create([
            'slug' => $slug,
            'order' => $order,
            'metadata' => ['icon' => 'flag'],
            'hijri_from' => -13,
            'hijri_to' => 11,
            'is_active' => true,
        ]);
        $milestone->syncTranslations(['title' => ['ar' => $slug, 'en' => $slug]]);

        return $milestone;
    }

    /**
     * Total results the directory reports for a URL.
     */
    private function directoryTotal(string $url): int
    {
        $total = null;

        $this->withSession(['locale' => 'en'])->get($url)
            ->assertOk()
            ->assertViewHas('events', function (LengthAwarePaginator $events) use (&$total): bool {
                $total = $events->total();

                return true;
            });

        return $total;
    }

    public function test_seerah_index_returns_200(): void
    {
        $this->makeEvent('birth-of-the-prophet');

        $response = $this->get('/seerah');

        $response->assertOk();
    }

    public function test_seerah_events_directory_returns_200(): void
    {
        $this->makeEvent('hijra-to-madinah');

        $response = $this->get('/seerah/events');

        $response->assertOk();
    }

    public function test_public_seerah_pages_return_200_with_no_events(): void
    {
        $this->withSession(['locale' => 'en'])->get('/seerah')->assertOk();

        $this->withSession(['locale' => 'en'])->get('/seerah/events')
            ->assertOk()
            ->assertSee(__('seerah.public.no_results_title'));
    }

    public function test_event_page_article_style_renders_details_and_sources_only(): void
    {
        $event = $this->makeEvent('battle-of-badr');
        $this->setTranslation($event, 'ar', [
            'metadata' => [['key' => 'عدد المسلمين', 'value' => '313']],
            'sources' => [['title' => 'السيرة النبوية', 'author' => 'ابن هشام', 'reference' => 'ج2']],
        ]);

        $this->withSession(['locale' => 'ar'])->get('/seerah/event/battle-of-badr?view=article')
            ->assertOk()
            ->assertSee(__('seerah.public.section_more_details'))
            ->assertSee('عدد المسلمين')
            ->assertSee('313')
            ->assertSee(__('seerah.public.section_sources'))
            ->assertSee('السيرة النبوية')
            ->assertSee('ابن هشام')
            // Removed concepts never render.
            ->assertDontSee('الشخصيات')
            ->assertDontSee('الدروس المستفادة')
            ->assertDontSee('section_characters')
            ->assertDontSee('section_lessons')
            ->assertDontSee('event_timeline_heading');
    }

    public function test_event_page_timeline_style_is_the_default(): void
    {
        $this->makeEvent('battle-of-uhud');

        $response = $this->withSession(['locale' => 'ar'])->get('/seerah/event/battle-of-uhud');

        $response->assertOk()
            // Timeline-style hero CTA + style switch to the article view.
            ->assertSee(__('seerah.public.explore_story'))
            ->assertSee(__('seerah.public.read_as_article'));
    }

    public function test_event_page_unknown_view_param_falls_back_to_timeline_style(): void
    {
        $this->makeEvent('treaty-of-hudaybiyyah');

        $response = $this->get('/seerah/event/treaty-of-hudaybiyyah?view=unknown');

        $response->assertOk()
            ->assertSee(__('seerah.public.read_as_article'));
    }

    public function test_event_page_returns_404_for_unknown_slug(): void
    {
        $response = $this->get('/seerah/event/this-event-does-not-exist');

        $response->assertNotFound();
    }

    public function test_draft_event_page_returns_404(): void
    {
        $this->makeEvent('draft-event', ['status' => 'draft']);

        $this->get('/seerah/event/draft-event')->assertNotFound();
        $this->get('/seerah/event/draft-event?view=article')->assertNotFound();
    }

    public function test_event_navigation_uses_editorial_chronology_and_skips_unpublished_events(): void
    {
        $current = $this->makeEvent('navigation-current', [
            'hijri_year' => null,
            'hijri_month' => null,
            'hijri_day' => null,
            'order' => 2,
        ]);
        $next = $this->makeEvent('navigation-next', [
            'hijri_year' => 4,
            'hijri_month' => 1,
            'hijri_day' => 1,
            'order' => 4,
        ]);
        $previous = $this->makeEvent('navigation-previous', [
            'hijri_year' => 1,
            'hijri_month' => 12,
            'hijri_day' => 29,
            'order' => 1,
        ]);
        $hidden = $this->makeEvent('navigation-hidden', [
            'hijri_year' => 3,
            'hijri_month' => 1,
            'hijri_day' => 1,
            'order' => 3,
            'status' => 'draft',
        ]);
        $this->setEnglishTitle($current, 'Current Chronological Event');
        $this->setEnglishTitle($next, 'Next Chronological Event');
        $this->setEnglishTitle($previous, 'Previous Chronological Event');
        $this->setEnglishTitle($hidden, 'Hidden Draft Event');

        $this->withSession(['locale' => 'en'])
            ->get(route('public.seerah.event', $current->slug))
            ->assertOk()
            ->assertSee('Previous Chronological Event')
            ->assertSee('Next Chronological Event')
            ->assertDontSee('Hidden Draft Event');
    }

    public function test_first_and_last_events_render_only_the_available_navigation_half(): void
    {
        $last = $this->makeEvent('navigation-last', [
            'hijri_year' => 2,
            'hijri_month' => 1,
            'hijri_day' => 1,
            'order' => 1,
        ]);
        $first = $this->makeEvent('navigation-first', [
            'hijri_year' => 1,
            'hijri_month' => 1,
            'hijri_day' => 1,
            'order' => 1,
        ]);
        $this->setEnglishTitle($first, 'First Boundary Event');
        $this->setEnglishTitle($last, 'Last Boundary Event');

        $this->withSession(['locale' => 'en'])
            ->get(route('public.seerah.event', $first->slug))
            ->assertOk()
            ->assertDontSee(__('seerah.public.previous_event'))
            ->assertSee(__('seerah.public.next_event'))
            ->assertSee('Last Boundary Event');

        $this->withSession(['locale' => 'en'])
            ->get(route('public.seerah.event', $last->slug))
            ->assertOk()
            ->assertSee(__('seerah.public.previous_event'))
            ->assertDontSee(__('seerah.public.next_event'))
            ->assertSee('First Boundary Event');
    }

    public function test_event_place_cover_and_gallery_render_from_database_values(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('seerah-events/covers/hero.jpg', 'image');

        $place = Location::factory()
            ->withNames(['ar' => 'بدر', 'en' => 'Badr Wells'])
            ->create(['latitude' => 23.7333, 'longitude' => 38.7667]);
        $event = $this->makeEvent('event-with-public-data', [
            'location_id' => $place->id,
            'cover_url' => 'seerah-events/covers/hero.jpg',
        ]);
        SeerahEventGalleryItem::query()->create([
            'event_id' => $event->id,
            'image' => 'seerah-events/gallery/one.jpg',
            'caption' => ['ar' => 'تعليق عربي', 'en' => 'English caption'],
            'order' => 1,
        ]);

        $this->withSession(['locale' => 'en'])
            ->get(route('public.seerah.event', ['slug' => $event->slug, 'view' => 'article']))
            ->assertOk()
            ->assertSee('openstreetmap.org/export/embed.html', false)
            ->assertSee('Badr Wells')
            ->assertSee('/storage/seerah-events/covers/hero.jpg', false)
            ->assertSee(__('seerah.public.event_gallery'))
            ->assertSee('seerah-events/gallery/one.jpg', false)
            ->assertSee('English caption')
            ->assertDontSee('تعليق عربي');
    }

    public function test_event_without_optional_data_renders_no_optional_sections(): void
    {
        $event = $this->makeEvent('event-without-public-data', ['location_id' => null]);
        $url = route('public.seerah.event', $event->slug);

        foreach ([$url, $url.'?view=article'] as $pageUrl) {
            $response = $this->withSession(['locale' => 'en'])->get($pageUrl)->assertOk();
            // Markup comments name some sections ("More details", "Event Location Map").
            $html = preg_replace('/<!--.*?-->/s', '', (string) $response->getContent());

            foreach ([
                __('seerah.public.section_more_details'),
                __('seerah.public.event_gallery'),
                __('seerah.public.dispute_heading'),
                __('seerah.public.event_location'),
                // "Media" also names the site nav link, so match the section anchor.
                'id="media"',
                'openstreetmap.org/export/embed.html',
            ] as $needle) {
                $this->assertStringNotContainsString($needle, $html, "{$pageUrl} rendered {$needle}");
            }
        }
    }

    public function test_dispute_note_renders_only_when_present_in_locale(): void
    {
        $disputed = $this->makeEvent('disputed-event');
        $this->setTranslation($disputed, 'en', ['dispute_note' => 'Some say it was in Rajab.']);
        $plain = $this->makeEvent('undisputed-event');

        $this->withSession(['locale' => 'en'])
            ->get(route('public.seerah.event', $disputed->slug))
            ->assertOk()
            ->assertSee(__('seerah.public.dispute_heading'))
            ->assertSee('Some say it was in Rajab.');

        $this->withSession(['locale' => 'en'])
            ->get(route('public.seerah.event', $plain->slug))
            ->assertOk()
            ->assertDontSee(__('seerah.public.dispute_heading'));
    }

    public function test_related_events_prefer_same_milestone_then_same_era(): void
    {
        $milestone = $this->makeMilestone('related-milestone');
        $other = $this->makeMilestone('other-milestone', 2);

        $current = $this->makeEvent('related-current', [
            'sm_id' => $milestone->id, 'era' => 'mecca', 'hijri_year' => 2,
        ]);
        $sameMilestoneLate = $this->makeEvent('related-milestone-late', [
            'sm_id' => $milestone->id, 'era' => 'medina', 'hijri_year' => 5,
        ]);
        $sameEraEarly = $this->makeEvent('related-era-early', [
            'sm_id' => $other->id, 'era' => 'mecca', 'hijri_year' => -5,
        ]);
        $sameMilestoneEarly = $this->makeEvent('related-milestone-early', [
            'sm_id' => $milestone->id, 'era' => 'medina', 'hijri_year' => 1,
        ]);
        $this->makeEvent('related-irrelevant', [
            'sm_id' => $other->id, 'era' => 'medina', 'hijri_year' => -10,
        ]);

        $this->withSession(['locale' => 'en'])
            ->get(route('public.seerah.event', $current->slug))
            ->assertOk()
            ->assertViewHas('related', fn (array $related): bool => array_column($related, 'slug') === [
                $sameMilestoneEarly->slug,
                $sameMilestoneLate->slug,
                $sameEraEarly->slug,
            ]);
    }

    public function test_event_without_milestone_or_era_has_no_related_events(): void
    {
        $current = $this->makeEvent('unrelated-current', ['sm_id' => null, 'era' => null]);
        $this->makeEvent('unrelated-other', ['sm_id' => null, 'era' => null]);

        $this->withSession(['locale' => 'en'])
            ->get(route('public.seerah.event', $current->slug))
            ->assertOk()
            ->assertViewHas('related', [])
            ->assertDontSee(__('seerah.public.related_events'));
    }

    public function test_directory_ignores_removed_importance_and_pivotal_filters(): void
    {
        $this->makeEvent('ignored-filter-one');
        $this->makeEvent('ignored-filter-two');

        $this->assertSame(2, $this->directoryTotal(route('public.seerah.events')));
        $this->assertSame(2, $this->directoryTotal(route('public.seerah.events', ['importance' => 'high', 'pivotal' => 1])));
    }

    public function test_directory_era_and_disputed_filters_narrow_results(): void
    {
        $this->makeEvent('filter-mecca', ['era' => 'mecca']);
        $medina = $this->makeEvent('filter-medina', ['era' => 'medina']);
        $disputed = $this->makeEvent('filter-disputed', ['era' => 'mecca']);
        $this->setTranslation($disputed, 'en', ['dispute_note' => 'Dated differently.']);
        $this->setEnglishTitle($medina, 'Medina Era Event');

        $this->assertSame(3, $this->directoryTotal(route('public.seerah.events')));
        $this->assertSame(1, $this->directoryTotal(route('public.seerah.events', ['era' => 'medina'])));
        $this->assertSame(1, $this->directoryTotal(route('public.seerah.events', ['disputed' => 1])));

        $this->withSession(['locale' => 'en'])
            ->get(route('public.seerah.events', ['era' => 'medina']))
            ->assertOk()
            ->assertSee('Medina Era Event');
    }

    public function test_timeline_view_renders_the_extended_event_payload(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('seerah-events/covers/timeline-hero.jpg', 'image');

        $place = Location::factory()
            ->withNames(['ar' => 'المدينة', 'en' => 'Medina City'])
            ->create(['latitude' => 24.4672, 'longitude' => 39.6111]);
        $event = $this->makeEvent('timeline-extended-payload', [
            'location_id' => $place->id,
            'cover_url' => 'seerah-events/covers/timeline-hero.jpg',
        ]);
        SeerahEventGalleryItem::query()->create([
            'event_id' => $event->id,
            'image' => 'seerah-events/gallery/timeline.jpg',
            'caption' => ['en' => 'Timeline caption'],
            'order' => 1,
        ]);

        $this->withSession(['locale' => 'en'])
            ->get(route('public.seerah.event', ['slug' => $event->slug, 'view' => 'timeline']))
            ->assertOk()
            ->assertSee('/storage/seerah-events/covers/timeline-hero.jpg', false)
            ->assertSee('seerah-events/gallery/timeline.jpg', false)
            ->assertSee('Timeline caption')
            ->assertSee('Medina City')
            ->assertSee('openstreetmap.org/export/embed.html', false);
    }

    public function test_editing_event_fields_invalidates_the_extended_public_payload(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('seerah-events/covers/old-hero.jpg', 'image');
        Storage::disk('public')->put('seerah-events/covers/new-hero.jpg', 'image');

        $place = Location::factory()
            ->withNames(['ar' => 'مكة', 'en' => 'Updated Place'])
            ->create(['latitude' => 21.4225, 'longitude' => 39.8262]);
        $event = $this->makeEvent('event-cache-refresh', [
            'cover_url' => 'seerah-events/covers/old-hero.jpg',
            'location_id' => null,
        ]);
        $url = route('public.seerah.event', $event->slug);

        $this->withSession(['locale' => 'en'])->get($url)
            ->assertOk()
            ->assertSee('/storage/seerah-events/covers/old-hero.jpg', false)
            ->assertDontSee('Updated Place');

        app(SeerahEventRepositoryInterface::class)->update($event->id, [
            'cover_url' => 'seerah-events/covers/new-hero.jpg',
            'location_id' => $place->id,
        ]);

        $this->withSession(['locale' => 'en'])->get($url)
            ->assertOk()
            ->assertDontSee('/storage/seerah-events/covers/old-hero.jpg', false)
            ->assertSee('/storage/seerah-events/covers/new-hero.jpg', false)
            ->assertSee('Updated Place')
            ->assertSee('openstreetmap.org/export/embed.html', false);
    }

    public function test_seerah_index_ui_chrome_is_localized_in_english(): void
    {
        $this->makeEvent('farewell-pilgrimage');

        $response = $this->withSession(['locale' => 'en'])->get('/seerah');

        $response->assertOk()
            // English UI chrome (quest-map redesign) rendered from lang files...
            ->assertSee('The Journey of Light')      // journey badge
            ->assertSee('The Prophetic Biography')   // hero heading
            ->assertSee('Journey')                   // mode toggle
            ->assertSee('Articles')                  // mode toggle
            // ...and the Arabic UI strings are not hardcoded.
            ->assertDontSee('رحلة النور')
            ->assertDontSee('الرحلة');
    }

    public function test_seerah_events_directory_ui_chrome_is_localized_in_english(): void
    {
        $this->makeEvent('conquest-of-mecca');

        $response = $this->withSession(['locale' => 'en'])->get('/seerah/events');

        $response->assertOk()
            ->assertSee('From the Year of the Elephant to the Rashidun Caliphate')
            ->assertDontSee('من عام الفيل حتى العهد الراشدي')
            ->assertDontSee('الأحداث التاريخية');
    }

    #[DataProvider('unpublishMutations')]
    public function test_ssg_001_unpublishing_invalidates_cached_event_detail(string $mutation): void
    {
        $event = $this->makeEvent("ssg-001-detail-{$mutation}");
        $title = "SSG-001 detail {$mutation}";
        $event->translations()->where('locale', 'en')->update(['title' => $title]);

        $url = route('public.seerah.event', $event->slug);

        $this->withSession(['locale' => 'en'])->get($url)
            ->assertOk()
            ->assertSee($title);

        $this->unpublish($event, $mutation);

        $this->withSession(['locale' => 'en'])->get($url)->assertNotFound();
    }

    #[DataProvider('unpublishMutations')]
    public function test_ssg_001_unpublishing_invalidates_cached_filtered_paginated_listing(string $mutation): void
    {
        $type = $this->makeEventType("ssg-001-{$mutation}");

        foreach (range(1, 12) as $year) {
            $this->makeEvent("ssg-001-filler-{$mutation}-{$year}", [
                'event_type' => $type->id,
                'hijri_year' => $year,
            ]);
        }

        $event = $this->makeEvent("ssg-001-listing-{$mutation}", [
            'event_type' => $type->id,
            'hijri_year' => 999,
        ]);
        $title = "SSG-001 listing {$mutation}";
        $event->translations()->where('locale', 'en')->update(['title' => $title]);

        $url = route('public.seerah.events', ['category' => $type->slug, 'page' => 2]);

        $this->withSession(['locale' => 'en'])->get($url)
            ->assertOk()
            ->assertSee($title);

        $this->unpublish($event, $mutation);

        $this->withSession(['locale' => 'en'])->get($url)
            ->assertOk()
            ->assertDontSee($title);
    }

    public function test_ssg_001_publishing_refreshes_public_pages_without_flushing_unrelated_cache(): void
    {
        $type = $this->makeEventType('ssg-001-publish');
        $event = $this->makeEvent('ssg-001-publish', [
            'event_type' => $type->id,
            'status' => 'draft',
        ]);
        $title = 'SSG-001 newly published';
        $event->translations()->where('locale', 'en')->update(['title' => $title]);
        $listingUrl = route('public.seerah.events', ['category' => $type->slug]);

        Cache::put('public_hadith_show_unrelated', 'still-cached', 1800);

        $this->withSession(['locale' => 'en'])->get($listingUrl)
            ->assertOk()
            ->assertDontSee($title);

        app(SeerahEventRepositoryInterface::class)->update($event->id, ['status' => 'published']);

        $this->withSession(['locale' => 'en'])->get($listingUrl)
            ->assertOk()
            ->assertSee($title);
        $this->withSession(['locale' => 'en'])
            ->get(route('public.seerah.event', $event->slug))
            ->assertOk()
            ->assertSee($title);
        $this->assertSame('still-cached', Cache::get('public_hadith_show_unrelated'));
    }

    public function test_ssg_001_second_event_request_is_served_from_cache(): void
    {
        $event = $this->makeEvent('ssg-001-cache-hit');
        $url = route('public.seerah.event', $event->slug);
        $seerahQueries = 0;

        DB::listen(static function ($query) use (&$seerahQueries): void {
            if (str_contains($query->sql, 'seerah_')) {
                $seerahQueries++;
            }
        });

        $this->withSession(['locale' => 'en'])->get($url)->assertOk();
        $queriesAfterFirstRequest = $seerahQueries;

        $this->withSession(['locale' => 'en'])->get($url)->assertOk();

        $this->assertGreaterThan(0, $queriesAfterFirstRequest);
        $this->assertSame($queriesAfterFirstRequest, $seerahQueries);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function unpublishMutations(): array
    {
        return [
            'single update' => ['single'],
            'bulk status update' => ['bulk'],
        ];
    }

    private function makeEventType(string $slug): SeerahEventType
    {
        return SeerahEventType::query()->create([
            'slug' => $slug,
            'locale' => ['ar' => $slug, 'en' => $slug],
            'is_active' => true,
        ]);
    }

    private function unpublish(SeerahEvent $event, string $mutation): void
    {
        $repository = app(SeerahEventRepositoryInterface::class);

        if ($mutation === 'bulk') {
            $repository->bulkUpdateStatus([$event->id], 'draft');

            return;
        }

        $repository->update($event->id, ['status' => 'draft']);
    }
}
