<?php

declare(strict_types=1);

namespace Database\Factories\Hadith;

use App\Domain\Hadith\Models\HadithScholar;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HadithScholar>
 */
class HadithScholarFactory extends Factory
{
    protected $model = HadithScholar::class;

    public function definition(): array
    {
        $slug = $this->faker->unique()->slug(2);

        return [
            'slug' => $slug,
            'name' => ['ar' => 'عالم', 'en' => 'Scholar '.$slug],
            'is_companion' => false,
        ];
    }

    public function companion(): static
    {
        return $this->state(fn (): array => ['is_companion' => true]);
    }
}
