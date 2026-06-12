<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Domain\Blog\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_categories_index_returns_paginated_list_with_correct_json_structure(): void
    {
        Category::query()->create([
            'slug' => 'seerah',
            'type' => 'blog',
            'status' => 'active',
            'name' => ['ar' => 'السيرة', 'en' => 'Seerah'],
            'description' => ['ar' => 'تصنيف السيرة', 'en' => 'Seerah category'],
            'lft' => 1,
            'rgt' => 2,
            'depth' => 0,
            'display_order' => 1,
        ]);

        $response = $this->getJson('/api/v1/categories');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data',
            ])
            ->assertJsonPath('success', true);
    }

    public function test_categories_index_does_not_require_authentication(): void
    {
        $response = $this->getJson('/api/v1/categories');

        $response->assertOk();
    }

    public function test_category_show_returns_single_category_by_slug(): void
    {
        Category::query()->create([
            'slug' => 'hadith',
            'type' => 'blog',
            'status' => 'active',
            'name' => ['ar' => 'الحديث', 'en' => 'Hadith'],
            'description' => ['ar' => 'تصنيف الحديث', 'en' => 'Hadith category'],
            'lft' => 1,
            'rgt' => 2,
            'depth' => 0,
            'display_order' => 1,
        ]);

        $response = $this->getJson('/api/v1/categories/hadith');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data',
            ])
            ->assertJsonPath('success', true);
    }

    public function test_category_show_returns_404_for_nonexistent_slug(): void
    {
        $response = $this->getJson('/api/v1/categories/nonexistent-category-slug');

        $response->assertNotFound()
            ->assertJsonPath('success', false);
    }

    public function test_category_response_always_has_success_and_data_keys(): void
    {
        $response = $this->getJson('/api/v1/categories');

        $response->assertJsonStructure(['success', 'data']);
    }
}
