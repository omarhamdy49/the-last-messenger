<?php

declare(strict_types=1);

namespace Database\Factories\Hadith;

use App\Domain\Hadith\Models\HadithCollection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HadithCollection>
 */
class HadithCollectionFactory extends Factory
{
    protected $model = HadithCollection::class;

    public function definition(): array
    {
        $code = $this->faker->unique()->lexify('coll????');

        return [
            'code' => $code,
            'name' => ['ar' => 'مجموعة '.$code, 'en' => 'Collection '.$code],
            'author' => ['ar' => 'مؤلف', 'en' => 'Author'],
            'reference_template' => 'Collection '.$code.' {hadithNumber}',
            'has_books' => true,
            'has_chapters' => true,
            'has_volumes' => false,
            'is_published' => true,
            'featured' => false,
            'display_order' => 0,
        ];
    }

    public function unpublished(): static
    {
        return $this->state(fn (): array => ['is_published' => false]);
    }

    public function bookless(): static
    {
        return $this->state(fn (): array => ['has_books' => false, 'has_chapters' => false]);
    }
}
