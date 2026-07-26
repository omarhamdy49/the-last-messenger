<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Seerah\Models\SeerahEventTimelineItem;
use App\Domain\Seerah\Models\SeerahEventType;
use App\Domain\Seerah\Models\SeerahParticipant;
use App\Domain\Seerah\Repositories\SeerahEventRepositoryInterface;
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
