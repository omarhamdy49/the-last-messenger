<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Media\Enums\ProgramStatus;
use App\Domain\Media\Models\MediaProgram;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * MediaProgram Factory
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Media\Models\MediaProgram>
 */
class MediaProgramFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = MediaProgram::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $englishTitle = ucfirst($this->faker->words(3, true));
        $slug = Str::slug($englishTitle).'-'.$this->faker->unique()->numberBetween(1, 10000);

        return [
            'slug' => $slug,
            'category_id' => $this->faker->numberBetween(1, 10),
            'title' => [
                'ar' => 'برنامج '.$this->faker->words(3, true),
                'en' => $englishTitle,
                'fr' => ucfirst($this->faker->words(3, true)),
                'tr' => ucfirst($this->faker->words(3, true)),
                'ur' => 'پروگرام '.$this->faker->words(3, true),
            ],
            'description' => [
                'ar' => $this->faker->paragraphs(3, true),
                'en' => $this->faker->paragraphs(3, true),
                'fr' => $this->faker->paragraphs(3, true),
                'tr' => $this->faker->paragraphs(3, true),
                'ur' => $this->faker->paragraphs(3, true),
            ],
            'thumbnail' => $this->faker->imageUrl(640, 360, 'islamic', true),
            'banner_image' => $this->faker->imageUrl(1920, 1080, 'islamic', true),
            'total_episodes' => $this->faker->numberBetween(1, 100),
            'total_views' => $this->faker->numberBetween(0, 100000),
            'status' => $this->faker->randomElement(ProgramStatus::cases()),
        ];
    }

    /**
     * Indicate that the program is ongoing.
     */
    public function ongoing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProgramStatus::ONGOING,
        ]);
    }

    /**
     * Indicate that the program is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProgramStatus::COMPLETED,
            'total_episodes' => $this->faker->numberBetween(20, 100),
        ]);
    }

    /**
     * Indicate that the program is paused.
     */
    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProgramStatus::PAUSED,
        ]);
    }

    /**
     * Create popular program.
     */
    public function popular(): static
    {
        return $this->state(fn (array $attributes) => [
            'total_views' => $this->faker->numberBetween(50000, 500000),
            'total_episodes' => $this->faker->numberBetween(50, 100),
        ]);
    }

    /**
     * Create program in specific category.
     */
    public function inCategory(int $categoryId): static
    {
        return $this->state(fn (array $attributes) => [
            'category_id' => $categoryId,
        ]);
    }

    /**
     * Create program with many episodes.
     */
    public function withManyEpisodes(): static
    {
        return $this->state(fn (array $attributes) => [
            'total_episodes' => $this->faker->numberBetween(50, 200),
        ]);
    }
}
