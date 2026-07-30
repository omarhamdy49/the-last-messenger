<?php

declare(strict_types=1);

namespace Database\Factories\Hadith;

use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithChapter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HadithChapter>
 */
class HadithChapterFactory extends Factory
{
    protected $model = HadithChapter::class;

    public function definition(): array
    {
        return [
            'book_id' => HadithBook::factory(),
            'chapter_number' => (string) $this->faker->unique()->numberBetween(1, 9999),
            'sort_order' => $this->faker->numberBetween(1, 100),
            'title' => ['ar' => 'باب', 'en' => 'Chapter'],
            'is_published' => true,
        ];
    }
}
