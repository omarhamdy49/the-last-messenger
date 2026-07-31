<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Hadith;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithChapter;
use App\Domain\Hadith\Models\HadithCollection;
use App\Domain\Hadith\Models\HadithGrading;
use App\Domain\Hadith\Models\HadithText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Covers the ten endpoints of contracts/hadith-api.md (T179–T183).
 *
 * There is no 401 case: every endpoint is an unauthenticated read, so there is
 * no credential to omit. plan.md records that deviation from Constitution VI,
 * and the 401 requirement is met by the admin routes.
 */
class HadithApiTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = '/api/v1/hadith';

    private HadithCollection $collection;

    private HadithBook $book;

    private Hadith $hadith;

    protected function setUp(): void
    {
        parent::setUp();

        $this->collection = HadithCollection::factory()->create([
            'code' => 'bukhari',
            'name' => ['ar' => 'صحيح البخاري', 'en' => 'Sahih al-Bukhari'],
            'author' => ['ar' => 'البخاري', 'en' => 'Al-Bukhari'],
            'about' => ['ar' => 'عن الكتاب', 'en' => 'About the collection'],
            'reference_template' => 'Sahih al-Bukhari {hadithNumber}',
            'has_volumes' => true,
            'featured' => true,
        ]);

        $this->book = HadithBook::factory()->for($this->collection, 'collection')->create([
            'book_number' => '1',
            'title' => ['ar' => 'بدء الوحى', 'en' => 'Revelation'],
            'intro' => ['ar' => 'مقدمة', 'en' => 'Book intro'],
        ]);

        $chapter = HadithChapter::factory()->for($this->book, 'book')->create([
            'chapter_number' => '1',
            'title' => ['ar' => 'باب', 'en' => 'Chapter one'],
        ]);

        $this->hadith = Hadith::query()->create([
            'collection_id' => $this->collection->id,
            'book_id' => $this->book->id,
            'chapter_id' => $chapter->id,
            'hadith_number' => '1',
            'in_book_number' => 1,
            'volume_number' => '1',
            'sunnah_urn' => 10,
            'sunnah_urn_en' => 100010,
            'is_published' => true,
        ]);

        HadithText::factory()->for($this->hadith, 'hadith')->arabic('إِنَّمَا الأَعْمَالُ بِالنِّيَّاتِ')->create();
        HadithText::factory()->for($this->hadith, 'hadith')->english('Actions are judged by intentions')->create();
        HadithGrading::factory()->for($this->hadith, 'hadith')->create();
        DB::table('hadith_hadiths')->where('id', $this->hadith->id)->update(['display_grade' => 'sahih']);
    }

    // ------------------------------------------------------- §1 collections

    public function test_it_lists_published_collections(): void
    {
        HadithCollection::factory()->unpublished()->create(['code' => 'hidden']);

        $response = $this->getJson(self::BASE.'/collections');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.code', 'bukhari')
            ->assertJsonPath('data.0.total_hadiths', 1)
            ->assertJsonMissing(['code' => 'hidden']);
    }

    /** `about` belongs to the detail endpoint only. */
    public function test_the_list_omits_about(): void
    {
        $this->getJson(self::BASE.'/collections')
            ->assertOk()
            ->assertJsonMissingPath('data.0.about');
    }

    // -------------------------------------------------------- §2 collection

    public function test_it_returns_one_collection_with_about(): void
    {
        // Accept-Language is explicit: the test client sends
        // "en-us,en;q=0.5" by default, so an unheadered request legitimately
        // gets English back.
        $this->getJson(self::BASE.'/collections/bukhari', ['Accept-Language' => 'ar'])
            ->assertOk()
            ->assertJsonPath('data.code', 'bukhari')
            ->assertJsonPath('data.about', 'عن الكتاب');
    }

    public function test_an_unknown_collection_is_404(): void
    {
        $this->getJson(self::BASE.'/collections/nosuch')
            ->assertNotFound()
            ->assertJsonPath('success', false);
    }

    public function test_an_unpublished_collection_is_404_not_an_empty_200(): void
    {
        $this->collection->update(['is_published' => false]);

        $this->getJson(self::BASE.'/collections/bukhari')->assertNotFound();
    }

    // ------------------------------------------------------------- §3/§4 books

    public function test_it_lists_books_with_counts(): void
    {
        $this->getJson(self::BASE.'/collections/bukhari/books')
            ->assertOk()
            ->assertJsonPath('data.0.book_number', '1')
            ->assertJsonPath('data.0.hadith_count', 1);
    }

    public function test_synthetic_books_are_excluded_from_the_list(): void
    {
        HadithBook::factory()->for($this->collection, 'collection')->synthetic()->create();

        $this->getJson(self::BASE.'/collections/bukhari/books')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_it_returns_one_book_with_intro(): void
    {
        $this->getJson(self::BASE.'/collections/bukhari/books/1', ['Accept-Language' => 'ar'])
            ->assertOk()
            ->assertJsonPath('data.intro', 'مقدمة');
    }

    public function test_an_unknown_book_is_404(): void
    {
        $this->getJson(self::BASE.'/collections/bukhari/books/999')->assertNotFound();
    }

    // ---------------------------------------------------------- §5 chapters

    public function test_it_lists_chapters(): void
    {
        $this->getJson(self::BASE.'/collections/bukhari/books/1/chapters')
            ->assertOk()
            ->assertJsonPath('data.0.chapter_number', '1');
    }

    /** A chapterless collection yields 200 with an empty list, never a 404. */
    public function test_a_book_without_chapters_returns_an_empty_list(): void
    {
        $collection = HadithCollection::factory()->create(['code' => 'ahmad', 'has_chapters' => false]);
        HadithBook::factory()->for($collection, 'collection')->create(['book_number' => '1']);

        $this->getJson(self::BASE.'/collections/ahmad/books/1/chapters')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    // ----------------------------------------------------- §6 book hadiths

    public function test_it_paginates_a_books_hadiths(): void
    {
        $this->getJson(self::BASE.'/collections/bukhari/books/1/hadiths')
            ->assertOk()
            ->assertJsonPath('data.0.hadith_number', '1')
            ->assertJsonStructure(['meta' => ['current_page', 'per_page', 'total']]);
    }

    /** Query count must not grow with page size. */
    public function test_the_query_count_is_constant_at_twenty_and_one_hundred_per_page(): void
    {
        foreach (range(2, 12) as $i) {
            $hadith = Hadith::query()->create([
                'collection_id' => $this->collection->id,
                'book_id' => $this->book->id,
                'hadith_number' => (string) $i,
                'in_book_number' => $i,
                'is_published' => true,
            ]);
            HadithText::factory()->for($hadith, 'hadith')->arabic()->create();
        }

        $count = function (int $perPage): int {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->getJson(self::BASE."/collections/bukhari/books/1/hadiths?per_page={$perPage}")->assertOk();
            $n = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $n;
        };

        $this->assertSame($count(2), $count(100));
    }

    // ------------------------------------------------------ §7 canonical

    public function test_it_returns_a_hadith_by_its_canonical_number(): void
    {
        $this->getJson(self::BASE.'/collections/bukhari/hadiths/1')
            ->assertOk()
            ->assertJsonPath('data.hadith_number', '1')
            ->assertJsonPath('data.display_grade', 'sahih')
            ->assertJsonPath('data.reference.canonical', 'Sahih al-Bukhari 1')
            ->assertJsonPath('data.reference.in_book', 'Book 1, Hadith 1')
            ->assertJsonPath('data.reference.translation_deprecated', true);
    }

    public function test_a_non_numeric_hadith_number_resolves(): void
    {
        Hadith::query()->create([
            'collection_id' => $this->collection->id,
            'book_id' => $this->book->id,
            'hadith_number' => '1a',
            'in_book_number' => 2,
            'is_published' => true,
        ]);

        $this->getJson(self::BASE.'/collections/bukhari/hadiths/1a')
            ->assertOk()
            ->assertJsonPath('data.hadith_number', '1a');
    }

    public function test_an_unknown_hadith_number_is_404(): void
    {
        $this->getJson(self::BASE.'/collections/bukhari/hadiths/99999')->assertNotFound();
    }

    public function test_an_unpublished_hadith_is_404(): void
    {
        $this->hadith->update(['is_published' => false]);

        $this->getJson(self::BASE.'/collections/bukhari/hadiths/1')->assertNotFound();
    }

    /** Only locales that have text appear — never an entry with an empty body. */
    public function test_texts_contain_only_populated_locales(): void
    {
        $arabicOnly = Hadith::query()->create([
            'collection_id' => $this->collection->id,
            'book_id' => $this->book->id,
            'hadith_number' => '2',
            'in_book_number' => 2,
            'is_published' => true,
        ]);
        HadithText::factory()->for($arabicOnly, 'hadith')->arabic()->create();

        $this->getJson(self::BASE.'/collections/bukhari/hadiths/2')
            ->assertOk()
            ->assertJsonCount(1, 'data.texts')
            ->assertJsonPath('data.texts.0.locale', 'ar');
    }

    // ----------------------------------------------------------- §8 urn

    public function test_it_resolves_an_arabic_urn(): void
    {
        $this->getJson(self::BASE.'/hadiths/10')
            ->assertOk()
            ->assertJsonPath('data.hadith_number', '1');
    }

    public function test_it_resolves_an_english_urn(): void
    {
        $this->getJson(self::BASE.'/hadiths/100010')
            ->assertOk()
            ->assertJsonPath('data.hadith_number', '1');
    }

    /** ~35% of records carry no urn, so a miss is ordinary, not an error. */
    public function test_an_unknown_urn_is_a_clean_404(): void
    {
        $this->getJson(self::BASE.'/hadiths/999999')
            ->assertNotFound()
            ->assertJsonPath('success', false);
    }

    // -------------------------------------------------------- §9 random

    public function test_it_returns_a_random_published_hadith(): void
    {
        $this->getJson(self::BASE.'/hadiths/random')
            ->assertOk()
            ->assertJsonPath('data.hadith_number', '1');
    }

    /** `random` is a static segment and must not be read as a urn. */
    public function test_random_does_not_collide_with_the_urn_route(): void
    {
        $this->getJson(self::BASE.'/hadiths/random')->assertOk();
    }

    // --------------------------------------------------------- §10 search

    public function test_search_returns_422_without_a_query(): void
    {
        $this->getJson(self::BASE.'/search')
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors('q');
    }

    public function test_search_returns_422_for_a_one_character_query(): void
    {
        $this->getJson(self::BASE.'/search?q=x')->assertStatus(422);
    }

    public function test_search_returns_422_for_an_invalid_grade(): void
    {
        $this->getJson(self::BASE.'/search?q=test&grade=excellent')->assertStatus(422);
    }

    // ------------------------------------------------------------ locale

    public function test_accept_language_selects_the_response_locale(): void
    {
        $this->getJson(self::BASE.'/collections', ['Accept-Language' => 'en'])
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Sahih al-Bukhari');
    }

    /** An unsupported or hostile Accept-Language falls back to Arabic. */
    public function test_an_unsupported_accept_language_falls_back_to_arabic(): void
    {
        $this->getJson(self::BASE.'/collections', ['Accept-Language' => 'zz'])
            ->assertOk()
            ->assertJsonPath('data.0.name', 'صحيح البخاري');
    }
}
