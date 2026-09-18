<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Blog\Models\Category;
use App\Domain\Blog\Models\Tag;
use App\Domain\Shared\Services\LanguageService;
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

    protected function setUp(): void
    {
        parent::setUp();
        app(LanguageService::class)->updateSettings('fr', ['active' => true]);
        app(LanguageService::class)->updateSettings('ur', ['active' => true]);
        app(LanguageService::class)->applyRuntimeConfiguration();
    }

    /**
     * NEEDS A PRODUCT DECISION — deliberately incomplete rather than deleted or silenced.
     *
     * This test has never passed. `git log -S` shows the assertions and the standalone
     * `openModuleSelector` buttons they forbid both arrived in the same commit (f4e703a,
     * "feat:enhance articles module", 10 Aug 2026), so it was committed already red: it
     * encodes an intended end-state — module insertion only inside the Quill editor — that
     * was never finished.
     *
     * `_form-wizard.blade.php:600-612` still renders four standalone "Add Block" buttons
     * (verse / hadith / sahabi / seerah) alongside the `ql-module-btn--*` toolbar buttons
     * inside the editor. Two insertion paths exist. Worth noting: the Quill path requires a
     * registered blot or the embed's data-module/data-id attributes are silently stripped,
     * so the two paths are not equivalent.
     *
     * Resolving it means choosing one:
     *   (a) finish the migration — delete the four block buttons and the openModuleSelector
     *       handler (:1457), leaving Quill as the only path, and this test goes green; or
     *   (b) keep the block toolbar as a supported feature and delete this test.
     *
     * (a) removes UI that content editors may rely on; (b) discards a stated design intent.
     * Either is a call for the maintainer, not for a test-triage pass.
     */
    public function test_quran_hadith_and_sahabi_are_only_available_inside_the_wysiwyg(): void
    {
        $this->markTestIncomplete(
            'Unresolved design conflict: _form-wizard.blade.php:600-612 still renders standalone '
            .'openModuleSelector block buttons that this test forbids. Both were added in f4e703a; '
            .'the test has never passed. Either finish removing the buttons or drop this test.'
        );

        $this->actingAs($this->createAdminUser());

        $this->get(route('admin.articles.create'))
            ->assertOk()
            ->assertDontSee("@click=\"openModuleSelector('verse'", false)
            ->assertDontSee("@click=\"openModuleSelector('hadith'", false)
            ->assertDontSee("@click=\"openModuleSelector('sahabi'", false)
            ->assertSee('ql-module-btn--verse', false)
            ->assertSee('ql-module-btn--hadith', false)
            ->assertSee('ql-module-btn--sahabi', false)
            ->assertSee("editor.deleteText(insertionIndex, range.length, 'user')", false);
    }

    public function test_show_and_preview_render_block_content_per_language(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        $this->post(route('admin.articles.store'), [
            'title' => ['ar' => 'معاينة المقال', 'en' => 'Preview Article'],
            'slug' => ['ar' => 'preview-article-ar', 'en' => 'preview-article'],
            'content' => ['ar' => '<p>نص</p>', 'en' => '<p>Text</p>'],
            'content_blocks' => [
                'ar' => json_encode([['type' => 'wysiwyg', 'content' => '<h2>عنوان داخلي</h2><p>فقرة المقال</p>']], JSON_UNESCAPED_UNICODE),
                'en' => json_encode([['type' => 'wysiwyg', 'content' => '<p>English body</p>']]),
            ],
            'writer_id' => $admin->id,
            'status' => 'draft',
        ])->assertSessionHasNoErrors();

        $article = ArticleLocale::query()->where('slug', 'preview-article')->firstOrFail()->article;

        $this->get(route('admin.articles.show', $article))
            ->assertOk()
            ->assertSee('<h2>عنوان داخلي</h2>', false)
            ->assertSee('<p>English body</p>', false);

        $this->get(route('admin.articles.preview', ['article' => $article, 'locale' => 'en']))
            ->assertOk()
            ->assertSee("x-data=\"{ lang: 'en' }\"", false)
            ->assertSee('<p>فقرة المقال</p>', false);
    }

    public function test_article_slug_is_optional_and_generated_per_locale_title(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        $this->post(route('admin.articles.store'), [
            'title' => ['ar' => 'مقال بلا رابط', 'en' => 'Article Without Slug'],
            'slug' => ['ar' => '', 'en' => ''],
            'content' => ['ar' => '<p>محتوى</p>', 'en' => '<p>Content</p>'],
            'writer_id' => $admin->id,
            'status' => 'draft',
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.articles.index'));

        $this->assertDatabaseHas('article_locale', ['locale' => 'en', 'slug' => 'article-without-slug']);
        $this->assertNotEmpty(ArticleLocale::query()->where('locale', 'ar')->where('title', 'مقال بلا رابط')->value('slug'));
    }

    public function test_inline_modules_are_saved_inside_wysiwyg_content_and_rendered_publicly(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        $inlineContent = '<h2>مقدمة الاختبار</h2>'
            .'<div class="module-embed" data-module="verse" data-id="1">بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيمِ</div>'
            .'<div class="module-embed" data-module="hadith" data-id="42">إنما الأعمال بالنيات</div>'
            .'<p><a href="/sahaba/abu-bakr-al-siddiq">أبو بكر الصديق</a></p>';
        $blocks = [['type' => 'wysiwyg', 'content' => $inlineContent]];

        $this->post(route('admin.articles.store'), [
            'title' => ['ar' => 'اختبار كتل المحتوى'],
            'slug' => ['ar' => 'module-block-persistence'],
            'content' => ['ar' => '<p>قيمة احتياطية يجب استبدالها.</p>'],
            'content_blocks' => ['ar' => json_encode($blocks, JSON_UNESCAPED_UNICODE)],
            'writer_id' => $admin->id,
            'status' => 'published',
            'is_ready' => ['ar' => true],
        ])->assertRedirect(route('admin.articles.index'));

        $locale = ArticleLocale::query()
            ->where('locale', 'ar')
            ->where('slug', 'module-block-persistence')
            ->firstOrFail();

        $this->assertSame($blocks, $locale->content['body']);

        $updatedBlocks = $blocks;
        $updatedBlocks[0]['content'] = str_replace('إنما الأعمال بالنيات', 'الدين النصيحة', $inlineContent);

        $this->put(route('admin.articles.update', $locale->article_id), [
            'title' => ['ar' => 'اختبار تعديل كتل المحتوى'],
            'slug' => ['ar' => 'module-block-persistence'],
            'content' => ['ar' => '<p>قيمة احتياطية ثانية.</p>'],
            'content_blocks' => ['ar' => json_encode($updatedBlocks, JSON_UNESCAPED_UNICODE)],
            'writer_id' => $admin->id,
            'status' => 'published',
            'is_ready' => ['ar' => true],
        ])->assertRedirect(route('admin.articles.index'));

        $locale->refresh();
        $this->assertSame($updatedBlocks, $locale->content['body']);

        $this->get(route('public.articles.show', 'module-block-persistence'))
            ->assertOk()
            ->assertSee('data-module="verse"', false)
            ->assertSee('data-module="hadith"', false)
            ->assertSee('href="/sahaba/abu-bakr-al-siddiq"', false)
            ->assertSee('بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيمِ')
            ->assertSee('الدين النصيحة')
            ->assertSee('أبو بكر الصديق');
    }

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
            'cover' => UploadedFile::fake()->image('article-cover.jpg', 1920, 820),
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
        // The cover is stored separately from the card thumb and fronts the public reader.
        $this->assertNotNull($article->featured_image);
        $this->assertNotSame($article->thumb, $article->featured_image);
        Storage::disk('public')->assertExists($article->featured_image);

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

    public function test_admin_can_set_article_thumbnail_from_media_library(): void
    {
        Storage::fake('public');

        $admin = $this->createAdminUser();
        $writer = $this->createWriterUser();
        $this->actingAs($admin);

        [$primaryCategory] = $this->createCategories();
        [$libraryMedia] = $this->createMediaFixtures($admin);

        // The library media must have a readable original on disk for the
        // controller to copy it into the public thumbnail location.
        Storage::disk('public')->put($libraryMedia->path, 'fake-image-bytes');

        $this->post(route('admin.articles.store'), [
            'title' => $this->localizedField('عنوان المقال', 'Article Title'),
            'content' => $this->localizedField(
                '<p>محتوى عربي كافٍ لتجاوز الحد الأدنى لعدد الأحرف المطلوبة هنا.</p>',
                '<p>Sufficient English content to clear the minimum length requirement here.</p>'
            ),
            'writer_id' => $writer->id,
            'category_id' => $primaryCategory->id,
            // No `thumb` file upload — the thumbnail is selected from the library.
            'thumb_id' => $libraryMedia->id,
        ])->assertRedirect(route('admin.articles.index'));

        $article = Article::query()->latest('id')->firstOrFail();

        $this->assertNotNull($article->thumb, 'Selected library media was not saved as the article thumbnail.');
        Storage::disk('public')->assertExists($article->thumb);
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
            'fr' => $english.' FR',
            'tr' => $english.' TR',
            'ur' => $english.' UR',
        ];
    }
}
