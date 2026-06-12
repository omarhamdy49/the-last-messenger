<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Article;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        return [
            'slug' => $this->faker->unique()->slug(),
            'writer_id' => User::factory(),
            'status' => Article::STATUS_DRAFT,
            'title' => ['ar' => $this->faker->sentence(), 'en' => $this->faker->sentence()],
            'excerpt' => ['ar' => $this->faker->paragraph(), 'en' => $this->faker->paragraph()],
            'content' => ['ar' => $this->faker->paragraphs(3, true), 'en' => $this->faker->paragraphs(3, true)],
            'seo_title' => null,
            'seo_description' => null,
            'seo_keywords' => null,
            'featured_image' => null,
            'og_image' => null,
            'featured' => false,
            'reading_time_minutes' => $this->faker->numberBetween(1, 20),
            'published_at' => null,
            'category_id' => null,
        ];
    }

    public function published(): static
    {
        return $this->state([
            'status' => Article::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
    }

    public function draft(): static
    {
        return $this->state([
            'status' => Article::STATUS_DRAFT,
            'published_at' => null,
        ]);
    }
}
