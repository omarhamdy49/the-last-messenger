<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Blog\Models\Category;
use App\Domain\Blog\Models\Series;
use App\Domain\Blog\Models\Tag;
use App\Models\Article;
use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArticleSeriesIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $writer;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->admin = $this->createAdminUser();
        $this->writer = $this->createWriterUser();
        $this->actingAs($this->admin);
    }

    // -------------------------------------------------------------------------
    // Series CRUD
    // -------------------------------------------------------------------------

    public function test_admin_can_create_series(): void
    {
        $payload = [
            'title' => ['ar' => 'سلسلة السيرة', 'en' => 'Seerah Series'],
            'description' => ['ar' => 'وصف السلسلة', 'en' => 'Series description'],
            'slug' => 'seerah-series',
            'thumbnail' => UploadedFile::fake()->image('series-thumb.jpg', 800, 400),
        ];

        // SeriesController::store redirects to the new series' show page (matching
        // admin.media-programs.show), not the index. The assertion below was stale.
        $response = $this->post(route('admin.series.store'), $payload);
        $series = Series::query()->where('slug', 'seerah-series')->firstOrFail();
        $response->assertRedirect(route('admin.series.show', $series->id));

        $series = Series::query()->firstOrFail();

        $this->assertSame('seerah-series', $series->slug);
        $this->assertSame('سلسلة السيرة', $series->getTranslation('title', 'ar'));
        $this->assertSame('Seerah Series', $series->getTranslation('title', 'en'));
        $this->assertSame('وصف السلسلة', $series->getTranslation('description', 'ar'));
        $this->assertSame(0, $series->total_articles);
        Storage::disk('public')->assertExists($series->thumbnail);
    }

    public function test_admin_can_create_series_via_json(): void
    {
        $payload = [
            'title' => ['ar' => 'سلسلة الأحاديث', 'en' => 'Hadith Series'],
            'description' => ['ar' => 'وصف', 'en' => 'Description'],
        ];

        $response = $this->postJson(route('admin.series.store'), $payload);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.slug', 'hadith-series');
    }

    public function test_admin_can_update_series(): void
    {
        $series = $this->createSeries('original-slug', 'عنوان أصلي', 'Original Title');

        $payload = [
            'title' => ['ar' => 'عنوان محدث', 'en' => 'Updated Title'],
            'description' => ['ar' => 'وصف محدث', 'en' => 'Updated desc'],
        ];

        $this->put(route('admin.series.update', $series), $payload)
            ->assertRedirect(route('admin.series.show', $series->id));

        $series->refresh();
        $this->assertSame('عنوان محدث', $series->getTranslation('title', 'ar'));
        $this->assertSame('Updated Title', $series->getTranslation('title', 'en'));
    }

    public function test_admin_can_update_series_thumbnail(): void
    {
        $series = $this->createSeries('thumb-test');

        // Set initial thumbnail
        $initialThumb = UploadedFile::fake()->image('initial.jpg');
        $initialPath = $initialThumb->store('series/thumbnails', 'public');
        $series->update(['thumbnail' => $initialPath]);

        $payload = [
            'title' => ['ar' => 'عنوان', 'en' => 'Title'],
            'thumbnail' => UploadedFile::fake()->image('new-thumb.jpg', 800, 400),
        ];

        $this->put(route('admin.series.update', $series), $payload)
            ->assertRedirect(route('admin.series.show', $series->id));

        $series->refresh();
        $this->assertNotSame($initialPath, $series->thumbnail);
        Storage::disk('public')->assertMissing($initialPath);
        Storage::disk('public')->assertExists($series->thumbnail);
    }

    public function test_admin_can_delete_series(): void
    {
        $series = $this->createSeries('delete-test');

        // Attach an article to the series
        $article = $this->createArticleWithLocales();
        $series->articles()->attach($article->id, ['position' => 0]);

        $this->assertDatabaseHas('article_series', [
            'article_id' => $article->id,
            'series_id' => $series->id,
        ]);

        $this->delete(route('admin.series.destroy', $series))
            ->assertRedirect(route('admin.series.index'));

        $this->assertDatabaseMissing('series', ['id' => $series->id]);
        $this->assertDatabaseMissing('article_series', ['series_id' => $series->id]);
        // Article itself still exists
        $this->assertDatabaseHas('articles', ['id' => $article->id]);
    }

    public function test_admin_can_bulk_delete_series(): void
    {
        $series1 = $this->createSeries('bulk-1');
        $series2 = $this->createSeries('bulk-2');
        $series3 = $this->createSeries('bulk-3');

        // Attach articles
        $article = $this->createArticleWithLocales();
        $series1->articles()->attach($article->id, ['position' => 0]);
        $series2->articles()->attach($article->id, ['position' => 1]);

        $response = $this->postJson(route('admin.series.bulk-delete'), [
            'ids' => [$series1->id, $series2->id],
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('count', 2);

        $this->assertDatabaseMissing('series', ['id' => $series1->id]);
        $this->assertDatabaseMissing('series', ['id' => $series2->id]);
        $this->assertDatabaseHas('series', ['id' => $series3->id]);
        $this->assertDatabaseMissing('article_series', ['series_id' => $series1->id]);
        $this->assertDatabaseMissing('article_series', ['series_id' => $series2->id]);
    }

    public function test_series_index_returns_list_with_stats(): void
    {
        $this->createSeries('series-a');
        $this->createSeries('series-b');

        $response = $this->getJson(route('admin.series.index'));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('stats.total', 2)
            ->assertJsonCount(2, 'data');
    }

    // -------------------------------------------------------------------------
    // Article CRUD with Series assignment
    // -------------------------------------------------------------------------

    public function test_create_article_with_series_assignment(): void
    {
        $series1 = $this->createSeries('series-1');
        $series2 = $this->createSeries('series-2');
        [$cat] = $this->createCategories();

        $payload = $this->buildArticlePayload([
            'category_id' => $cat->id,
            'series' => [$series1->id, $series2->id],
        ]);

        $this->post(route('admin.articles.store'), $payload)
            ->assertRedirect(route('admin.articles.index'));

        $article = Article::query()->firstOrFail();

        $this->assertCount(2, $article->series);
        $this->assertDatabaseHas('article_series', [
            'article_id' => $article->id,
            'series_id' => $series1->id,
            'position' => 0,
        ]);
        $this->assertDatabaseHas('article_series', [
            'article_id' => $article->id,
            'series_id' => $series2->id,
            'position' => 1,
        ]);
    }

    public function test_update_article_changes_series_assignment(): void
    {
        $series1 = $this->createSeries('series-a');
        $series2 = $this->createSeries('series-b');
        $series3 = $this->createSeries('series-c');
        [$cat] = $this->createCategories();

        // Create article with series1 and series2
        $payload = $this->buildArticlePayload([
            'category_id' => $cat->id,
            'series' => [$series1->id, $series2->id],
        ]);
        $this->post(route('admin.articles.store'), $payload);
        $article = Article::query()->firstOrFail();

        $this->assertCount(2, $article->series);

        // Update to use series2 and series3 (drop series1, add series3)
        $updatePayload = $this->buildArticlePayload([
            'category_id' => $cat->id,
            'series' => [$series2->id, $series3->id],
        ], 'Updated');

        $this->put(route('admin.articles.update', $article), $updatePayload)
            ->assertRedirect(route('admin.articles.index'));

        $article->refresh()->load('series');
        $seriesIds = $article->series->pluck('id')->toArray();

        $this->assertContains($series2->id, $seriesIds);
        $this->assertContains($series3->id, $seriesIds);
        $this->assertNotContains($series1->id, $seriesIds);
        $this->assertCount(2, $seriesIds);
    }

    public function test_create_article_without_series(): void
    {
        [$cat] = $this->createCategories();

        $payload = $this->buildArticlePayload([
            'category_id' => $cat->id,
        ]);

        $this->post(route('admin.articles.store'), $payload)
            ->assertRedirect(route('admin.articles.index'));

        $article = Article::query()->firstOrFail();
        $this->assertCount(0, $article->series);
    }

    public function test_update_article_can_remove_all_series(): void
    {
        $series = $this->createSeries('remove-test');
        [$cat] = $this->createCategories();

        // Create with series
        $payload = $this->buildArticlePayload([
            'category_id' => $cat->id,
            'series' => [$series->id],
        ]);
        $this->post(route('admin.articles.store'), $payload);
        $article = Article::query()->firstOrFail();
        $this->assertCount(1, $article->series);

        // Update with empty series
        $updatePayload = $this->buildArticlePayload([
            'category_id' => $cat->id,
            'series' => [],
        ], 'Updated');

        $this->put(route('admin.articles.update', $article), $updatePayload)
            ->assertRedirect(route('admin.articles.index'));

        $article->refresh()->load('series');
        $this->assertCount(0, $article->series);
    }

    // -------------------------------------------------------------------------
    // Full article CRUD with all relations
    // -------------------------------------------------------------------------

    public function test_full_article_lifecycle_with_all_relations(): void
    {
        $series = $this->createSeries('lifecycle-series');
        [$primaryCat, $secondaryCat] = $this->createCategories();
        [$primaryTag, $secondaryTag] = $this->createTags();
        [$heroMedia, $galleryMedia] = $this->createMediaFixtures();

        // === CREATE ===
        $createPayload = $this->buildArticlePayload([
            'writer_id' => $this->writer->id,
            'category_id' => $primaryCat->id,
            'additional_categories' => [$secondaryCat->id],
            'tags' => [$primaryTag->id],
            'media_items' => [$heroMedia->id, $galleryMedia->id],
            'series' => [$series->id],
            'is_featured' => true,
            'is_pinned' => true,
            'read_time' => 8,
            'hijri_date' => '1 ربيع الأول 1 هـ',
            'thumb' => UploadedFile::fake()->image('thumb.jpg', 1200, 630),
        ]);

        $this->post(route('admin.articles.store'), $createPayload)
            ->assertRedirect(route('admin.articles.index'));

        $article = Article::query()
            ->with(['locales', 'categories', 'tags', 'mediaItems', 'series', 'writer'])
            ->firstOrFail();

        // Assert article attributes
        $this->assertTrue($article->is_featured);
        $this->assertTrue($article->is_pinned);
        $this->assertSame($this->writer->id, $article->writer_id);
        $this->assertSame($primaryCat->id, $article->category_id);
        Storage::disk('public')->assertExists($article->thumb);

        // Assert locales
        $this->assertCount(5, $article->locales);
        $this->assertSame('المقالة الأولى', $article->locales->firstWhere('locale', 'ar')?->title);
        $this->assertSame('First Article', $article->locales->firstWhere('locale', 'en')?->title);

        // Assert relations
        $this->assertCount(2, $article->categories);
        $this->assertCount(1, $article->tags);
        $this->assertCount(2, $article->mediaItems);
        $this->assertCount(1, $article->series);
        $this->assertSame($series->id, $article->series->first()->id);
        $this->assertEquals(0, $article->series->first()->pivot->position);

        // Assert pivot
        $this->assertDatabaseHas('article_series', [
            'article_id' => $article->id,
            'series_id' => $series->id,
            'position' => 0,
        ]);
        $this->assertDatabaseHas('articles_media', [
            'article_id' => $article->id,
            'media_id' => $heroMedia->id,
        ]);

        // === UPDATE ===
        $series2 = $this->createSeries('updated-series');
        $oldThumb = $article->thumb;

        $updatePayload = $this->buildArticlePayload([
            'writer_id' => $this->writer->id,
            'category_id' => $secondaryCat->id,
            'additional_categories' => [$primaryCat->id],
            'tags' => [$secondaryTag->id],
            'media_items' => [$galleryMedia->id],
            'series' => [$series2->id],
            'is_featured' => false,
            'is_pinned' => false,
            'read_time' => 12,
            'hijri_date' => '2 ربيع الأول 1 هـ',
            'thumb' => UploadedFile::fake()->image('new-thumb.jpg', 1200, 630),
        ], 'Updated');

        $this->put(route('admin.articles.update', $article), $updatePayload)
            ->assertRedirect(route('admin.articles.index'));

        $article->refresh()->load(['locales', 'categories', 'tags', 'mediaItems', 'series']);

        $this->assertFalse($article->is_featured);
        $this->assertFalse($article->is_pinned);
        $this->assertSame($secondaryCat->id, $article->category_id);

        // Thumb replaced
        $this->assertNotSame($oldThumb, $article->thumb);
        Storage::disk('public')->assertMissing($oldThumb);
        Storage::disk('public')->assertExists($article->thumb);

        // Relations updated
        $this->assertCount(2, $article->categories);
        $this->assertSame($secondaryTag->id, $article->tags->sole()->id);
        $this->assertCount(1, $article->mediaItems);
        $this->assertSame($galleryMedia->id, $article->mediaItems->sole()->id);

        // Series updated
        $this->assertCount(1, $article->series);
        $this->assertSame($series2->id, $article->series->first()->id);
        $this->assertDatabaseMissing('article_series', [
            'article_id' => $article->id,
            'series_id' => $series->id,
        ]);

        // Locales updated
        $this->assertSame('المقالة المحدثة', $article->locales->firstWhere('locale', 'ar')?->title);

        // === DELETE ===
        $this->delete(route('admin.articles.destroy', $article))
            ->assertRedirect(route('admin.articles.index'));

        $this->assertSoftDeleted('articles', ['id' => $article->id]);
    }

    // -------------------------------------------------------------------------
    // Article-Series pivot position ordering
    // -------------------------------------------------------------------------

    public function test_articles_in_series_maintain_position_order(): void
    {
        $series = $this->createSeries('ordered-series');

        $article1 = $this->createArticleWithLocales();
        $article2 = $this->createArticleWithLocales();
        $article3 = $this->createArticleWithLocales();

        $series->articles()->attach([
            $article1->id => ['position' => 2],
            $article2->id => ['position' => 0],
            $article3->id => ['position' => 1],
        ]);

        $orderedArticles = $series->articles()->get();

        $this->assertSame($article2->id, $orderedArticles[0]->id);
        $this->assertSame($article3->id, $orderedArticles[1]->id);
        $this->assertSame($article1->id, $orderedArticles[2]->id);
    }

    public function test_article_can_belong_to_multiple_series(): void
    {
        $series1 = $this->createSeries('multi-1');
        $series2 = $this->createSeries('multi-2');
        $series3 = $this->createSeries('multi-3');

        $article = $this->createArticleWithLocales();

        $article->series()->attach([
            $series1->id => ['position' => 0],
            $series2->id => ['position' => 1],
            $series3->id => ['position' => 0],
        ]);

        $this->assertCount(3, $article->series);
        $this->assertDatabaseHas('article_series', [
            'article_id' => $article->id,
            'series_id' => $series1->id,
        ]);
        $this->assertDatabaseHas('article_series', [
            'article_id' => $article->id,
            'series_id' => $series3->id,
        ]);
    }

    // -------------------------------------------------------------------------
    // Cascade behavior
    // -------------------------------------------------------------------------

    public function test_deleting_series_cascades_to_pivot(): void
    {
        $series = $this->createSeries('cascade-test');
        $article = $this->createArticleWithLocales();

        $series->articles()->attach($article->id, ['position' => 0]);
        $this->assertDatabaseHas('article_series', ['series_id' => $series->id]);

        $series->articles()->detach();
        $series->delete();

        $this->assertDatabaseMissing('article_series', ['series_id' => $series->id]);
        $this->assertDatabaseHas('articles', ['id' => $article->id]);
    }

    public function test_force_deleting_article_cascades_to_pivot(): void
    {
        $series = $this->createSeries('article-cascade');
        $article = $this->createArticleWithLocales();

        $series->articles()->attach($article->id, ['position' => 0]);
        $this->assertDatabaseHas('article_series', ['article_id' => $article->id]);

        $article->forceDelete();

        $this->assertDatabaseMissing('article_series', ['article_id' => $article->id]);
        $this->assertDatabaseHas('series', ['id' => $series->id]);
    }

    // -------------------------------------------------------------------------
    // Series article count
    // -------------------------------------------------------------------------

    public function test_series_can_update_articles_count(): void
    {
        $series = $this->createSeries('count-test');
        $article1 = $this->createArticleWithLocales();
        $article2 = $this->createArticleWithLocales();

        $series->articles()->attach([
            $article1->id => ['position' => 0],
            $article2->id => ['position' => 1],
        ]);

        $series->updateArticlesCount();
        $series->refresh();

        $this->assertSame(2, $series->total_articles);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createSeries(string $slug, string $arTitle = 'سلسلة', string $enTitle = 'Series'): Series
    {
        return Series::query()->create([
            'slug' => $slug,
            'title' => ['ar' => $arTitle, 'en' => $enTitle],
            'description' => ['ar' => 'وصف', 'en' => 'Description'],
            'total_articles' => 0,
        ]);
    }

    private function createArticleWithLocales(): Article
    {
        $article = Article::query()->create([
            'writer_id' => $this->writer->id,
            'read_time' => 5,
            'views' => 0,
            'is_featured' => false,
            'is_pinned' => false,
            'status' => 'draft',
        ]);

        $article->locales()->create([
            'locale' => 'ar',
            'title' => 'مقالة اختبار '.$article->id,
            'slug' => 'test-article-ar-'.$article->id,
            'content' => ['body' => '<p>محتوى</p>'],
            'is_ready' => true,
        ]);

        $article->locales()->create([
            'locale' => 'en',
            'title' => 'Test Article '.$article->id,
            'slug' => 'test-article-en-'.$article->id,
            'content' => ['body' => '<p>Content</p>'],
            'is_ready' => true,
        ]);

        return $article;
    }

    /**
     * @return array{0: Category, 1: Category}
     */
    private function createCategories(): array
    {
        $primary = Category::query()->create([
            'slug' => 'seerah-'.uniqid(),
            'type' => 'blog',
            'status' => 'active',
            'name' => ['ar' => 'السيرة', 'en' => 'Seerah'],
            'description' => ['ar' => 'تصنيف', 'en' => 'Category'],
            'lft' => 1,
            'rgt' => 4,
            'depth' => 0,
            'display_order' => 1,
        ]);

        $secondary = Category::query()->create([
            'slug' => 'timeline-'.uniqid(),
            'type' => 'blog',
            'status' => 'active',
            'name' => ['ar' => 'الخط الزمني', 'en' => 'Timeline'],
            'description' => ['ar' => 'تصنيف', 'en' => 'Category'],
            'lft' => 5,
            'rgt' => 8,
            'depth' => 0,
            'display_order' => 2,
        ]);

        return [$primary, $secondary];
    }

    /**
     * @return array{0: Tag, 1: Tag}
     */
    private function createTags(): array
    {
        $primary = Tag::query()->create([
            'slug' => 'migration-'.uniqid(),
            'name' => ['ar' => 'الهجرة', 'en' => 'Migration'],
            'description' => ['ar' => 'وسم', 'en' => 'Tag'],
            'type' => 'article',
            'usage_count' => 0,
        ]);

        $secondary = Tag::query()->create([
            'slug' => 'madinah-'.uniqid(),
            'name' => ['ar' => 'المدينة', 'en' => 'Madinah'],
            'description' => ['ar' => 'وسم', 'en' => 'Tag'],
            'type' => 'article',
            'usage_count' => 0,
        ]);

        return [$primary, $secondary];
    }

    /**
     * @return array{0: Media, 1: Media}
     */
    private function createMediaFixtures(): array
    {
        $hero = Media::query()->create([
            'filename' => 'hero-'.uniqid().'.jpg',
            'original_filename' => 'hero.jpg',
            'path' => 'media/hero.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 204800,
            'width' => 1200,
            'height' => 630,
            'disk' => 'public',
            'alt_text' => 'Hero image',
            'caption' => 'Hero',
            'metadata' => ['source' => 'test'],
            'type' => 'image',
            'mediable_type' => User::class,
            'mediable_id' => $this->admin->id,
            'collection_name' => 'images',
            'tags' => ['test'],
        ]);

        $gallery = Media::query()->create([
            'filename' => 'gallery-'.uniqid().'.jpg',
            'original_filename' => 'gallery.jpg',
            'path' => 'media/gallery.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 102400,
            'width' => 800,
            'height' => 600,
            'disk' => 'public',
            'alt_text' => 'Gallery image',
            'caption' => 'Gallery',
            'metadata' => ['source' => 'test'],
            'type' => 'image',
            'mediable_type' => User::class,
            'mediable_id' => $this->admin->id,
            'collection_name' => 'images',
            'tags' => ['test'],
        ]);

        return [$hero, $gallery];
    }

    private function buildArticlePayload(array $overrides = [], string $prefix = ''): array
    {
        $arTitle = $prefix ? 'المقالة المحدثة' : 'المقالة الأولى';
        $enTitle = $prefix ? "{$prefix} Article" : 'First Article';

        return array_merge([
            'title' => $this->localizedField($arTitle, $enTitle),
            'content' => $this->localizedField('<h2>عنوان</h2><p>محتوى</p>', '<h2>Heading</h2><p>Content</p>'),
            'excerpt' => $this->localizedField('ملخص', 'Excerpt'),
            'description' => $this->localizedField('وصف', 'Description'),
            'summary' => $this->localizedField('خلاصة', 'Summary'),
            'seo_title' => $this->localizedField('SEO عربي', 'SEO English'),
            'seo_description' => $this->localizedField('وصف SEO', 'SEO Description'),
            'is_ready' => [
                'ar' => true,
                'en' => true,
                'fr' => true,
                'tr' => true,
                'ur' => false,
            ],
            'writer_id' => $this->writer->id,
            'is_featured' => false,
            'is_pinned' => false,
        ], $overrides);
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
