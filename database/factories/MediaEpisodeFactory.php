<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Media\Models\MediaEpisode;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * MediaEpisode Factory
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Media\Models\MediaEpisode>
 */
class MediaEpisodeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = MediaEpisode::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $episodeNumber = $this->faker->numberBetween(1, 100);
        $titleSuffix = ucfirst($this->faker->words(3, true));
        $title = 'Episode '.$episodeNumber;
        $slug = Str::slug($title).'-'.$this->faker->unique()->numberBetween(1, 10000);

        // Generate realistic YouTube URL
        $videoId = $this->faker->regexify('[A-Za-z0-9_-]{11}');
        $videoUrl = "https://www.youtube.com/watch?v={$videoId}";

        return [
            'program_id' => 1, // Default, should be overridden
            'slug' => $slug,
            'episode_number' => $episodeNumber,
            'title' => [
                'ar' => 'الحلقة '.$episodeNumber.' - '.$this->faker->words(3, true),
                'en' => 'Episode '.$episodeNumber.' - '.$titleSuffix,
                'fr' => 'Épisode '.$episodeNumber.' - '.ucfirst($this->faker->words(3, true)),
                'tr' => 'Bölüm '.$episodeNumber.' - '.ucfirst($this->faker->words(3, true)),
                'ur' => 'قسط '.$episodeNumber.' - '.$this->faker->words(3, true),
            ],
            'description' => [
                'ar' => $this->faker->paragraphs(2, true),
                'en' => $this->faker->paragraphs(2, true),
                'fr' => $this->faker->paragraphs(2, true),
                'tr' => $this->faker->paragraphs(2, true),
                'ur' => $this->faker->paragraphs(2, true),
            ],
            'thumbnail' => $this->faker->imageUrl(640, 360, 'islamic', true),
            'video_url' => $videoUrl,
            'duration_minutes' => $this->faker->numberBetween(5, 120),
            'views_count' => $this->faker->numberBetween(0, 10000),
            'published_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }

    /**
     * Indicate that the episode is unpublished.
     */
    public function unpublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'published_at' => null,
        ]);
    }

    /**
     * Indicate that the episode is published recently.
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'published_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Create episode for specific program.
     */
    public function forProgram(int $programId): static
    {
        return $this->state(fn (array $attributes) => [
            'program_id' => $programId,
        ]);
    }

    /**
     * Create episode with specific number.
     */
    public function number(int $episodeNumber): static
    {
        return $this->state(fn (array $attributes) => [
            'episode_number' => $episodeNumber,
            'title' => [
                'ar' => 'الحلقة '.$episodeNumber,
                'en' => 'Episode '.$episodeNumber,
                'fr' => 'Épisode '.$episodeNumber,
                'tr' => 'Bölüm '.$episodeNumber,
                'ur' => 'قسط '.$episodeNumber,
            ],
        ]);
    }

    /**
     * Create popular episode.
     */
    public function popular(): static
    {
        return $this->state(fn (array $attributes) => [
            'views_count' => $this->faker->numberBetween(50000, 500000),
        ]);
    }

    /**
     * Create long episode.
     */
    public function long(): static
    {
        return $this->state(fn (array $attributes) => [
            'duration_minutes' => $this->faker->numberBetween(60, 180),
        ]);
    }

    /**
     * Create short episode.
     */
    public function short(): static
    {
        return $this->state(fn (array $attributes) => [
            'duration_minutes' => $this->faker->numberBetween(5, 15),
        ]);
    }

    /**
     * Use Vimeo video URL.
     */
    public function vimeo(): static
    {
        return $this->state(fn (array $attributes) => [
            'video_url' => 'https://vimeo.com/'.$this->faker->numberBetween(100000000, 999999999),
        ]);
    }
}
