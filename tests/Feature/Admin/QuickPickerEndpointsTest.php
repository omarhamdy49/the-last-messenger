<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Blog\Models\Category;
use App\Models\Article;
use App\Models\ArticleLocale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * JSON endpoints behind the Seerah event links tab: paginated article picker
 * and tag/category quick-create.
 */
final class QuickPickerEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_article_picker_paginates_searches_filters_and_excludes(): void
    {
        $this->actingAs($this->createAdminUser());
        $category = Category::query()->create(['slug' => 'seerah', 'type' => 'blog', 'status' => 'active']);

        $articles = collect(range(1, 14))->map(function (int $i) use ($category): Article {
            $article = Article::factory()->create([
                'status' => $i % 2 ? 'published' : 'draft',
                'category_id' => $i <= 3 ? $category->id : null,
            ]);
            ArticleLocale::create(['article_id' => $article->id, 'locale' => 'ar', 'title' => $i === 5 ? 'غزوة بدر' : "مقال {$i}", 'slug' => "a-{$i}"]);

            return $article;
        });

        $this->getJson(route('admin.articles.modules.articles'))
            ->assertOk()->assertJsonCount(12, 'data')
            ->assertJsonPath('meta.last_page', 2)->assertJsonPath('meta.total', 14);

        $this->getJson(route('admin.articles.modules.articles', ['q' => 'بدر']))
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $articles[4]->id);

        $this->getJson(route('admin.articles.modules.articles', ['category' => $category->id]))
            ->assertJsonPath('meta.total', 3);

        $this->getJson(route('admin.articles.modules.articles', ['status' => 'draft', 'exclude' => [$articles[1]->id]]))
            ->assertJsonPath('meta.total', 6);
    }

    public function test_tag_and_category_can_be_quick_created_as_json(): void
    {
        $this->actingAs($this->createAdminUser());

        $tag = $this->postJson(route('admin.tags.store'), ['name' => ['ar' => 'الغزوات'], 'type' => 'event'])
            ->assertCreated()->json('data.id');
        $this->assertDatabaseHas('tag_translations', ['tag_id' => $tag, 'locale' => 'ar', 'name' => 'الغزوات']);

        $this->postJson(route('admin.categories.store'), ['name' => ['ar' => 'المعارك'], 'type' => 'timeline'])
            ->assertUnprocessable()->assertJsonValidationErrors('name.en');

        $this->postJson(route('admin.categories.store'), ['name' => ['ar' => 'المعارك', 'en' => 'Battles'], 'type' => 'timeline'])
            ->assertCreated()->assertJsonPath('data.slug', 'battles')->assertJsonPath('data.name', 'المعارك');
    }
}
