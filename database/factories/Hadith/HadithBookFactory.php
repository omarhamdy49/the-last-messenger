<?php

declare(strict_types=1);

namespace Database\Factories\Hadith;

use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithCollection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HadithBook>
 */
final class HadithBookFactory extends Factory
{
    protected $model = HadithBook::class;

    public function definition(): array
    {
        $n = $this->faker->unique()->numberBetween(1, 100000);

        return [
            'collection_id' => HadithCollection::factory(),
            'book_number' => (string) $n,
            'title_ar' => 'كتاب '.$n,
            'title_en' => 'Book '.$n,
            'sort_order' => $n,
            'is_published' => true,
        ];
    }

    public function unpublished(): static
    {
        return $this->state(fn (): array => ['is_published' => false]);
    }
}
