<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Seerah\Models\SeerahEventTimelineItem;
use App\Domain\Seerah\Models\SeerahEventType;
use App\Domain\Seerah\Models\SeerahParticipant;
use App\Domain\Seerah\Repositories\SeerahEventRepositoryInterface;
use App\Models\Article;
use App\Models\ArticleLocale;
use Database\Factories\SeerahEventFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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
     * featured_image is set to avoid Spatie media fallback queries on SQLite.
     */
    private function makeEvent(string $slug, array $attributes = []): SeerahEvent
    {
        return SeerahEventFactory::new()->create(array_merge([
            'slug' => $slug,
            'featured_image' => 'https://example.com/image.jpg',
            // The factory may pick a random article id; no articles exist here.
            'related_article_id' => null,
        ], $attributes));
    }

    /**
     * Attach an inner timeline item (with AR/EN translations) to an event.
     */
    private function attachTimelineItem(SeerahEvent $event, int $order, string $titleAr): SeerahEventTimelineItem
    {
        $item = SeerahEventTimelineItem::query()->create([
            'seerah_event_id' => $event->id,
            'display_order' => $order,
        ]);

        foreach (['ar' => $titleAr, 'en' => "Step {$order}"] as $locale => $title) {
            DB::table('seerah_event_timeline_item_translations')->insert([
                'seerah_event_timeline_item_id' => $item->id,
                'locale' => $locale,
                'title' => $title,
                'description' => "وصف {$title}",
                'is_ready' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $item;
    }

    /**
     * Attach a participant (with an AR translation) to an event.
     */
    private function attachParticipant(SeerahEvent $event, string $nameAr): SeerahParticipant
    {
        $participant = SeerahParticipant::query()->create([
            'event_id' => $event->id,
            'participant_type' => 'sahabi',
            'role' => 'companion',
        ]);

        DB::table('seerah_participant_translations')->insert([
            'seerah_participant_id' => $participant->id,
            'locale' => 'ar',
            'name' => $nameAr,
            'description' => "نبذة عن {$nameAr}",
            'is_ready' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $participant;
    }

    private function setEnglishTitle(SeerahEvent $event, string $title): void
    {
        $event->translations()->where('locale', 'en')->update(['title' => $title]);
    }

    private function makeArticle(string $slug, string $title, string $status = Article::STATUS_PUBLISHED): Article
    {
        $article = Article::factory()->create([
            'status' => $status,
            'published_at' => $status === Article::STATUS_PUBLISHED ? now() : null,
        ]);

        ArticleLocale::query()->create([
            'article_id' => $article->id,
            'locale' => 'en',
            'title' => $title,
            'slug' => $slug,
            'excerpt' => $title,
            'content' => ['body' => '<p>Article body</p>'],
            'is_ready' => true,
        ]);

        return $article;
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

    public function test_event_page_article_style_returns_200_with_timeline_and_characters(): void
    {
        $event = $this->makeEvent('battle-of-badr');
        $this->attachTimelineItem($event, 1, 'خروج الجيش');
        $this->attachTimelineItem($event, 2, 'بداية المعركة');
        $this->attachParticipant($event, 'حمزة بن عبد المطلب');

        $response = $this->withSession(['locale' => 'ar'])->get('/seerah/event/battle-of-badr');

        $response->assertOk()
            ->assertSee('خروج الجيش')
            ->assertSee('بداية المعركة')
            ->assertSee('حمزة بن عبد المطلب');
    }

    public function test_event_page_timeline_style_returns_200_with_real_data(): void
    {
        $event = $this->makeEvent('battle-of-uhud');
        $this->attachTimelineItem($event, 1, 'خطة النبي');
        $this->attachParticipant($event, 'مصعب بن عمير');

        $response = $this->withSession(['locale' => 'ar'])->get('/seerah/event/battle-of-uhud?view=timeline');

        $response->assertOk()
            // Timeline-style hero CTA + style switch back to the article view.
            ->assertSee(__('seerah.public.explore_story'))
            ->assertSee(__('seerah.public.read_as_article'))
            ->assertSee('خطة النبي')
            ->assertSee('مصعب بن عمير');
    }

    public function test_event_page_unknown_view_param_falls_back_to_article_style(): void
    {
        $this->makeEvent('treaty-of-hudaybiyyah');

        $response = $this->get('/seerah/event/treaty-of-hudaybiyyah?view=unknown');

        $response->assertOk()
            ->assertSee(__('seerah.public.read_as_timeline'));
    }

    public function test_event_page_returns_404_for_unknown_slug(): void
    {
        $response = $this->get('/seerah/event/this-event-does-not-exist');

        $response->assertNotFound();
    }

    public function test_event_navigation_uses_hijri_chronology_and_skips_unpublished_events(): void
    {
        $current = $this->makeEvent('navigation-current', [
            'hijri_year' => 2,
            'hijri_month' => 2,
            'hijri_day' => 2,
            'display_order' => 2,
            'is_featured' => false,
        ]);
        $next = $this->makeEvent('navigation-next', [
            'hijri_year' => 4,
            'hijri_month' => 1,
            'hijri_day' => 1,
            'display_order' => 1,
            'is_featured' => false,
        ]);
        $previous = $this->makeEvent('navigation-previous', [
            'hijri_year' => 1,
            'hijri_month' => 12,
            'hijri_day' => 29,
            'display_order' => 9,
            'is_featured' => false,
        ]);
        $hidden = $this->makeEvent('navigation-hidden', [
            'hijri_year' => 3,
            'hijri_month' => 1,
            'hijri_day' => 1,
            'display_order' => 1,
            'status' => 'draft',
            'is_featured' => false,
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
            'display_order' => 1,
            'is_featured' => false,
        ]);
        $first = $this->makeEvent('navigation-first', [
            'hijri_year' => 1,
            'hijri_month' => 1,
            'hijri_day' => 1,
            'display_order' => 1,
            'is_featured' => false,
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

    public function test_event_media_map_and_public_related_article_render_from_database_values(): void
    {
        $article = $this->makeArticle('locale-related-article', 'Localized Related Article');
        $event = $this->makeEvent('event-with-public-data', [
            'featured_image' => 'seerah-events/hero.jpg',
            'gallery' => ['seerah-events/gallery-one.jpg', 'https://example.com/gallery-two.jpg'],
            'related_article_id' => $article->id,
            'latitude' => 21.4225,
            'longitude' => 39.8262,
            'is_featured' => false,
        ]);

        $this->withSession(['locale' => 'en'])
            ->get(route('public.seerah.event', $event->slug))
            ->assertOk()
            ->assertSee('/storage/seerah-events/hero.jpg', false)
            ->assertSee('/storage/seerah-events/gallery-one.jpg', false)
            ->assertSee('https://example.com/gallery-two.jpg', false)
            ->assertSee('openstreetmap.org/export/embed.html', false)
            ->assertSee('Localized Related Article')
            ->assertSee('/articles/locale-related-article', false);
    }

    public function test_absent_event_media_article_and_coordinates_render_no_optional_blocks(): void
    {
        $event = $this->makeEvent('event-without-public-data', [
            'featured_image' => null,
            'gallery' => null,
            'related_article_id' => null,
            'latitude' => null,
            'longitude' => null,
            'is_featured' => false,
        ]);

        $this->withSession(['locale' => 'en'])
            ->get(route('public.seerah.event', $event->slug))
            ->assertOk()
            ->assertDontSee(__('seerah.public.event_gallery'))
            ->assertDontSee(__('seerah.public.related_article'))
            ->assertDontSee('openstreetmap.org/export/embed.html', false)
            ->assertDontSee(__('seerah.public.event_location_map'));
    }

    public function test_draft_related_article_is_not_linked_and_cached_link_is_invalidated(): void
    {
        $article = $this->makeArticle('article-that-becomes-draft', 'Article That Becomes Draft');
        $event = $this->makeEvent('event-with-draft-article', [
            'related_article_id' => $article->id,
            'is_featured' => false,
        ]);
        $url = route('public.seerah.event', $event->slug);

        $this->withSession(['locale' => 'en'])->get($url)
            ->assertOk()
            ->assertSee('/articles/article-that-becomes-draft', false);

        $article->update(['status' => Article::STATUS_DRAFT]);

        $this->withSession(['locale' => 'en'])->get($url)
            ->assertOk()
            ->assertDontSee('/articles/article-that-becomes-draft', false)
            ->assertDontSee('Article That Becomes Draft');
    }

    public function test_related_events_prefer_same_phase_then_fill_with_featured_events(): void
    {
        $current = $this->makeEvent('related-current', [
            'phase' => 'mecca',
            'hijri_year' => 2,
            'is_featured' => false,
        ]);
        $samePhaseEarly = $this->makeEvent('related-same-early', [
            'phase' => 'mecca',
            'hijri_year' => 1,
            'is_featured' => false,
        ]);
        $samePhaseLate = $this->makeEvent('related-same-late', [
            'phase' => 'mecca',
            'hijri_year' => 3,
            'is_featured' => false,
        ]);
        $featuredFallback = $this->makeEvent('related-featured-fallback', [
            'phase' => 'hijra',
            'hijri_year' => 0,
            'is_featured' => true,
        ]);
        $this->makeEvent('related-irrelevant', [
            'phase' => 'hijra',
            'hijri_year' => 4,
            'is_featured' => false,
        ]);

        $this->withSession(['locale' => 'en'])
            ->get(route('public.seerah.event', $current->slug))
            ->assertOk()
            ->assertViewHas('related', fn (array $related): bool => array_column($related, 'slug') === [
                $samePhaseEarly->slug,
                $samePhaseLate->slug,
                $featuredFallback->slug,
            ]);
    }

    public function test_timeline_view_renders_the_extended_event_payload(): void
    {
        $article = $this->makeArticle('timeline-related-article', 'Timeline Related Article');
        $event = $this->makeEvent('timeline-extended-payload', [
            'featured_image' => 'seerah-events/timeline-hero.jpg',
            'gallery' => ['seerah-events/timeline-gallery.jpg'],
            'related_article_id' => $article->id,
            'latitude' => 24.4672,
            'longitude' => 39.6111,
            'is_featured' => false,
        ]);

        $this->withSession(['locale' => 'en'])
            ->get(route('public.seerah.event', ['slug' => $event->slug, 'view' => 'timeline']))
            ->assertOk()
            ->assertSee('/storage/seerah-events/timeline-hero.jpg', false)
            ->assertSee('/storage/seerah-events/timeline-gallery.jpg', false)
            ->assertSee('Timeline Related Article')
            ->assertSee('openstreetmap.org/export/embed.html', false);
    }

    public function test_editing_event_fields_invalidates_the_extended_public_payload(): void
    {
        $article = $this->makeArticle('updated-related-article', 'Updated Related Article');
        $event = $this->makeEvent('event-cache-refresh', [
            'featured_image' => 'seerah-events/old-hero.jpg',
            'gallery' => null,
            'related_article_id' => null,
            'latitude' => null,
            'longitude' => null,
            'is_featured' => false,
        ]);
        $url = route('public.seerah.event', $event->slug);

        $this->withSession(['locale' => 'en'])->get($url)
            ->assertOk()
            ->assertSee('/storage/seerah-events/old-hero.jpg', false)
            ->assertDontSee('Updated Related Article');

        app(SeerahEventRepositoryInterface::class)->update($event->id, [
            'featured_image' => 'seerah-events/new-hero.jpg',
            'gallery' => ['seerah-events/new-gallery.jpg'],
            'related_article_id' => $article->id,
            'latitude' => 21.4225,
            'longitude' => 39.8262,
        ]);

        $this->withSession(['locale' => 'en'])->get($url)
            ->assertOk()
            ->assertDontSee('/storage/seerah-events/old-hero.jpg', false)
            ->assertSee('/storage/seerah-events/new-hero.jpg', false)
            ->assertSee('/storage/seerah-events/new-gallery.jpg', false)
            ->assertSee('Updated Related Article')
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
                'seerah_event_type_id' => $type->id,
                'hijri_year' => $year,
            ]);
        }

        $event = $this->makeEvent("ssg-001-listing-{$mutation}", [
            'seerah_event_type_id' => $type->id,
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
            'seerah_event_type_id' => $type->id,
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
        $type = SeerahEventType::query()->create([
            'slug' => $slug,
            'is_active' => true,
        ]);
        $type->syncTranslations(['name' => ['ar' => $slug, 'en' => $slug]]);

        return $type;
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
