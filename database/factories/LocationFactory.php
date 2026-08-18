<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Shared\Enums\LocationType;
use App\Domain\Shared\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    protected $model = Location::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => $this->faker->unique()->slug(2),
            'type' => LocationType::CITY->value,
            'parent_id' => null,
            'latitude' => $this->faker->latitude(20, 30),
            'longitude' => $this->faker->longitude(35, 50),
            'aliases' => [],
            'is_active' => true,
            'display_order' => 0,
        ];
    }

    public function withoutCoordinates(): static
    {
        return $this->state(fn (): array => [
            'latitude' => null,
            'longitude' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }

    public function ofType(LocationType $type): static
    {
        return $this->state(fn (): array => ['type' => $type->value]);
    }

    /**
     * @param  array<int, string>  $aliases
     */
    public function withAliases(array $aliases): static
    {
        return $this->state(fn (): array => ['aliases' => $aliases]);
    }

    /**
     * Create the location, then attach its per-locale names.
     *
     * @param  array<string, string>  $names  locale => name
     */
    public function withNames(array $names): static
    {
        return $this->afterCreating(function (Location $location) use ($names): void {
            $location->syncTranslations(['name' => $names]);
        });
    }
}
