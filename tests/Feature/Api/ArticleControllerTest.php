<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Domain\Blog\Models\Article;
use App\Domain\Blog\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test getting all articles
     */
    public function test_can_get_all_articles(): void
    {
        Article::factory()->count(15)->create();

        $response = $this->getJson('/api/articles');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'data' => [
                        '*' => ['id', 'slug', 'title', 'excerpt'],
                    ],
                    'meta' => ['current_page', 'total', 'per_page'],
                ],
            ]);
    }

    /**
     * Test getting single article by slug
     */
    public function test_can_get_article_by_slug(): void
    {
        $article = Article::factory()->create([
            'slug' => 'test-article',
        ]);

        $response = $this->getJson('/api/articles/test-article');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => ['id', 'slug', 'title', 'content'],
            ]);
    }

    /**
     * Test getting articles by category
     */
    public function test_can_get_articles_by_category(): void
    {
        $category = Category::factory()->create(['slug' => 'test-category']);
        Article::factory()->count(5)->create(['category_id' => $category->id]);

        $response = $this->getJson('/api/articles/category/test-category');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => ['data' => ['*' => ['id', 'slug']]],
            ]);
    }

    /**
     * Test search articles
     */
    public function test_can_search_articles(): void
    {
        Article::factory()->create([
            'slug' => 'test-article',
            'title' => ['ar' => 'مقاله تجريبية', 'en' => 'Test Article'],
        ]);

        $response = $this->getJson('/api/articles/search?q=test');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => ['data' => ['*']],
            ]);
    }

    /**
     * Test getting featured articles
     */
    public function test_can_get_featured_articles(): void
    {
        Article::factory()->count(5)->create(['is_featured' => true]);

        $response = $this->getJson('/api/articles/featured');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => ['*' => ['id', 'slug']],
            ]);
    }
}

