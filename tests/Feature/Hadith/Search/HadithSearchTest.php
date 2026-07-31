<?php

declare(strict_types=1);

namespace Tests\Feature\Hadith\Search;

use App\Domain\Hadith\DTOs\SearchCriteria;
use App\Domain\Hadith\Enums\HadithGrade;
use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithCollection;
use App\Domain\Hadith\Models\HadithText;
use App\Domain\Hadith\Services\ArabicTextNormalizer;
use App\Domain\Hadith\Services\HadithSearchService;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Tests\TestCase;

/**
 * Requires innodb_ft_min_token_size = 2 and a rebuilt FULLTEXT index; without
 * them the two-character assertions fail, which is the point of asserting them.
 */
class HadithSearchTest extends TestCase
{
    // NOT RefreshDatabase: it wraps each test in a transaction, and InnoDB does
    // not update a FULLTEXT index until commit, so MATCH would see no rows.
    use DatabaseTruncation;

    /**
     * FULLTEXT is not transactional: InnoDB does not update the index until
     * commit, so RefreshDatabase's wrapping transaction would leave MATCH seeing
     * no rows at all. Truncation is used instead.
     *
     * Only the hadith tables are truncated. Wiping everything also empties the
     * reference data that migrations seed — `languages` above all — and nothing
     * restores it, which silently breaks every later test in the run.
     *
     * @var list<string>
     */
    protected array $tablesToTruncate = [
        'hadith_gradings',
        'hadith_texts',
        'hadith_hadiths',
        'hadith_chapters',
        'hadith_books',
        'hadith_scholars',
        'hadith_collections',
        'hadith_import_runs',
    ];

    private HadithSearchService $search;

