<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Blog\Enums\ArticleStatus;
use App\Domain\Blog\Models\Article;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Article Factory
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Blog\Models\Article>
 */
class ArticleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Article::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = $this->faker->sentence(6);
        $slug = Str::slug($title).'-'.$this->faker->unique()->numberBetween(1, 10000);

        return [
            'slug' => $slug,
            'user_id' => 1, // Default admin user
            'category_id' => $this->faker->numberBetween(1, 10),
            'series_id' => $this->faker->optional(0.3)->numberBetween(1, 5),
            'title' => [
                'ar' => 'مقال عن '.$this->faker->words(4, true),
                'en' => $this->faker->sentence(6),
                'fr' => $this->faker->sentence(6),
                'tr' => $this->faker->sentence(6),
                'ur' => 'مضمون '.$this->faker->words(4, true),
            ],
            'excerpt' => [
                'ar' => $this->faker->paragraph(2),
                'en' => $this->faker->paragraph(2),
                'fr' => $this->faker->paragraph(2),
                'tr' => $this->faker->paragraph(2),
                'ur' => $this->faker->paragraph(2),
            ],
            'content' => [
                'ar' => $this->faker->paragraphs(8, true),
                'en' => $this->faker->paragraphs(8, true),
                'fr' => $this->faker->paragraphs(8, true),
                'tr' => $this->faker->paragraphs(8, true),
                'ur' => $this->faker->paragraphs(8, true),
            ],
            'featured_image' => $this->faker->optional(0.7)->imageUrl(1200, 630, 'islamic', true),
            'reading_time_minutes' => $this->faker->numberBetween(3, 20),
            'views_count' => $this->faker->numberBetween(0, 10000),
            'favorites_count' => $this->faker->numberBetween(0, 500),
            'status' => $this->faker->randomElement(ArticleStatus::cases()),
            'published_at' => $this->faker->optional(0.7)->dateTimeBetween('-1 year', 'now'),
        ];
    }

    /**
     * Indicate that the article is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ArticleStatus::PUBLISHED,
            'published_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    /**
     * Indicate that the article is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ArticleStatus::DRAFT,
            'published_at' => null,
        ]);
    }

    /**
     * Indicate that the article is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ArticleStatus::ARCHIVED,
            'published_at' => $this->faker->dateTimeBetween('-2 years', '-1 year'),
        ]);
    }

    /**
     * Indicate that the article is featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'featured_image' => $this->faker->imageUrl(1200, 630, 'islamic', true),
            'views_count' => $this->faker->numberBetween(5000, 50000),
            'favorites_count' => $this->faker->numberBetween(200, 2000),
        ]);
    }

    /**
     * Indicate that the article is popular.
     */
    public function popular(): static
    {
        return $this->state(fn (array $attributes) => [
            'views_count' => $this->faker->numberBetween(10000, 100000),
            'favorites_count' => $this->faker->numberBetween(500, 5000),
        ]);
    }

    /**
     * Create article in a specific category.
     */
    public function inCategory(int $categoryId): static
    {
        return $this->state(fn (array $attributes) => [
            'category_id' => $categoryId,
        ]);
    }

    /**
     * Create article in a specific series.
     */
    public function inSeries(int $seriesId): static
    {
        return $this->state(fn (array $attributes) => [
            'series_id' => $seriesId,
        ]);
    }
}
