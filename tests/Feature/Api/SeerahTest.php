<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Seerah\Models\SeerahEventGalleryItem;
use App\Domain\Seerah\Models\SeerahEventMedia;
use App\Domain\Seerah\Models\SeerahEventType;
use App\Domain\Shared\Models\Location;
use App\Models\Article;
use App\Models\ArticleLocale;
use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SeerahTest extends TestCase
{
    use RefreshDatabase;

    private const LIST_ITEM_STRUCTURE = [
        'id', 'slug', 'title', 'description', 'hijri_year', 'hijri_month', 'hijri_day',
        'formatted_hijri_date', 'gregorian_date', 'formatted_gregorian_date', 'era', 'era_label',
        'status', 'status_label', 'order', 'event_type' => ['id', 'slug', 'name', 'icon', 'color', 'gradient'],
        'location', 'has_location', 'thumb_url', 'cover_url', 'created_at', 'updated_at',
    ];

    private const DETAIL_ONLY_KEYS = ['content', 'dispute_note', 'metadata', 'sources', 'gallery', 'media'];

    private const REMOVED_KEYS = [
        'hijri_date', 'gregorian_year', 'importance', 'importance_label', 'featured_image', 'views_count',
        'display_order', 'is_featured', 'participants', 'article', 'latitude', 'longitude',
        'event_type_label', 'event_type_icon',
    ];

    private SeerahEventType $type;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create(), ['api:access']);

        $this->type = SeerahEventType::query()->create([
            'slug' => 'battle',
            'locale' => ['ar' => 'غزوة', 'en' => 'Battle'],
            'metadata' => ['icon' => 'swords', 'color' => 'red', 'gradient' => 'from-red-500'],
            'is_active' => true,
        ]);
    }

    private function createSeerahEvent(array $overrides = []): SeerahEvent
    {
        return SeerahEvent::factory()->create(array_merge([
            'slug' => 'battle-of-badr',
            'event_type' => $this->type->id,
            'order' => 1,
            'hijri_year' => 2,
            'gregorian_date' => '0624-03-13',
            'era' => 'medina',
        ], $overrides));
    }

    private function assertListShape(array $item): void
    {
        foreach ([...self::DETAIL_ONLY_KEYS, ...self::REMOVED_KEYS] as $key) {
            $this->assertArrayNotHasKey($key, $item, "List item must not contain `{$key}`.");
        }
    }

    public function test_index_returns_list_shape(): void
    {
        $this->createSeerahEvent();

        $response = $this->getJson('/api/v1/seerah')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'data' => ['data' => ['*' => self::LIST_ITEM_STRUCTURE], 'meta']])
            ->assertJsonPath('data.data.0.slug', 'battle-of-badr')
            ->assertJsonPath('data.data.0.event_type.slug', 'battle')
            ->assertJsonPath('data.data.0.era', 'medina');

        $this->assertListShape($response->json('data.data.0'));
    }

    public function test_index_with_empty_database_returns_empty_data(): void
    {
        $this->getJson('/api/v1/seerah')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.data', []);
    }

    public function test_index_excludes_drafts(): void
    {
        $this->createSeerahEvent();
        SeerahEvent::factory()->draft()->create(['slug' => 'draft-event']);

        $this->getJson('/api/v1/seerah')
            ->assertOk()
            ->assertJsonCount(1, 'data.data');
    }

    public function test_index_filters_by_era(): void
    {
        $this->createSeerahEvent();
        $this->createSeerahEvent(['slug' => 'first-revelation', 'era' => 'mecca']);

        $this->getJson('/api/v1/seerah?era=mecca')
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.slug', 'first-revelation');

        $this->getJson('/api/v1/seerah?era=not-an-era')->assertStatus(422);
    }

    public function test_a_published_event_without_a_ready_translation_is_not_public(): void
    {
        $this->createSeerahEvent();
        $unready = $this->createSeerahEvent(['slug' => 'unready-event']);
        $unready->translations()->update(['is_ready' => false]);

        $this->getJson('/api/v1/seerah')
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.slug', 'battle-of-badr');

        $this->getJson('/api/v1/seerah/unready-event')->assertNotFound();
    }

    public function test_search_returns_list_shape(): void
    {
        $event = $this->createSeerahEvent();
        $event->translations()->where('locale', 'en')->update(['title' => 'Qx Badr']);

        // A 2-byte term takes the LIKE path: FULLTEXT cannot see rows that
        // RefreshDatabase never commits.
        $response = $this->getJson('/api/v1/seerah/search?q=Qx')
            ->assertOk()
            ->assertJsonStructure(['success', 'data' => ['data' => ['*' => self::LIST_ITEM_STRUCTURE]]]);

        $this->assertNotEmpty($response->json('data.data'));
        $this->assertListShape($response->json('data.data.0'));
    }

    public function test_search_requires_a_query(): void
    {
        $this->getJson('/api/v1/seerah/search')->assertUnprocessable();
    }

    public function test_show_returns_detail_shape_with_gallery_and_media(): void
    {
        $event = $this->createSeerahEvent();
        $event->translations()->where('locale', 'ar')->update([
            'metadata' => json_encode([['key' => 'المكان', 'value' => 'بدر']]),
            'sources' => json_encode([['title' => 'السيرة', 'author' => 'ابن هشام', 'reference' => '2/1']]),
        ]);
        SeerahEventGalleryItem::query()->create([
            'event_id' => $event->id, 'image' => 'seerah-events/gallery/badr.jpg', 'caption' => ['ar' => 'بدر'], 'order' => 0,
        ]);
        $media = Media::query()->create([
            'public_key' => (string) Str::ulid(),
            'filename' => 'badr.mp4',
            'original_filename' => 'badr.mp4',
            'path' => 'media/badr.mp4',
            'mime_type' => 'video/mp4',
            'size' => 1024,
            'disk' => 'public',
            'type' => 'video',
        ]);
        SeerahEventMedia::query()->create(['event_id' => $event->id, 'media_id' => $media->id]);

        DB::enableQueryLog();
        $response = $this->getJson('/api/v1/seerah/battle-of-badr');
        $mediaQueries = collect(DB::getQueryLog())
            ->filter(fn (array $query): bool => (bool) preg_match('/from [`"]media[`"]/i', $query['query']));
        DB::disableQueryLog();

        $response->assertOk()
            ->assertJsonStructure(['success', 'data' => [...self::LIST_ITEM_STRUCTURE, 'milestone', ...self::DETAIL_ONLY_KEYS]])
            ->assertJsonPath('data.gallery.0.image', Storage::disk('public')->url('seerah-events/gallery/badr.jpg'))
            ->assertJsonPath('data.gallery.0.caption', 'بدر')
            ->assertJsonPath('data.media.0.id', $media->id)
            ->assertJsonPath('data.media.0.url', route('public.articles.media', ['media' => $media->public_key]))
            ->assertJsonPath('data.metadata.0.key', 'المكان')
            ->assertJsonPath('data.sources.0.author', 'ابن هشام');

        foreach (self::REMOVED_KEYS as $key) {
            $this->assertArrayNotHasKey($key, $response->json('data'));
        }

        $this->assertCount(1, $mediaQueries, 'The show endpoint must resolve attached media in exactly one query.');
    }

    public function test_show_returns_seo_external_links_and_public_links_only(): void
    {
        $event = $this->createSeerahEvent();
        // Every locale row: the API request resolves its own locale.
        $event->translations->each->update([
            'seo_metadata' => ['title' => 'SEO title', 'description' => 'SEO description'],
            'external_links' => [['title' => 'Map', 'url' => 'https://example.com/map']],
        ]);
        $published = Article::factory()->create(['status' => 'published']);
        ArticleLocale::create(['article_id' => $published->id, 'locale' => 'ar', 'title' => 'Linked', 'slug' => 'linked-article', 'is_ready' => true]);
        ArticleLocale::create(['article_id' => $published->id, 'locale' => 'en', 'title' => 'Linked', 'slug' => 'linked-article-en', 'is_ready' => true]);
        $draft = Article::factory()->create(['status' => 'draft']);
        $event->links()->createMany([
            ['type' => 'article', 'target_id' => $published->id, 'order' => 0],
            ['type' => 'article', 'target_id' => $draft->id, 'order' => 1],
        ]);

        $this->getJson('/api/v1/seerah/battle-of-badr')
            ->assertOk()
            ->assertJsonPath('data.seo.title', 'SEO title')
            ->assertJsonPath('data.external_links.0.url', 'https://example.com/map')
            ->assertJsonCount(1, 'data.links.article')
            ->assertJsonPath('data.links.article.0.id', $published->id);
    }

    public function test_show_returns_404_for_unknown_slug(): void
    {
        $this->getJson('/api/v1/seerah/this-event-does-not-exist')
            ->assertNotFound()
            ->assertJsonPath('success', false);
    }

    public function test_show_returns_404_for_a_draft(): void
    {
        SeerahEvent::factory()->draft()->create(['slug' => 'draft-event']);

        $this->getJson('/api/v1/seerah/draft-event')->assertNotFound();
    }

    public function test_featured_endpoint_is_removed(): void
    {
        $this->createSeerahEvent();

        $this->getJson('/api/v1/seerah/featured')->assertNotFound();
    }

    public function test_by_type_returns_matching_events(): void
    {
        $this->createSeerahEvent();
        $other = SeerahEventType::query()->create(['slug' => 'treaty', 'locale' => ['ar' => 'صلح'], 'is_active' => true]);
        $this->createSeerahEvent(['slug' => 'hudaybiyyah', 'event_type' => $other->id]);

        $response = $this->getJson('/api/v1/seerah/type/battle')
            ->assertOk()
            ->assertJsonStructure(['data' => ['data' => ['*' => self::LIST_ITEM_STRUCTURE]]])
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.slug', 'battle-of-badr');

        $this->assertListShape($response->json('data.data.0'));
    }

    public function test_by_type_with_unknown_type_returns_empty_data(): void
    {
        $this->createSeerahEvent();

        $this->getJson('/api/v1/seerah/type/no-such-type')
            ->assertOk()
            ->assertJsonPath('data.data', []);
    }

    public function test_by_hijri_year_returns_matching_events(): void
    {
        $this->createSeerahEvent();
        $this->createSeerahEvent(['slug' => 'uhud', 'hijri_year' => 3]);

        $response = $this->getJson('/api/v1/seerah/hijri/2')
            ->assertOk()
            ->assertJsonStructure(['data' => ['data' => ['*' => self::LIST_ITEM_STRUCTURE]]])
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.hijri_year', 2);

        $this->assertListShape($response->json('data.data.0'));
    }

    public function test_by_gregorian_year_filters_by_date_range(): void
    {
        $this->createSeerahEvent();
        $this->createSeerahEvent(['slug' => 'uhud', 'gregorian_date' => '0625-03-23']);

        $response = $this->getJson('/api/v1/seerah/gregorian/624')
            ->assertOk()
            ->assertJsonStructure(['data' => ['data' => ['*' => self::LIST_ITEM_STRUCTURE]]])
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.slug', 'battle-of-badr');

        $this->assertListShape($response->json('data.data.0'));
    }

    public function test_map_returns_only_events_whose_place_has_coordinates(): void
    {
        $badr = Location::factory()->withNames(['ar' => 'بدر'])->create([
            'slug' => 'badr', 'latitude' => '23.7800000', 'longitude' => '38.7900000',
        ]);
        $nowhere = Location::factory()->withoutCoordinates()->create(['slug' => 'nowhere']);
        $this->createSeerahEvent(['location_id' => $badr->id]);
        $this->createSeerahEvent(['slug' => 'unplaced', 'location_id' => $nowhere->id]);
        $this->createSeerahEvent(['slug' => 'no-place']);

        $response = $this->getJson('/api/v1/seerah/map')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'battle-of-badr')
            ->assertJsonPath('data.0.has_location', true)
            ->assertJsonPath('data.0.location.slug', 'badr')
            ->assertJsonPath('data.0.location.latitude', 23.78);

        $this->assertListShape($response->json('data.0'));
    }

    public function test_every_endpoint_requires_a_token(): void
    {
        $this->app['auth']->forgetGuards();

        foreach ([
            '/api/v1/seerah',
            '/api/v1/seerah/search?q=badr',
            '/api/v1/seerah/battle-of-badr',
            '/api/v1/seerah/type/battle',
            '/api/v1/seerah/hijri/2',
            '/api/v1/seerah/gregorian/624',
            '/api/v1/seerah/map',
        ] as $uri) {
            $this->getJson($uri)->assertUnauthorized();
        }
    }

    public function test_invalid_per_page_is_rejected(): void
    {
        foreach ([
            '/api/v1/seerah',
            '/api/v1/seerah/search?q=badr&',
            '/api/v1/seerah/type/battle',
            '/api/v1/seerah/hijri/2',
            '/api/v1/seerah/gregorian/624',
        ] as $uri) {
            $separator = str_contains($uri, '?') ? '' : '?';

            foreach (['abc', '1000'] as $perPage) {
                $this->getJson("{$uri}{$separator}per_page={$perPage}")->assertUnprocessable();
            }
        }
    }
}
