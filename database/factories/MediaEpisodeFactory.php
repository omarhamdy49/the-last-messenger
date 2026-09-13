<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Media\Models\MediaEpisode;
use App\Domain\Media\Models\MediaEpisodeTranslation;
use App\Domain\Media\Models\MediaProgram;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * MediaEpisode Factory
 *
 * @extends Factory<MediaEpisode>
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
     * Sample episode title patterns for realistic data.
     *
     * @var array<array<string, string>>
     */
    private array $episodeTitles = [
        ['ar' => 'البداية', 'en' => 'The Beginning'],
        ['ar' => 'ولادة المصطفى', 'en' => 'Birth of the Chosen One'],
        ['ar' => 'الطفولة', 'en' => 'Childhood'],
        ['ar' => 'الشباب', 'en' => 'Youth'],
        ['ar' => 'قبل البعثة', 'en' => 'Before the Prophethood'],
        ['ar' => 'بدء الوحي', 'en' => 'The First Revelation'],
        ['ar' => 'أولى المسلمين', 'en' => 'The First Muslims'],
        ['ar' => 'في مواجهة قريش', 'en' => 'Facing Quraysh'],
        ['ar' => 'الهجرة المباركة', 'en' => 'The Blessed Migration'],
        ['ar' => 'بناء الأمة', 'en' => 'Building the Nation'],
    ];

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (MediaEpisode $episode) {
            $episodeData = $this->faker->randomElement($this->episodeTitles);

            foreach (['ar', 'en'] as $locale) {
                MediaEpisodeTranslation::create([
                    'media_episode_id' => $episode->id,
                    'locale' => $locale,
                    'title' => $locale === 'ar' ? $episodeData['ar'] : $episodeData['en'],
                    'description' => $this->faker->optional(0.8)->paragraph(),
                    'is_ready' => true,
                ]);
            }
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $episode = $this->faker->randomElement($this->episodeTitles);
        $slug = Str::slug($episode['en']).'-'.$this->faker->unique()->numberBetween(1, 99999);

        return [
            'program_id' => MediaProgram::factory(),
            'season_number' => 1,
            'episode_number' => $this->faker->numberBetween(1, 30),
            'slug' => $slug,
            'video_url' => 'https://www.youtube.com/embed/'.$this->faker->regexify('[A-Za-z0-9_-]{11}'),
            'duration_seconds' => $this->faker->numberBetween(300, 5400), // 5 min to 90 min
            'views_count' => $this->faker->numberBetween(0, 100000),
            'is_featured' => $this->faker->boolean(15),
        ];
    }

    /**
     * Episode belonging to a specific program.
     */
    public function forProgram(int $programId, int $seasonNumber = 1): static
    {
        return $this->state(fn (array $attributes) => [
            'program_id' => $programId,
            'season_number' => $seasonNumber,
        ]);
    }

    /**
     * Featured episode state.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
            'views_count' => $this->faker->numberBetween(50000, 500000),
        ]);
    }
}
