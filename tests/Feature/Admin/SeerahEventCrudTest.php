<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Seerah\Models\SeerahEventType;
use App\Domain\Shared\Services\LanguageService;
use App\Models\Article;
use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SeerahEventCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        app(LanguageService::class)->updateSettings('fr', ['active' => true]);
        app(LanguageService::class)->updateSettings('ur', ['active' => true]);
    }

    public function test_admin_can_create_view_update_and_delete_seerah_events_with_multilingual_article_relations(): void
    {
        Storage::fake('public');

        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        $sourceArticle = $this->createArticleWithMedia($admin, 'الهجرة إلى المدينة', 'Migration to Madinah', 'migration');
        $updatedArticle = $this->createArticleWithMedia($admin, 'بناء المسجد النبوي', 'Building the Prophet Mosque', 'mosque');

        $battleTypeId = SeerahEventType::query()->where('slug', 'battle')->value('id');

        $createPayload = [
            'slug' => 'battle-of-badr',
            'title' => $this->localizedField('غزوة بدر', 'Battle of Badr'),
            'description' => $this->localizedField('وصف عربي للحدث', 'English event description'),
            'content' => $this->localizedField('تفاصيل عربية للحدث', 'English event content'),
            'dispute_note' => $this->localizedField('قيل سنة 2 هـ وقيل 3 هـ', 'Some place it in 2 AH, others in 3 AH'),
            'hijri_date' => '17 رمضان 2 هـ',
            'hijri_year' => 2,
            'hijri_month' => 9,
            'hijri_day' => 17,
            'gregorian_date' => '0624-03-13',
            'gregorian_year' => 624,
            'seerah_event_type_id' => $battleTypeId,
            'status' => 'published',
            'importance' => 'high',
            'location' => 'Badr',
            'latitude' => '23.7833',
            'longitude' => '38.7900',
            'related_article_id' => $sourceArticle->id,
            'display_order' => 1,
            'is_featured' => '1',
            'featured_image' => UploadedFile::fake()->image('badr-create.jpg', 1200, 630),
        ];

        $this->post(route('admin.seerah-events.store'), $createPayload)
            ->assertRedirect(route('admin.seerah-events.index'));

        $event = SeerahEvent::query()->where('slug', 'battle-of-badr')->firstOrFail();

        $this->assertSame('غزوة بدر', $event->getTranslation('title', 'ar'));
        $this->assertSame('Battle of Badr FR', $event->getTranslation('title', 'fr'));
        $this->assertSame('Battle of Badr UR', $event->getTranslation('title', 'ur'));
        $this->assertSame('قيل سنة 2 هـ وقيل 3 هـ', $event->getTranslation('dispute_note', 'ar'));
        $this->assertSame('Some place it in 2 AH, others in 3 AH', $event->getTranslation('dispute_note', 'en', false));
        $this->assertSame($sourceArticle->id, $event->related_article_id);
        $this->assertSame($battleTypeId, $event->seerah_event_type_id);
        $this->assertSame('battle', $event->type->slug);
        $this->assertSame('published', $event->status->value);
        $this->assertTrue($event->is_featured);
        $this->assertNotNull($event->featured_image);
        Storage::disk('public')->assertExists($event->featured_image);

        $this->get(route('admin.seerah-events.show', $event->id))
            ->assertOk()
            ->assertSee('غزوة بدر')
            ->assertSee('الهجرة إلى المدينة');

        $oldImagePath = $event->featured_image;

        $updatePayload = [
            'slug' => 'battle-of-badr',
            'title' => $this->localizedField('غزوة بدر الكبرى', 'The Great Battle of Badr'),
            'description' => $this->localizedField('وصف عربي محدث', 'Updated English description'),
            'content' => $this->localizedField('تفاصيل عربية محدثة', 'Updated English content'),
            'dispute_note' => $this->localizedField('الراجح 2 هـ', 'The stronger view is 2 AH'),
            'hijri_date' => '17 رمضان 2 هـ',
            'hijri_year' => 2,
            'hijri_month' => 9,
            'hijri_day' => 17,
            'gregorian_date' => '0624-03-13',
            'gregorian_year' => 624,
            'seerah_event_type_id' => $battleTypeId,
            'status' => 'published',
            'importance' => 'high',
            'location' => 'Badr Valley',
            'latitude' => '23.7900',
            'longitude' => '38.8000',
            'related_article_id' => $updatedArticle->id,
            'display_order' => 2,
            'is_featured' => '1',
            'featured_image' => UploadedFile::fake()->image('badr-update.jpg', 1200, 630),
        ];

        $this->put(route('admin.seerah-events.update', $event->id), $updatePayload)
            ->assertRedirect(route('admin.seerah-events.index'));

        $event->refresh();

        $this->assertSame('غزوة بدر الكبرى', $event->getTranslation('title', 'ar'));
        $this->assertSame('The Great Battle of Badr TR', $event->getTranslation('title', 'tr'));
        $this->assertSame('الراجح 2 هـ', $event->getTranslation('dispute_note', 'ar'));
        $this->assertSame('The stronger view is 2 AH', $event->getTranslation('dispute_note', 'en', false));
        $this->assertSame($updatedArticle->id, $event->related_article_id);
        $this->assertSame('Badr Valley', $event->location);
        $this->assertSame(2, $event->display_order);
        $this->assertNotSame($oldImagePath, $event->featured_image);
        Storage::disk('public')->assertMissing($oldImagePath);
        Storage::disk('public')->assertExists($event->featured_image);

        Sanctum::actingAs($admin, ['api:access']);
        $response = $this->getJson('/api/v1/seerah/battle-of-badr');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.slug', 'battle-of-badr')
            ->assertJsonPath('data.title.ar', 'غزوة بدر الكبرى')
            ->assertJsonPath('data.title.fr', 'The Great Battle of Badr FR')
            ->assertJsonPath('data.content.ur', 'Updated English content UR')
            ->assertJsonPath('data.article.id', $updatedArticle->id)
            ->assertJsonPath('data.article.locales.ar.title', 'بناء المسجد النبوي')
            ->assertJsonPath('data.article.locales.en.title', 'Building the Prophet Mosque')
            ->assertJsonPath('data.article.media_items.0.filename', 'mosque-media.jpg');

        $this->assertStringContainsString(
            'seerah-events/featured-images',
            (string) $response->json('data.featured_image')
        );

        $event->refresh();
        $this->assertSame(1, $event->views_count);

        $this->delete(route('admin.seerah-events.destroy', $event->id))
            ->assertRedirect(route('admin.seerah-events.index'));

        $this->assertDatabaseMissing('seerah_events', ['id' => $event->id]);
        Storage::disk('public')->assertMissing($event->featured_image);
    }

    public function test_show_displays_formatted_content_in_each_available_language(): void
    {
        $this->actingAs($this->createAdminUser());
        $event = SeerahEvent::factory()->create();
        $event->translations()->where('locale', 'ar')->update([
            'description' => '<p>ملخص <strong>الحدث</strong></p><script>alert("unsafe-description")</script>',
            'content' => '<h2>تفاصيل الحدث</h2><p>النص <strong>الكامل</strong></p><script>alert("unsafe")</script>',
        ]);
        $event->translations()->where('locale', 'en')->update([
            'content' => '<p>Full English content</p>',
        ]);

        $this->get(route('admin.seerah-events.show', $event->id))
            ->assertOk()
            ->assertSee('<p>ملخص <strong>الحدث</strong></p>', false)
            ->assertDontSee('<script>alert("unsafe-description")</script>', false)
            ->assertSee('المحتوى الكامل')
            ->assertSee('<h2>تفاصيل الحدث</h2>', false)
            ->assertSee('<strong>الكامل</strong>', false)
            ->assertSee('<p>Full English content</p>', false)
            ->assertSee('id="event-content-ar"', false)
            ->assertSee('id="event-content-en"', false)
            ->assertDontSee('<script>alert("unsafe")</script>', false);
    }

    public function test_show_uses_first_available_content_language_and_hides_empty_content(): void
    {
        $this->actingAs($this->createAdminUser());
        $event = SeerahEvent::factory()->create();
        $event->translations()->update(['content' => null, 'description' => null]);
        $event->translations()->where('locale', 'fr')->update(['content' => '<p>Contenu complet</p>']);

        $this->get(route('admin.seerah-events.show', $event->id))
            ->assertOk()
            ->assertSee("x-data=\"{ activeTab: 'fr' }\"", false)
            ->assertSee('<p>Contenu complet</p>', false)
            ->assertDontSee('id="event-content-ar"', false);

        $emptyEvent = SeerahEvent::factory()->create();
        $emptyEvent->translations()->update(['content' => null]);
        $this->get(route('admin.seerah-events.show', $emptyEvent->id))
            ->assertOk()->assertDontSee('id="event-content-heading"', false);
    }

    private function createArticleWithMedia(User $owner, string $arabicTitle, string $englishTitle, string $slugPrefix): Article
    {
        $article = Article::query()->create([
            'writer_id' => $owner->id,
            'read_time' => 6,
            'views' => 0,
            'is_featured' => false,
            'is_pinned' => false,
        ]);

        foreach ($this->localizedField($arabicTitle, $englishTitle) as $locale => $title) {
            $article->locales()->create([
                'locale' => $locale,
                'title' => $title,
                'slug' => $slugPrefix.'-'.$locale,
                'description' => $title.' description',
                'excerpt' => $title.' excerpt',
                'content' => [
                    'body' => '<p>'.$title.' body</p>',
                    'summary' => $title.' summary',
                    'toc' => [],
                ],
                'seo_metadata' => [
                    'title' => $title.' seo',
                    'description' => $title.' seo description',
                ],
                'is_ready' => true,
            ]);
        }

        $media = Media::query()->create([
            'filename' => $slugPrefix.'-media.jpg',
            'original_filename' => $slugPrefix.'-media.jpg',
            'path' => 'media/'.$slugPrefix.'-media.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 153600,
            'width' => 1200,
            'height' => 630,
            'disk' => 'public',
            'alt_text' => $englishTitle.' image',
            'caption' => $englishTitle.' image',
            'metadata' => ['source' => 'test'],
            'type' => 'image',
            'mediable_type' => User::class,
            'mediable_id' => $owner->id,
            'collection_name' => 'images',
            'tags' => [$slugPrefix],
        ]);

        $article->mediaItems()->sync([$media->id]);

        return $article->load(['locales', 'mediaItems']);
    }

    /**
     * @return array<string, string>
     */
    private function localizedField(string $arabic, string $english): array
    {
        return [
            'ar' => $arabic,
            'en' => $english,
            'fr' => $english.' FR',
            'tr' => $english.' TR',
            'ur' => $english.' UR',
        ];
    }
}
