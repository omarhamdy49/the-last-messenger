<?php

declare(strict_types=1);

namespace Tests\Feature\Web\Hadith;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithChapter;
use App\Domain\Hadith\Models\HadithCollection;
use App\Domain\Hadith\Models\HadithText;
use App\Domain\Hadith\Services\HadithBrowseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Covers the hub, collection and book pages (T114–T117).
 */
class HadithBrowsePagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function collection(array $overrides = []): HadithCollection
    {
        return HadithCollection::factory()->create(array_merge([
            'code' => 'bukhari',
            'name' => ['ar' => 'صحيح البخاري', 'en' => 'Sahih al-Bukhari'],
            'reference_template' => 'Sahih al-Bukhari {hadithNumber}',
        ], $overrides));
    }

    private function hadithIn(HadithBook $book, HadithCollection $collection, ?HadithChapter $chapter, string $number, int $inBook): Hadith
    {
        $hadith = Hadith::query()->create([
            'collection_id' => $collection->id,
            'book_id' => $book->id,
            'chapter_id' => $chapter?->id,
            'hadith_number' => $number,
            'in_book_number' => $inBook,
            'is_published' => true,
        ]);
        HadithText::factory()->for($hadith, 'hadith')->arabic('نص الحديث '.$number)->create();

        return $hadith;
    }

    // ---------------------------------------------------------------- hub

    public function test_the_hub_lists_only_published_collections(): void
    {
        $this->collection();
        $this->collection(['code' => 'hidden', 'name' => ['ar' => 'مخفية', 'en' => 'Hidden'], 'is_published' => false]);

        $this->get('/hadith')
            ->assertOk()
            ->assertSee('صحيح البخاري', false)
            ->assertDontSee('مخفية', false);
    }

    public function test_the_hub_shows_a_meaningful_empty_state(): void
    {
        $this->get('/hadith')->assertOk()->assertSee(__('hadith.no_collections'));
    }

    // --------------------------------------------------------- collection

    public function test_a_collection_lists_its_books_in_sort_order(): void
    {
        $collection = $this->collection();
        HadithBook::factory()->for($collection, 'collection')->create([
            'book_number' => '2', 'sort_order' => 2, 'title' => ['ar' => 'الكتاب الثاني', 'en' => 'Second'],
        ]);
        HadithBook::factory()->for($collection, 'collection')->create([
            'book_number' => '1', 'sort_order' => 1, 'title' => ['ar' => 'الكتاب الأول', 'en' => 'First'],
        ]);

        $response = $this->get('/hadith/bukhari');

        $response->assertOk();
        $body = $response->getContent();
        $this->assertLessThan(
            strpos($body, 'الكتاب الثاني'),
            strpos($body, 'الكتاب الأول'),
            'books must render in sort_order, not insertion order',
        );
    }

    public function test_an_unknown_collection_is_a_404(): void
    {
        $this->get('/hadith/nosuch')->assertNotFound();
    }

    public function test_an_unpublished_collection_is_a_404(): void
    {
        $this->collection(['is_published' => false]);

        $this->get('/hadith/bukhari')->assertNotFound();
    }

    /** A synthetic book is hidden plumbing, but its hadiths are real content. */
    public function test_a_bookless_collection_renders_its_hadiths_without_exposing_the_synthetic_book(): void
    {
        $collection = $this->collection(['code' => 'nawawi40', 'has_books' => false]);
        $synthetic = HadithBook::factory()->for($collection, 'collection')->synthetic()->create([
            'title' => ['ar' => 'كتاب اصطناعي', 'en' => 'Synthetic placeholder'],
        ]);
        $this->hadithIn($synthetic, $collection, null, '1', 1);

        $response = $this->get('/hadith/nawawi40');

        $response->assertOk();
        $response->assertDontSee('Synthetic placeholder');
        $response->assertDontSee('كتاب اصطناعي', false);
        $response->assertSee('nawawi40:1');
        $response->assertDontSee(__('hadith.no_books'));
    }

    public function test_a_collection_with_no_books_shows_an_empty_state(): void
    {
        $this->collection();

        $this->get('/hadith/bukhari')->assertOk()->assertSee(__('hadith.no_books'));
    }

    // --------------------------------------------------------------- book

    public function test_a_book_renders_its_hadiths_under_chapter_headings_in_reading_order(): void
    {
        $collection = $this->collection();
        $book = HadithBook::factory()->for($collection, 'collection')->create([
            'book_number' => '1', 'title' => ['ar' => 'بدء الوحى', 'en' => 'Revelation'],
        ]);
        $chapterOne = HadithChapter::factory()->for($book, 'book')->create([
            'chapter_number' => '1', 'sort_order' => 1, 'title' => ['ar' => 'الباب الأول', 'en' => 'Chapter one'],
        ]);
        $chapterTwo = HadithChapter::factory()->for($book, 'book')->create([
            'chapter_number' => '2', 'sort_order' => 2, 'title' => ['ar' => 'الباب الثاني', 'en' => 'Chapter two'],
        ]);

        // Inserted out of order on purpose: ordering must come from
        // in_book_number, not from insertion.
        $this->hadithIn($book, $collection, $chapterTwo, '3', 3);
        $this->hadithIn($book, $collection, $chapterOne, '1', 1);

        $response = $this->get('/hadith/bukhari/1');
        $response->assertOk();

        $body = $response->getContent();
        $this->assertLessThan(strpos($body, 'الباب الثاني'), strpos($body, 'الباب الأول'));
        $this->assertLessThan(strpos($body, 'bukhari:3'), strpos($body, 'bukhari:1'));
    }

    public function test_a_book_renders_the_chapter_intro_and_ending(): void
    {
        $collection = $this->collection();
        $book = HadithBook::factory()->for($collection, 'collection')->create(['book_number' => '1']);
        $chapter = HadithChapter::factory()->for($book, 'book')->create([
            'chapter_number' => '1',
            'title' => ['ar' => 'الباب', 'en' => 'Chapter'],
            'intro' => ['ar' => 'مقدمة الباب', 'en' => 'Chapter preamble'],
            'ending' => ['ar' => 'خاتمة الباب', 'en' => 'Chapter ending'],
        ]);
        $this->hadithIn($book, $collection, $chapter, '1', 1);

        $this->get('/hadith/bukhari/1')
            ->assertOk()
            ->assertSee('مقدمة الباب', false)
            ->assertSee('خاتمة الباب', false);
    }

    public function test_a_book_page_exposes_one_accessible_toggle_for_bilingual_hadith_cards(): void
    {
        $collection = $this->collection();
        $book = HadithBook::factory()->for($collection, 'collection')->create(['book_number' => '1']);
        $hadith = $this->hadithIn($book, $collection, null, '1', 1);
        HadithText::factory()->for($hadith, 'hadith')->english('Actions are judged by intentions')->create();

        $this->get('/hadith/bukhari/1')
            ->assertOk()
            ->assertSee('role="group" aria-label="'.__('hadith.text_language').'"', false)
            ->assertSee('العربية', false)
            ->assertSee('English', false)
            ->assertSee('نص الحديث 1', false)
            ->assertSee('Actions are judged by intentions', false)
            ->assertSee('dir="rtl" lang="ar"', false)
            ->assertSee('dir="ltr" lang="en"', false)
            ->assertSee("x-show=\"textLanguage === 'en' || false\"", false);
    }

    public function test_selecting_english_keeps_arabic_visible_when_a_card_has_no_translation(): void
    {
        $collection = $this->collection();
        $book = HadithBook::factory()->for($collection, 'collection')->create(['book_number' => '1']);
        $this->hadithIn($book, $collection, null, '1', 1);

        $this->get('/hadith/bukhari/1')
            ->assertOk()
            ->assertSee("x-show=\"textLanguage === 'ar' || true\"", false)
            ->assertSee('نص الحديث 1', false)
            ->assertDontSee('dir="ltr" lang="en"', false);
    }

    public function test_an_unknown_book_is_a_404(): void
    {
        $this->collection();

        $this->get('/hadith/bukhari/999')->assertNotFound();
    }

    public function test_a_named_introduction_book_uses_the_book_route_and_view(): void
    {
        $collection = $this->collection([
            'code' => 'muslim',
            'name' => ['ar' => 'صحيح مسلم', 'en' => 'Sahih Muslim'],
        ]);
        $book = HadithBook::factory()->for($collection, 'collection')->create([
            'book_number' => 'introduction',
            // Production imported this named book without a localized title,
            // which used to leave both the H1 and document title blank.
            'title' => ['ar' => '', 'en' => ''],
        ]);
        $this->hadithIn($book, $collection, null, 'intro.1', 1);

        $this->get('/hadith/muslim/introduction')
            ->assertOk()
            ->assertViewIs('public.hadith.book')
            ->assertViewHas('book', fn (HadithBook $rendered): bool => $rendered->book_number === 'introduction')
            ->assertSee('muslim:intro.1')
            ->assertSee('<title>المقدمة — صحيح مسلم</title>', false)
            ->assertSee('المقدمة', false);
    }

    public function test_an_unpublished_hadith_does_not_appear_in_its_book(): void
    {
        $collection = $this->collection();
        $book = HadithBook::factory()->for($collection, 'collection')->create(['book_number' => '1']);
        $chapter = HadithChapter::factory()->for($book, 'book')->create(['chapter_number' => '1']);

        $this->hadithIn($book, $collection, $chapter, '1', 1);
        Hadith::query()->create([
            'collection_id' => $collection->id, 'book_id' => $book->id, 'chapter_id' => $chapter->id,
            'hadith_number' => '2', 'in_book_number' => 2, 'is_published' => false,
        ]);

        $this->get('/hadith/bukhari/1')
            ->assertOk()
            ->assertSee('bukhari:1')
            ->assertDontSee('bukhari:2');
    }

    public function test_a_chapterless_collection_still_renders_its_hadiths(): void
    {
        $collection = $this->collection(['code' => 'ahmad', 'has_chapters' => false]);
        $book = HadithBook::factory()->for($collection, 'collection')->create(['book_number' => '1']);
        $this->hadithIn($book, $collection, null, '1', 1);

        $this->get('/hadith/ahmad/1')->assertOk()->assertSee('ahmad:1');
    }

    public function test_source_missing_individual_grade_is_not_presented_as_a_negative_grading(): void
    {
        app()->setLocale('en');
        $collection = $this->collection([
            'code' => 'muslim',
            'name' => ['ar' => 'صحيح مسلم', 'en' => 'Sahih Muslim'],
            'reference_template' => 'Sahih Muslim {hadithNumber}',
        ]);
        $book = HadithBook::factory()->for($collection, 'collection')->create(['book_number' => '1']);
        $hadith = $this->hadithIn($book, $collection, null, '1', 1);
        $hadith->update([
            'source_metadata' => [
                'grading_context' => [
                    'per_hadith_status' => 'not_provided_by_source',
                    'collection_classification' => 'Sahih',
                ],
            ],
        ]);

        $this->get('/hadith/muslim:1')
            ->assertOk()
            ->assertDontSee('Not provided by source')
            ->assertDontSee('Collection-level classification')
            ->assertDontSee('not an independent verdict');

        $this->get('/hadith/muslim/1')
            ->assertOk()
            ->assertDontSee('Not provided by source')
            ->assertDontSee('Collection-level classification')
            ->assertDontSee('Unknown', false);
    }

    /**
     * The query count must not grow with the page size, or a 100-row page costs
     * five times a 20-row one for the same work.
     */
    public function test_the_query_count_is_identical_at_twenty_and_one_hundred_per_page(): void
    {
        $collection = $this->collection();
        $book = HadithBook::factory()->for($collection, 'collection')->create(['book_number' => '1']);

        foreach (range(1, 12) as $i) {
            $chapter = HadithChapter::factory()->for($book, 'book')->create([
                'chapter_number' => (string) $i, 'sort_order' => $i,
            ]);
            $this->hadithIn($book, $collection, $chapter, (string) $i, $i);
        }

        $count = function (int $perPage): int {
            $service = app(HadithBrowseService::class);
            $collection = $service->findCollection('bukhari');
            $book = $service->findBook($collection, '1');

            DB::flushQueryLog();
            DB::enableQueryLog();
            $result = $service->bookContents($book, $perPage);
            // Force the lazy pieces the view touches.
            $result['groups']->each(fn (array $g) => $g['hadiths']->each(fn ($h) => $h->textFor('ar')));
            $queries = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $queries;
        };

        $this->assertSame($count(4), $count(12), 'query count must not depend on page size');
    }
}
