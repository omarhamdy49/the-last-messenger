<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Blog\Models\Category;
use App\Domain\Blog\Models\Tag;
use App\Models\Article;
use App\Models\ArticleLocale;
use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArticleCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_view_and_delete_articles_with_multilingual_media_relations(): void
    {
        Storage::fake('public');

        $admin = $this->createAdminUser();
        $writer = $this->createWriterUser();
        $this->actingAs($admin);

        [$primaryCategory, $secondaryCategory] = $this->createCategories();
        [$primaryTag, $secondaryTag] = $this->createTags();
        [$heroMedia, $galleryMedia] = $this->createMediaFixtures($admin);

        $createPayload = [
            'title' => $this->localizedField('الهجرة إلى المدينة', 'Migration to Madinah'),
            'content' => $this->localizedField('<h2>مقدمة</h2><p>محتوى عربي كامل</p>', '<h2>Introduction</h2><p>Full English content</p>'),
            'excerpt' => $this->localizedField('ملخص عربي قصير', 'Short English excerpt'),
            'description' => $this->localizedField('وصف عربي للمقال', 'English article description'),
            'summary' => $this->localizedField('خلاصة عربية', 'English summary'),
            'seo_title' => $this->localizedField('عنوان SEO عربي', 'English SEO title'),
            'seo_description' => $this->localizedField('وصف SEO عربي', 'English SEO description'),
            'is_ready' => [
                'ar' => true,
                'en' => true,
                'fr' => true,
                'tr' => true,
                'ur' => false,
            ],
            'writer_id' => $writer->id,
            'category_id' => $primaryCategory->id,
            'additional_categories' => [$secondaryCategory->id],
            'tags' => [$primaryTag->id],
            'media_items' => [$heroMedia->id, $galleryMedia->id],
            'is_featured' => true,
            'is_pinned' => true,
            'read_time' => 8,
            'hijri_date' => '1 ربيع الأول 1 هـ',
            'thumb' => UploadedFile::fake()->image('article-thumb.jpg', 1200, 630),
        ];

        $this->post(route('admin.articles.store'), $createPayload)
            ->assertRedirect(route('admin.articles.index'));

        $article = Article::query()
            ->with(['locales', 'categories', 'tags', 'mediaItems', 'writer'])
            ->firstOrFail();

        $this->assertTrue($article->is_featured);
        $this->assertTrue($article->is_pinned);
        $this->assertSame($writer->id, $article->writer_id);
        $this->assertSame($primaryCategory->id, $article->category_id);
        $this->assertCount(5, $article->locales);
        $this->assertCount(2, $article->categories);
        $this->assertCount(1, $article->tags);
        $this->assertCount(2, $article->mediaItems);
        $this->assertSame('Migration to Madinah', $article->locales->firstWhere('locale', 'en')?->title);
        $this->assertSame('Migration to Madinah FR', $article->locales->firstWhere('locale', 'fr')?->title);
        $this->assertSame('Migration to Madinah TR', $article->locales->firstWhere('locale', 'tr')?->title);
        $this->assertSame('Migration to Madinah UR', $article->locales->firstWhere('locale', 'ur')?->title);
        $this->assertSame(
            'English SEO description FR',
            $article->locales->firstWhere('locale', 'fr')?->seo_description
        );
        $this->assertDatabaseHas('articles_media', [
            'article_id' => $article->id,
            'media_id' => $heroMedia->id,
        ]);
        Storage::disk('public')->assertExists($article->thumb);

        $showResponse = $this->getJson(route('admin.articles.show', $article));

        $showResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.writer.id', $writer->id)
            ->assertJsonPath('data.locales.ar.title', 'الهجرة إلى المدينة')
            ->assertJsonPath('data.locales.fr.title', 'Migration to Madinah FR')
            ->assertJsonPath('data.locales.tr.seo_title', 'English SEO title TR')
            ->assertJsonPath('data.locales.ur.is_ready', false)
            ->assertJsonCount(2, 'data.media_items')
            ->assertJsonPath('data.urls.preview', route('admin.articles.preview', $article));
        $showResponse->assertJsonFragment(['filename' => 'migration-cover.jpg']);
        $showResponse->assertJsonFragment(['filename' => 'migration-map.jpg']);

        $oldThumbPath = $article->thumb;

        $updatePayload = [
            'title' => $this->localizedField('الهجرة المباركة', 'The Blessed Migration'),
            'content' => $this->localizedField('<h2>تحديث</h2><p>محتوى عربي محدث</p>', '<h2>Update</h2><p>Updated English content</p>'),
            'excerpt' => $this->localizedField('ملخص عربي محدث', 'Updated English excerpt'),
            'description' => $this->localizedField('وصف عربي محدث', 'Updated English article description'),
            'summary' => $this->localizedField('خلاصة عربية محدثة', 'Updated English summary'),
            'seo_title' => $this->localizedField('عنوان SEO عربي محدث', 'Updated English SEO title'),
            'seo_description' => $this->localizedField('وصف SEO عربي محدث', 'Updated English SEO description'),
            'is_ready' => [
                'ar' => true,
                'en' => true,
                'fr' => true,
                'tr' => false,
                'ur' => true,
            ],
            'writer_id' => $writer->id,
            'category_id' => $secondaryCategory->id,
            'additional_categories' => [$primaryCategory->id],
            'tags' => [$secondaryTag->id],
            'media_items' => [$galleryMedia->id],
            'is_featured' => false,
            'is_pinned' => false,
            'read_time' => 10,
            'hijri_date' => '2 ربيع الأول 1 هـ',
            'thumb' => UploadedFile::fake()->image('article-thumb-updated.jpg', 1200, 630),
        ];

        $this->put(route('admin.articles.update', $article), $updatePayload)
            ->assertRedirect(route('admin.articles.index'));

        $article->refresh()->load(['locales', 'categories', 'tags', 'mediaItems']);
        $arabicLocale = ArticleLocale::query()
            ->where('article_id', $article->id)
            ->where('locale', 'ar')
            ->firstOrFail();

        $this->assertFalse($article->is_featured);
        $this->assertFalse($article->is_pinned);
        $this->assertSame($secondaryCategory->id, $article->category_id);
        $this->assertSame('الهجرة المباركة', $arabicLocale->title);
        $this->assertSame('Updated English SEO description UR', $article->locales->firstWhere('locale', 'ur')?->seo_description);
        $this->assertFalse((bool) $article->locales->firstWhere('locale', 'tr')?->is_ready);
        $this->assertCount(2, $article->categories);
        $this->assertSame($secondaryTag->id, $article->tags->sole()->id);
        $this->assertCount(1, $article->mediaItems);
        $this->assertSame($galleryMedia->id, $article->mediaItems->sole()->id);
        $this->assertNotSame($oldThumbPath, $article->thumb);
        Storage::disk('public')->assertMissing($oldThumbPath);
        Storage::disk('public')->assertExists($article->thumb);

        $updatedShowResponse = $this->getJson(route('admin.articles.show', $article));

        $updatedShowResponse->assertOk()
            ->assertJsonPath('data.locales.en.title', 'The Blessed Migration')
            ->assertJsonPath('data.locales.ur.seo_description', 'Updated English SEO description UR')
            ->assertJsonPath('data.locales.tr.is_ready', false)
            ->assertJsonCount(1, 'data.media_items')
            ->assertJsonPath('data.media_items.0.id', $galleryMedia->id);

        $this->delete(route('admin.articles.destroy', $article))
            ->assertRedirect(route('admin.articles.index'));

        $this->assertSoftDeleted('articles', ['id' => $article->id]);
    }

    /**
     * @return array{0: Category, 1: Category}
     */
    private function createCategories(): array
    {
        $primaryCategory = Category::query()->create([
            'slug' => 'seerah',
            'type' => 'blog',
            'status' => 'active',
            'name' => ['ar' => 'السيرة', 'en' => 'Seerah'],
            'description' => ['ar' => 'تصنيف السيرة', 'en' => 'Seerah category'],
            'lft' => 1,
            'rgt' => 4,
            'depth' => 0,
            'display_order' => 1,
        ]);

        $secondaryCategory = Category::query()->create([
            'slug' => 'timeline',
            'type' => 'blog',
            'status' => 'active',
            'name' => ['ar' => 'الخط الزمني', 'en' => 'Timeline'],
            'description' => ['ar' => 'تصنيف زمني', 'en' => 'Timeline category'],
            'lft' => 5,
            'rgt' => 8,
            'depth' => 0,
            'display_order' => 2,
        ]);

        return [$primaryCategory, $secondaryCategory];
    }

    /**
     * @return array{0: Tag, 1: Tag}
     */
    private function createTags(): array
    {
        $primaryTag = Tag::query()->create([
            'slug' => 'migration',
            'name' => ['ar' => 'الهجرة', 'en' => 'Migration'],
            'description' => ['ar' => 'وسم الهجرة', 'en' => 'Migration tag'],
            'type' => 'article',
            'usage_count' => 0,
        ]);

        $secondaryTag = Tag::query()->create([
            'slug' => 'madinah',
            'name' => ['ar' => 'المدينة', 'en' => 'Madinah'],
            'description' => ['ar' => 'وسم المدينة', 'en' => 'Madinah tag'],
            'type' => 'article',
            'usage_count' => 0,
        ]);

        return [$primaryTag, $secondaryTag];
    }

    /**
     * @return array{0: Media, 1: Media}
     */
    private function createMediaFixtures(User $owner): array
    {
        $heroMedia = Media::query()->create([
            'filename' => 'migration-cover.jpg',
            'original_filename' => 'migration-cover.jpg',
            'path' => 'media/migration-cover.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 204800,
            'width' => 1200,
            'height' => 630,
            'disk' => 'public',
            'alt_text' => 'Migration cover',
            'caption' => 'Main migration cover',
            'metadata' => ['source' => 'test'],
            'type' => 'image',
            'mediable_type' => User::class,
            'mediable_id' => $owner->id,
            'collection_name' => 'images',
            'tags' => ['migration', 'featured'],
        ]);

        $galleryMedia = Media::query()->create([
            'filename' => 'migration-map.jpg',
            'original_filename' => 'migration-map.jpg',
            'path' => 'media/migration-map.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 102400,
            'width' => 800,
            'height' => 600,
            'disk' => 'public',
            'alt_text' => 'Migration map',
            'caption' => 'Route map',
            'metadata' => ['source' => 'test'],
            'type' => 'image',
            'mediable_type' => User::class,
            'mediable_id' => $owner->id,
            'collection_name' => 'images',
            'tags' => ['map'],
        ]);

        return [$heroMedia, $galleryMedia];
    }

    /**
     * @return array<string, string>
     */
    private function localizedField(string $arabic, string $english): array
    {
        return [
            'ar' => $arabic,
            'en' => $english,
            'fr' => $english . ' FR',
            'tr' => $english . ' TR',
            'ur' => $english . ' UR',
        ];
    }
}
