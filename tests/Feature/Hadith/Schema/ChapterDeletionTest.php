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
 * FR-010. `chapter_id` is RESTRICT, not nullOnDelete: silently orphaning every
 * hadith in a chapter because an editor deleted it is not an acceptable outcome,
 * and nullOnDelete would do exactly that without any error.
 */
class ChapterDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_refuses_to_delete_a_chapter_that_still_has_hadiths(): void
    {
        $collection = HadithCollection::factory()->create();
        $book = HadithBook::factory()->for($collection, 'collection')->create();
        $chapter = HadithChapter::factory()->for($book, 'book')->create();

        $hadith = Hadith::query()->create([
            'collection_id' => $collection->id,
            'book_id' => $book->id,
            'chapter_id' => $chapter->id,
            'hadith_number' => '1',
            'in_book_number' => 1,
        ]);

        try {
            $chapter->delete();
            $this->fail('Deleting a chapter with hadiths should have been rejected.');
        } catch (QueryException) {
            // expected
        }

        $this->assertDatabaseHas('hadith_chapters', ['id' => $chapter->id]);
        $this->assertDatabaseHas('hadith_hadiths', [
            'id' => $hadith->id,
            'chapter_id' => $chapter->id,
        ]);
    }

    public function test_an_empty_chapter_deletes_cleanly(): void
    {
        $chapter = HadithChapter::factory()->create();

        $chapter->delete();

        $this->assertDatabaseMissing('hadith_chapters', ['id' => $chapter->id]);
    }

    public function test_it_refuses_to_delete_a_book_that_still_has_hadiths(): void
    {
        $collection = HadithCollection::factory()->create();
        $book = HadithBook::factory()->for($collection, 'collection')->create();

        Hadith::query()->create([
            'collection_id' => $collection->id,
            'book_id' => $book->id,
            'hadith_number' => '1',
            'in_book_number' => 1,
        ]);

        $this->expectException(QueryException::class);

        $book->delete();
    }
}
