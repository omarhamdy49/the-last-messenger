<?php

declare(strict_types=1);

namespace Database\Factories\Hadith;

use App\Domain\Hadith\Enums\Grade;
use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithCollection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Hadith>
 */
final class HadithFactory extends Factory
{
    protected $model = Hadith::class;

    public function definition(): array
    {
        $n = $this->faker->unique()->numberBetween(1, 1000000);
        $text = 'نص الحديث رقم '.$n;

        return [
            'collection_id' => HadithCollection::factory(),
            'book_id' => null,
            'chapter_id' => null,
            // Structural position, not the displayed number.
            'reference_key' => '1-'.$n,
            'number' => (string) $n,
            'sort_order' => $n,
            'grade' => Grade::Sahih->value,
            'raw_grade' => 'Sahih',
            'text_ar' => $text,
            'text_en' => 'Hadith text number '.$n,
            'search_ar' => $text,
            'search_en' => 'hadith text number '.$n,
            'is_published' => true,
        ];
    }

    /** One of the 38 documented records with no Arabic — never publishable. */
    public function withoutArabic(): static
    {
        return $this->state(fn (): array => [
            'text_ar' => null,
            'search_ar' => null,
            'is_published' => false,
        ]);
    }

    public function ungraded(): static
    {
        return $this->state(fn (): array => ['grade' => null]);
    }

    public function unpublished(): static
    {
        return $this->state(fn (): array => ['is_published' => false]);
    }
}
