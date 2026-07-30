<?php

declare(strict_types=1);

namespace Database\Factories\Hadith;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithChapter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Hadith>
 */
class HadithFactory extends Factory
{
    protected $model = Hadith::class;

    /**
     * The composite foreign keys mean collection_id, book_id and chapter_id must
     * agree, so the default builds a consistent chapter -> book -> collection
     * chain and derives the two parent ids from it. Passing a stray book_id
     * without its matching collection_id is exactly what the schema rejects.
     */
    public function definition(): array
    {
        return [
            'chapter_id' => HadithChapter::factory(),
            'book_id' => fn (array $attributes): int => HadithChapter::query()
                ->whereKey($attributes['chapter_id'])->value('book_id'),
            'collection_id' => fn (array $attributes): int => HadithBook::query()
                ->whereKey($attributes['book_id'])->value('collection_id'),
            'hadith_number' => (string) $this->faker->unique()->numberBetween(1, 99999),
            'in_book_number' => $this->faker->numberBetween(1, 500),
            'is_published' => true,
        ];
    }

    /** A chapterless hadith, as collections with has_chapters = false produce. */
    public function withoutChapter(): static
    {
        return $this->state(fn (): array => [
            'chapter_id' => null,
            'book_id' => HadithBook::factory(),
        ]);
    }

    public function unpublished(): static
    {
        return $this->state(fn (): array => ['is_published' => false]);
    }

    public function withUrn(int $urn): static
    {
        return $this->state(fn (): array => ['sunnah_urn' => $urn]);
    }
}
