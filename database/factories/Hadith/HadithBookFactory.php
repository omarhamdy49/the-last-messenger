<?php

declare(strict_types=1);

namespace Database\Factories\Hadith;

use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithCollection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HadithBook>
 */
class HadithBookFactory extends Factory
{
    protected $model = HadithBook::class;

    public function definition(): array
    {
        return [
            'collection_id' => HadithCollection::factory(),
            'book_number' => (string) $this->faker->unique()->numberBetween(1, 9999),
            'sort_order' => $this->faker->numberBetween(1, 100),
            'title' => ['ar' => 'كتاب', 'en' => 'Book'],
            'is_synthetic' => false,
            'is_published' => true,
        ];
    }

    public function synthetic(): static
    {
        return $this->state(fn (): array => ['is_synthetic' => true, 'book_number' => '0']);
    }

    public function unpublished(): static
    {
        return $this->state(fn (): array => ['is_published' => false]);
    }
}
