<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\People\Models\IslamicRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IslamicRole>
 */
final class IslamicRoleFactory extends Factory
{
    protected $model = IslamicRole::class;

    public function definition(): array
    {
        $slug = $this->faker->unique()->slug(2);

        return [
            'slug' => $slug,
            'name_ar' => 'دور',
            'name_en' => ucfirst($slug),
        ];
    }

    public function sahabi(): static
    {
        return $this->state(fn (): array => [
            'slug' => 'sahabi', 'name_ar' => 'صحابي', 'name_en' => 'Companion',
        ]);
    }

    public function narrator(): static
    {
        return $this->state(fn (): array => [
            'slug' => 'narrator', 'name_ar' => 'راوي', 'name_en' => 'Narrator',
        ]);
    }
}
