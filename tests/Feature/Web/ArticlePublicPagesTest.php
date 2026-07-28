<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Domain\Blog\Models\Category;
use App\Domain\Blog\Models\Tag;
use App\Models\Article;
use App\Models\ArticleLocale;
use Database\Seeders\HomeSectionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Covers the public Articles pages (B-8): the library index (payload, facets),
 * the single-article reader (session-guarded view counter, author, related
 * carousel), and the 404 path for unknown slugs.
 */
final class ArticlePublicPagesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create a published article with a ready Arabic locale row.
     */
    private function makeArticle(string $slug, string $title, array $overrides = []): Article
    {
        $article = Article::factory()->published()->create(array_merge([
            'slug' => $slug,
            'views' => 0,
            'read_time' => 5,
            'thumb' => null,
        ], $overrides));

        ArticleLocale::query()->create([
            'article_id' => $article->id,
            'locale' => 'ar',
            'title' => $title,
            'slug' => $slug,
            'excerpt' => 'مقتطف عن '.$title,
            'content' => ['body' => '<p>محتوى المقال للاختبار.</p>'],
            'is_ready' => true,
        ]);

        return $article;
    }

    /**
     * Create a blog category with an Arabic name translation.
     */
    private function makeCategory(string $slug, string $nameAr): Category
    {
        $category = Category::query()->create([
            'slug' => $slug,
            'type' => 'blog',
            'status' => 'active',
        ]);

        DB::table('category_translations')->insert([
            'category_id' => $category->id,
            'locale' => 'ar',
            'name' => $nameAr,
            'is_ready' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $category;
    }

    /**
     * Create an article tag with an Arabic name translation.
     */
    private function makeTag(string $slug, string $nameAr): Tag
    {
        $tag = Tag::query()->create([
            'slug' => $slug,
            'type' => 'article',
        ]);

        DB::table('tag_translations')->insert([
            'tag_id' => $tag->id,
            'locale' => 'ar',
            'name' => $nameAr,
            'is_ready' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $tag;
    }

    public function test_index_returns_200_and_shows_seeded_titles(): void
    {
        $this->makeArticle('first-seerah-study', 'First Seerah Study');
        $this->makeArticle('second-seerah-study', 'Second Seerah Study');

        $response = $this->withSession(['locale' => 'ar'])->get(route('public.articles'));

        $response->assertOk()
            ->assertSee('First Seerah Study')
            ->assertSee('Second Seerah Study');
    }

    public function test_index_excludes_articles_without_a_ready_locale(): void
    {
        $this->makeArticle('ready-article', 'Ready Article');

        // Article with no ready locale must not appear in the public payload.
        Article::factory()->published()->create(['slug' => 'unready-article']);

        $response = $this->withSession(['locale' => 'ar'])->get(route('public.articles'));

        $response->assertOk();
        $this->assertCount(1, $response->viewData('articles'));
    }

    public function test_index_payload_excludes_content_bodies(): void
    {
        $this->makeArticle('payload-check', 'Payload Check Article');

        $response = $this->withSession(['locale' => 'ar'])->get(route('public.articles'));

        $response->assertOk();

        $cards = $response->viewData('articles');
        $this->assertNotEmpty($cards);
        $this->assertArrayNotHasKey('body', $cards[0]);
        $this->assertArrayNotHasKey('content', $cards[0]);
        $this->assertSame('مقتطف عن Payload Check Article', $cards[0]['excerpt']);
    }

    public function test_index_category_facets_count_only_published_articles(): void
    {
        $category = $this->makeCategory('seerah', 'السيرة النبوية');

        $this->makeArticle('cat-article-one', 'Category Article One', ['category_id' => $category->id]);
        $this->makeArticle('cat-article-two', 'Category Article Two', ['category_id' => $category->id]);

        // No ready locale — must not be counted.
        Article::factory()->published()->create(['slug' => 'cat-draft', 'category_id' => $category->id]);

        // Category with no published articles — must not appear at all.
        $this->makeCategory('empty-category', 'تصنيف فارغ');

        $response = $this->get(route('public.articles'));

        $response->assertOk();

        $categories = $response->viewData('categories');
        $this->assertCount(1, $categories);
        $this->assertSame($category->id, $categories[0]['id']);
        $this->assertSame('السيرة النبوية', $categories[0]['name']);
        $this->assertSame(2, $categories[0]['count']);
    }

    public function test_index_tag_facets_have_real_counts(): void
    {
        $tag = $this->makeTag('hijra', 'الهجرة');
        $unusedTag = $this->makeTag('unused', 'غير مستخدم');

        $articleA = $this->makeArticle('tagged-one', 'Tagged Article One');
        $articleB = $this->makeArticle('tagged-two', 'Tagged Article Two');
        $articleA->tags()->attach($tag->id);
        $articleB->tags()->attach($tag->id);

        $response = $this->get(route('public.articles'));

        $response->assertOk();

        $tags = $response->viewData('tags');
        $this->assertCount(1, $tags);
        $this->assertSame('الهجرة', $tags[0]['name']);
        $this->assertSame(2, $tags[0]['count']);
        $this->assertNotContains('غير مستخدم', array_column($tags, 'name'));
    }

    public function test_show_returns_200_with_title_author_and_related_section(): void
    {
        $article = $this->makeArticle('shown-article', 'Shown Article Title');

        $response = $this->get(route('public.articles.show', 'shown-article'));

        $response->assertOk()
            ->assertSee('Shown Article Title')
            ->assertSee($article->writer->name)
            ->assertSee(__('articles.public.related_articles'));
    }

    public function test_show_renders_related_articles_from_same_category(): void
    {
        $category = $this->makeCategory('battles', 'الغزوات');

        $this->makeArticle('main-battle-article', 'Main Battle Article', ['category_id' => $category->id]);
        $this->makeArticle('sibling-battle-article', 'Sibling Battle Article', ['category_id' => $category->id]);

        $response = $this->get(route('public.articles.show', 'main-battle-article'));

        $response->assertOk()->assertSee('Sibling Battle Article');

        $related = $response->viewData('related');
        $this->assertCount(1, $related);
        $this->assertSame('Sibling Battle Article', $related[0]['title']);
    }

    public function test_show_increments_views_exactly_once_per_session(): void
    {
        $article = $this->makeArticle('counted-article', 'Counted Article');

        $this->get(route('public.articles.show', 'counted-article'))->assertOk();
        $this->assertSame(1, (int) $article->fresh()->views);

        // Same session: a refresh must not inflate the counter.
        $this->get(route('public.articles.show', 'counted-article'))->assertOk();
        $this->assertSame(1, (int) $article->fresh()->views);

        // New session: counts again.
        $this->flushSession();
        $this->get(route('public.articles.show', 'counted-article'))->assertOk();
        $this->assertSame(2, (int) $article->fresh()->views);
    }

    public function test_show_returns_404_for_unknown_slug(): void
    {
        $this->get(route('public.articles.show', 'no-such-article'))->assertNotFound();
    }

    public function test_home_featured_section_renders_a_real_published_article(): void
    {
        $this->seed(HomeSectionsSeeder::class);

        $article = $this->makeArticle('real-home-feature-ar', 'ميزة حقيقية', [
            'is_featured' => true,
            'thumb' => 'articles/thumbs/real-home-feature.webp',
        ]);
        ArticleLocale::query()->create([
            'article_id' => $article->id,
            'locale' => 'en',
            'title' => 'Real Home Feature',
            'slug' => 'real-home-feature',
            'excerpt' => 'A real database excerpt.',
            'is_ready' => true,
        ]);
        Cache::forget('home_featured_articles_en_3');

        $response = $this->withSession(['locale' => 'en'])->get(route('public.home'));

        $response->assertOk()
            ->assertSee('Real Home Feature')
            ->assertSee('A real database excerpt.')
            ->assertSee(route('public.articles.show', 'real-home-feature'), false)
            ->assertSee(asset('storage/articles/thumbs/real-home-feature.webp'), false);
    }

    public function test_home_featured_section_is_hidden_without_a_publishable_article(): void
    {
        $this->seed(HomeSectionsSeeder::class);

        $draft = Article::factory()->draft()->create(['slug' => 'ready-draft']);
        ArticleLocale::query()->create([
            'article_id' => $draft->id,
            'locale' => 'ar',
            'title' => 'Ready Draft',
            'slug' => 'ready-draft',
            'is_ready' => true,
        ]);

        $published = Article::factory()->published()->create(['slug' => 'unready-published']);
        ArticleLocale::query()->create([
            'article_id' => $published->id,
            'locale' => 'ar',
            'title' => 'Unready Published',
            'slug' => 'unready-published',
            'is_ready' => false,
        ]);
        Cache::forget('home_featured_articles_ar_3');

        $this->withSession(['locale' => 'ar'])
            ->get(route('public.home'))
            ->assertOk()
            ->assertDontSee('محتوى مميز');
    }
}
