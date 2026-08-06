<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\People\Enums\Gender;
use App\Domain\People\Models\Muslim;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Muslim>
 */
final class MuslimFactory extends Factory
{
    protected $model = Muslim::class;

    public function definition(): array
    {
        $nameEn = $this->faker->name();

        return [
            'slug' => Str::slug($nameEn).'-'.$this->faker->unique()->numberBetween(1, 999999),
            'name_ar' => 'عبد الله بن '.$this->faker->numberBetween(1, 9999),
            'name_en' => $nameEn,
            'kunya' => ['ar' => 'أبو محمد', 'en' => 'Abu Muhammad'],
            'bio' => ['ar' => 'سيرة موجزة.', 'en' => 'A short biography.'],
            'gender' => Gender::Male->value,
            // Negative years are pre-Hijra and entirely normal for companions.
            'birth_year_hijri' => $this->faker->numberBetween(-60, 10),
            'death_year_hijri' => $this->faker->numberBetween(11, 120),
            'generation' => 'صحابي',
            'is_published' => true,
        ];
    }

    public function unpublished(): static
    {
        return $this->state(fn (): array => ['is_published' => false]);
    }

    public function hiddenFromTree(): static
    {
        return $this->state(fn (): array => ['hide_from_tree' => true]);
    }

    /** A person with only an Arabic name — legitimate, and the CHECK allows it. */
    public function arabicOnly(): static
    {
        return $this->state(fn (): array => ['name_en' => null]);
    }
}
