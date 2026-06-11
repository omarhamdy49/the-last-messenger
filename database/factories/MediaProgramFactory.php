<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Media\Enums\MaturityRating;
use App\Domain\Media\Enums\ProgramCategory;
use App\Domain\Media\Models\MediaProgram;
use App\Domain\Media\Models\MediaProgramTranslation;
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
     * Sample Islamic media programs for realistic test data.
     *
     * @var array<array<string, string>>
     */
    private array $islamicPrograms = [
        ['ar' => 'عمر', 'en' => 'Omar', 'host_ar' => 'إنتاج MBC', 'host_en' => 'MBC Production'],
        ['ar' => 'قصص الأنبياء', 'en' => 'Stories of the Prophets', 'host_ar' => 'عمرو خالد', 'host_en' => 'Amr Khaled'],
        ['ar' => 'على خطى الحبيب', 'en' => 'In the Footsteps of the Beloved', 'host_ar' => 'عمرو خالد', 'host_en' => 'Amr Khaled'],
        ['ar' => 'بلال', 'en' => 'Bilal', 'host_ar' => 'إنتاج باراكة', 'host_en' => 'Barakah Production'],
        ['ar' => 'السيرة النبوية', 'en' => 'The Prophetic Biography', 'host_ar' => 'المختار الشنقيطي', 'host_en' => 'Al-Mukhtar Al-Shinqiti'],
    ];

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (MediaProgram $program) {
            $programData = $this->faker->randomElement($this->islamicPrograms);

            foreach (['ar', 'en'] as $locale) {
                MediaProgramTranslation::create([
                    'media_program_id' => $program->id,
                    'locale' => $locale,
                    'title' => $locale === 'ar' ? $programData['ar'] : $programData['en'],
                    'description' => $this->faker->paragraphs(2, true),
                    'host' => $locale === 'ar' ? $programData['host_ar'] : $programData['host_en'],
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
        $program = $this->faker->randomElement($this->islamicPrograms);
        $slug = Str::slug($program['en']).'-'.$this->faker->unique()->numberBetween(1, 9999);

        return [
            'slug' => $slug,
            'category' => $this->faker->randomElement(ProgramCategory::cases()),
            'maturity_rating' => MaturityRating::ALL,
            'year' => $this->faker->numberBetween(2000, 2024),
            'rating' => round($this->faker->numberBetween(60, 100) / 10, 1),
            'is_featured' => $this->faker->boolean(20),
            'is_trending' => $this->faker->boolean(15),
            'views_count' => $this->faker->numberBetween(0, 500000),
            'display_order' => $this->faker->numberBetween(1, 100),
        ];
    }

    /**
     * Featured program state.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
            'views_count' => $this->faker->numberBetween(100000, 1000000),
        ]);
    }

    /**
     * Trending program state.
     */
    public function trending(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_trending' => true,
        ]);
    }

    /**
     * Seerah category program state.
     */
    public function seerah(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => ProgramCategory::SEERAH,
        ]);
    }
}
