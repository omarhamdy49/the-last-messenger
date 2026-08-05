<?php

declare(strict_types=1);

namespace Tests\Feature\Hadith\Browse;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithCollection;
use App\Domain\Hadith\Services\HadithBrowseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The hub hero states what the published library holds. These figures are read
 * by a reader as a claim about the site, so every exclusion the browse surface
 * applies has to apply here too — and the totals must not be a function of what
 * the collection listing happens to return on a given request.
 */
class LibraryTotalsTest extends TestCase
{
    use RefreshDatabase;

    private HadithBrowseService $browse;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->browse = app(HadithBrowseService::class);
    }

    private function collection(string $code, bool $published = true): HadithCollection
    {
        return HadithCollection::query()->create([
            'code' => $code,
            'name' => ['ar' => $code, 'en' => $code],
            'is_published' => $published,
            'reference_template' => $code.' {hadithNumber}',
        ]);
    }

    public function test_totals_count_only_published_collections_books_and_hadiths(): void
    {
        $collection = $this->collection('bukhari');
        $book = HadithBook::factory()->for($collection, 'collection')->create(['is_published' => true]);

        Hadith::factory()->count(3)->withoutChapter()->create([
            'book_id' => $book->id,
            'collection_id' => $collection->id,
        ]);
        Hadith::factory()->withoutChapter()->unpublished()->create([
            'book_id' => $book->id,
            'collection_id' => $collection->id,
        ]);
        HadithBook::factory()->for($collection, 'collection')->create(['is_published' => false]);

        $this->assertSame(
            ['collections' => 1, 'hadiths' => 3, 'books' => 1],
            $this->browse->libraryTotals(),
        );
    }

    public function test_synthetic_books_are_excluded_so_the_total_matches_the_listing(): void
    {
        $collection = $this->collection('nawawi40');
        HadithBook::factory()->for($collection, 'collection')->create(['is_published' => true]);
        HadithBook::factory()->for($collection, 'collection')->create([
            'is_published' => true,
            'is_synthetic' => true,
        ]);

        $totals = $this->browse->libraryTotals();

        // The hub cards count books the same way; a total larger than the sum of
        // the parts is the bug this asserts against.
        $this->assertSame(1, $totals['books']);
        $this->assertSame(
            $this->browse->hub()->sum('books_count'),
            $totals['books'],
        );
    }

    public function test_content_under_an_unpublished_collection_is_not_advertised(): void
    {
        // Published rows hanging off an unpublished collection are unreachable to
        // readers, so counting them would advertise content the site won't serve.
        $hidden = $this->collection('hidden', published: false);
        $book = HadithBook::factory()->for($hidden, 'collection')->create(['is_published' => true]);

        Hadith::factory()->count(2)->withoutChapter()->create([
            'book_id' => $book->id,
            'collection_id' => $hidden->id,
        ]);

        $this->assertSame(
            ['collections' => 0, 'hadiths' => 0, 'books' => 0],
            $this->browse->libraryTotals(),
        );
    }

    public function test_totals_are_independent_of_how_much_of_the_listing_is_rendered(): void
    {
        // The regression this guards: totals derived by summing the rendered
        // collection would drop to the size of one page the day the hub listing
        // is paginated. The aggregate must not move when the listing is sliced.
        foreach (['bukhari', 'muslim', 'abudawud'] as $code) {
            $collection = $this->collection($code);
            $book = HadithBook::factory()->for($collection, 'collection')->create(['is_published' => true]);
            Hadith::factory()->count(2)->withoutChapter()->create([
                'book_id' => $book->id,
                'collection_id' => $collection->id,
            ]);
        }

        $totals = $this->browse->libraryTotals();
        $firstPageOnly = $this->browse->hub()->take(1);

        $this->assertSame(3, $totals['collections']);
        $this->assertSame(6, $totals['hadiths']);
        $this->assertSame(2, $firstPageOnly->sum('hadiths_count'), 'guard: the slice really is smaller');
        $this->assertNotSame($firstPageOnly->sum('hadiths_count'), $totals['hadiths']);
    }
}
