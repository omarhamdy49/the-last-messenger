<?php

declare(strict_types=1);

namespace Database\Factories\Hadith;

use App\Domain\Hadith\Models\HadithCollection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<HadithCollection>
 */
final class HadithCollectionFactory extends Factory
{
    protected $model = HadithCollection::class;

    public function definition(): array
    {
        $slug = Str::slug($this->faker->unique()->words(2, true));

        return [
            'slug' => $slug,
            'title_ar' => 'مجموعة '.$this->faker->numberBetween(1, 9999),
            'title_en' => ucfirst($slug),
            // Mid-line placeholder: the number is substituted, never appended.
            'reference_template' => ucfirst($slug).' {hadithNumber}',
            'has_books' => true,
            'has_chapters' => true,
            'sort_order' => 0,
            'is_published' => true,
        ];
    }

    /** A collection with no books — a real shape, not an edge case. */
    public function bookless(): static
    {
        return $this->state(fn (): array => ['has_books' => false, 'has_chapters' => false]);
    }

    public function unpublished(): static
    {
        return $this->state(fn (): array => ['is_published' => false]);
    }
}
