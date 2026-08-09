<?php

declare(strict_types=1);

namespace Tests\Helpers;

use App\Models\Article;
use App\Domain\Blog\Models\Category;
use App\Domain\Blog\Models\Series;
use App\Domain\Blog\Models\Tag;
use App\Domain\Media\Models\MediaProgram;
use App\Models\User;

/**
 * Test Helper Trait
 * Provides helper methods for creating test data
 */
trait TestHelper
{
    /**
     * Create a test user
     */
    protected function createTestUser(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    /**
     * Create an authenticated user
     */
    protected function actingAsUser(array $attributes = []): User
    {
        $user = $this->createTestUser($attributes);
        $this->actingAs($user);

        return $user;
    }

    /**
     * Create a test article with related data
     */
    protected function createTestArticle(array $attributes = []): Article
    {
        if (! isset($attributes['category_id'])) {
            $category = Category::factory()->create();
            $attributes['category_id'] = $category->id;
        }

        return Article::factory()->create($attributes);
    }

    /**
     * Create a test category
     */
    protected function createTestCategory(array $attributes = []): Category
    {
        return Category::factory()->create($attributes);
    }

    /**
     * Create a test series
     */
    protected function createTestSeries(array $attributes = []): Series
    {
        return Series::factory()->create($attributes);
    }

    /**
     * Create a test tag
     */
    protected function createTestTag(array $attributes = []): Tag
    {
        return Tag::factory()->create($attributes);
    }

    /**
     * Create a test media program
     */
    protected function createTestMediaProgram(array $attributes = []): MediaProgram
    {
        return MediaProgram::factory()->create($attributes);
    }

    /**
     * Assert JSON structure for API response
     */
    protected function assertJsonStructure($response, array $structure): void
    {
        $response->assertStatus(200)
            ->assertJsonStructure($structure);
    }

    /**
     * Assert paginated response structure
     */
    protected function assertPaginatedResponse($response): void
    {
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'data' => [],
                    'meta' => ['current_page', 'total', 'per_page'],
                ],
            ]);
    }

    /**
     * Assert success response structure
     */
    protected function assertSuccessResponse($response): void
    {
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data',
            ]);
    }
}