    private ArabicTextNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->search = app(HadithSearchService::class);
        $this->normalizer = app(ArabicTextNormalizer::class);
    }

    private function seedHadith(string $arabic, string $code = 'bukhari', ?string $english = null, HadithGrade $grade = HadithGrade::SAHIH): Hadith
    {
        $collection = HadithCollection::query()->firstOrCreate(
            ['code' => $code],
            ['name' => ['ar' => $code, 'en' => $code], 'is_published' => true, 'reference_template' => $code.' {hadithNumber}'],
        );
        $book = HadithBook::factory()->for($collection, 'collection')->create();

        $hadith = Hadith::query()->create([
            'collection_id' => $collection->id,
            'book_id' => $book->id,
            'hadith_number' => (string) random_int(1, 999999),
            'in_book_number' => 1,
            'is_published' => true,
        ]);
        // display_grade is derived, so it is set the way the app sets it.
        Hadith::query()->whereKey($hadith->id)->update(['display_grade' => $grade->value]);

        HadithText::query()->create([
            'hadith_id' => $hadith->id, 'locale' => 'ar',
            'body' => $arabic, 'search_body' => $this->normalizer->forLocale($arabic, 'ar'),
        ]);

        if ($english !== null) {
            HadithText::query()->create([
                'hadith_id' => $hadith->id, 'locale' => 'en',
                'body' => $english, 'search_body' => $english,
            ]);
        }

        return $hadith->refresh();
    }

    private function find(string $query, array $extra = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->search->search(new SearchCriteria(
            query: $query,
            collectionCode: $extra['collectionCode'] ?? null,
            grade: $extra['grade'] ?? null,
            locale: $extra['locale'] ?? null,
            page: $extra['page'] ?? 1,
            requestedPerPage: $extra['requestedPerPage'] ?? 20,
        ));
    }

    public function test_a_two_character_arabic_term_returns_results(): void
    {
        // "ما" is a real two-character token in this text. At the MySQL default
        // of 3 it is unindexable and this returns nothing.
        $this->seedHadith('إنما الأعمال بالنيات وإنما لكل امرئ ما نوى');

        $this->assertSame(1, $this->find('ما')->total(), 'two-character tokens need innodb_ft_min_token_size = 2');
    }

    public function test_a_diacritised_query_and_its_plain_form_return_identical_sets(): void
    {
        $this->seedHadith('إِنَّمَا الأَعْمَالُ بِالنِّيَّاتِ');

        $withMarks = $this->find('الأَعْمَالُ')->pluck('id')->all();
        $without = $this->find('الاعمال')->pluck('id')->all();

        $this->assertNotEmpty($withMarks);
        $this->assertSame($withMarks, $without);
    }

    public function test_alef_and_ya_variants_fold_in_the_query(): void
    {
        $this->seedHadith('وإنما لكل امرئ ما نوى');

        $this->assertSame(1, $this->find('نوي')->total(), 'alef maqsura in the corpus must match ya in the query');
    }

    public function test_every_term_is_required_so_more_words_narrow_the_search(): void
    {
        $this->seedHadith('إنما الأعمال بالنيات');
        $this->seedHadith('إنما الماء من الماء');

        $this->assertSame(2, $this->find('انما')->total());
        $this->assertSame(1, $this->find('انما الاعمال')->total());
    }

    public function test_it_searches_english_text_too(): void
    {
        $this->seedHadith('إنما الأعمال بالنيات', english: 'Actions are judged by intentions');

        $this->assertSame(1, $this->find('intentions')->total());
    }

    public function test_a_bilingual_hadith_occupies_one_result_slot(): void
    {
        // Matching in both locales must not return the record twice.
        $this->seedHadith('نية النية', english: 'niyyah niyyah');

        $this->assertSame(1, $this->find('نيه')->total());
    }

    public function test_boolean_mode_operators_in_the_query_are_neutralised(): void
    {
        $this->seedHadith('إنما الأعمال بالنيات');

        // An unbalanced quote is a MySQL syntax error if passed through.
        $this->assertSame(1, $this->find('"الاعمال')->total());
        $this->assertSame(1, $this->find('+الاعمال -')->total());
    }

    public function test_a_query_of_only_noise_matches_nothing_without_erroring(): void
    {
        $this->seedHadith('إنما الأعمال بالنيات');

        $this->assertSame(0, $this->find('+ - " (')->total());
    }

    public function test_the_collection_filter_narrows_results(): void
    {
        $this->seedHadith('إنما الأعمال بالنيات', 'bukhari');
        $this->seedHadith('إنما الأعمال بالنيات', 'muslim');

        $this->assertSame(2, $this->find('الاعمال')->total());
        $this->assertSame(1, $this->find('الاعمال', ['collectionCode' => 'muslim'])->total());
    }

    public function test_the_grade_filter_narrows_results(): void
    {
        $this->seedHadith('إنما الأعمال بالنيات', grade: HadithGrade::SAHIH);
        $this->seedHadith('إنما الأعمال بالنيات', 'muslim', grade: HadithGrade::DAIF);

        $this->assertSame(1, $this->find('الاعمال', ['grade' => HadithGrade::DAIF])->total());
    }

    public function test_the_locale_filter_restricts_which_text_rows_are_searched(): void
    {
        $this->seedHadith('إنما الأعمال بالنيات', english: 'Actions are judged by intentions');

        $this->assertSame(0, $this->find('intentions', ['locale' => 'ar'])->total());
        $this->assertSame(1, $this->find('intentions', ['locale' => 'en'])->total());
    }

    public function test_unpublished_hadiths_never_appear_in_results(): void
    {
        $hadith = $this->seedHadith('إنما الأعمال بالنيات');
        Hadith::query()->whereKey($hadith->id)->update(['is_published' => false]);

        $this->assertSame(0, $this->find('الاعمال')->total());
    }

    public function test_an_oversized_page_size_clamps_to_one_hundred(): void
    {
        $criteria = new SearchCriteria(query: 'x', requestedPerPage: 5000);

        $this->assertSame(100, $criteria->perPage());
    }

    /**
     * MySQL rewrites COUNT(*) on a MATCH query and can report more rows than the
     * table holds, which would make the paginator offer pages that do not exist.
     */
    public function test_the_total_never_exceeds_the_number_of_matching_hadiths(): void
    {
        foreach (range(1, 5) as $i) {
            $this->seedHadith('إنما الأعمال بالنيات رقم '.$i, english: 'Actions number '.$i);
        }

        $paginator = $this->find('الاعمال', ['requestedPerPage' => 2]);

        $this->assertSame(5, $paginator->total());
        $this->assertCount(2, $paginator->items());
        $this->assertSame(3, $paginator->lastPage());
    }

    /**
     * DatabaseTruncation clears tables BEFORE each test, so the last one in the
     * class leaves its rows committed for whatever runs next — and a
     * RefreshDatabase test then cannot create its own `bukhari`. Clearing on the
     * way out keeps the blast radius inside this class.
     */
    protected function tearDown(): void
    {
        foreach ($this->tablesToTruncate as $table) {
            \Illuminate\Support\Facades\DB::table($table)->delete();
        }

        parent::tearDown();
    }
}
