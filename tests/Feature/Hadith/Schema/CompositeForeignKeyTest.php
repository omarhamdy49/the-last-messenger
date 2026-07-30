<?php

declare(strict_types=1);

namespace Tests\Feature\Hadith\Schema;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithChapter;
use App\Domain\Hadith\Models\HadithCollection;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FR-009. Without the composite foreign keys, a hadith can claim collection A,
 * a book that belongs to collection B, and a chapter that belongs to a third
 * book — and every reference line it renders is then wrong.
 *
 * These assertions target the DATABASE, not application validation: they insert
 * straight through Eloquent and require the engine to refuse.
 */
class CompositeForeignKeyTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_rejects_a_hadith_whose_book_belongs_to_another_collection(): void
    {
        $collectionA = HadithCollection::factory()->create();
        $collectionB = HadithCollection::factory()->create();
        $bookOfB = HadithBook::factory()->for($collectionB, 'collection')->create();

        $this->expectException(QueryException::class);

        Hadith::query()->create([
            'collection_id' => $collectionA->id,
            'book_id' => $bookOfB->id,
            'chapter_id' => null,
            'hadith_number' => '1',
            'in_book_number' => 1,
        ]);
    }

    public function test_it_rejects_a_hadith_whose_chapter_belongs_to_another_book(): void
    {
        $collection = HadithCollection::factory()->create();
        $bookOne = HadithBook::factory()->for($collection, 'collection')->create();
        $bookTwo = HadithBook::factory()->for($collection, 'collection')->create();
        $chapterOfBookTwo = HadithChapter::factory()->for($bookTwo, 'book')->create();

        $this->expectException(QueryException::class);

        Hadith::query()->create([
            'collection_id' => $collection->id,
            'book_id' => $bookOne->id,
            'chapter_id' => $chapterOfBookTwo->id,
            'hadith_number' => '2',
            'in_book_number' => 1,
        ]);
    }

    public function test_it_accepts_a_consistent_chain(): void
    {
        $collection = HadithCollection::factory()->create();
        $book = HadithBook::factory()->for($collection, 'collection')->create();
        $chapter = HadithChapter::factory()->for($book, 'book')->create();

        $hadith = Hadith::query()->create([
            'collection_id' => $collection->id,
            'book_id' => $book->id,
            'chapter_id' => $chapter->id,
            'hadith_number' => '3',
            'in_book_number' => 1,
        ]);

        $this->assertTrue($hadith->exists);
        $this->assertDatabaseHas('hadith_hadiths', [
            'id' => $hadith->id,
            'collection_id' => $collection->id,
            'book_id' => $book->id,
            'chapter_id' => $chapter->id,
        ]);
    }

    public function test_the_canonical_identity_is_unique_per_collection(): void
    {
        $collection = HadithCollection::factory()->create();
        $book = HadithBook::factory()->for($collection, 'collection')->create();

        Hadith::query()->create([
            'collection_id' => $collection->id,
            'book_id' => $book->id,
            'hadith_number' => '1a',
            'in_book_number' => 1,
        ]);

        $this->expectException(QueryException::class);

        Hadith::query()->create([
            'collection_id' => $collection->id,
            'book_id' => $book->id,
            'hadith_number' => '1a',
            'in_book_number' => 2,
        ]);
    }
}
