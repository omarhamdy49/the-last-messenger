<?php

declare(strict_types=1);

namespace Database\Factories\Hadith;

use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithChapter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HadithChapter>
 */
final class HadithChapterFactory extends Factory
{
    protected $model = HadithChapter::class;

    public function definition(): array
    {
        $n = $this->faker->unique()->numberBetween(1, 100000);

        return [
            'book_id' => HadithBook::factory(),
            'chapter_number' => (string) $n,
            'title_ar' => 'باب '.$n,
            'title_en' => 'Chapter '.$n,
            'sort_order' => $n,
            'is_published' => true,
        ];
    }

    public function unpublished(): static
    {
        return $this->state(fn (): array => ['is_published' => false]);
    }
}
