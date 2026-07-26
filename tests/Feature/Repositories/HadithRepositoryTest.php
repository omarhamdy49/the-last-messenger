<?php

declare(strict_types=1);

namespace Tests\Feature\Repositories;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithCollection;
use App\Domain\Hadith\Models\HadithTranslation;
use App\Domain\Hadith\Repositories\HadithRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Integration tests for HadithRepository.
 *
 * These tests hit the real database (SQLite :memory: by default, or a MySQL
 * test database when TEST_DB_CONNECTION=mysql is set).
 *
 * FULLTEXT searches are MySQL-only. Any test that invokes FullTextSearchHelper
 * with a term >= 3 characters will emit MATCH...AGAINST SQL, which SQLite does
 * not support. Those tests are annotated with a runtime skip when running under
 * SQLite so the suite stays green on both drivers.
 */
class HadithRepositoryTest extends TestCase
{
    use RefreshDatabase;

    // ---------------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------------

    /**
     * True when the test DB driver is SQLite (the default in phpunit.xml).
     */
    private function isUsingSqlite(): bool
    {
        return DB::getDriverName() === 'sqlite';
    }

    /**
     * Create the minimum hierarchy: collection → book → hadith → translation.
     * Returns the Hadith model.
     */
    private function createHadithWithTranslation(
        string $hadithNumber = '1',
        string $gradingStatus = Hadith::GRADING_SAHIH,
        string $arabicText = 'إِنَّمَا الْأَعْمَالُ بِالنِّيَّاتِ'
    ): Hadith {
        $collection = HadithCollection::create([
            'name_ar' => 'صحيح البخاري',
            'name_en' => 'Sahih Bukhari',
            'slug_ar' => 'صحيح-البخاري',
            'slug_en' => 'sahih-bukhari',
            'abbreviation' => 'BUK',
        ]);

        $book = HadithBook::create([
            'collection_id' => $collection->id,
            'title_ar' => 'كتاب الوحي',
            'title_en' => 'Book of Revelation',
        ]);

        $hadith = Hadith::create([
            'book_id' => $book->id,
            'hadith_number' => $hadithNumber,
            'grading_status' => $gradingStatus,
        ]);

        HadithTranslation::create([
            'hadith_id' => $hadith->id,
            'language_code' => 'ar',
            'text' => $arabicText,
        ]);

        return $hadith->fresh();
    }

    // ---------------------------------------------------------------------------
    // getByBookAndNumber()
    // ---------------------------------------------------------------------------

    public function test_get_by_book_and_number_returns_matching_hadith(): void
    {
        $hadith = $this->createHadithWithTranslation('42');

        $repo = app(HadithRepositoryInterface::class);
        $result = $repo->getByBookAndNumber($hadith->book_id, '42');

        $this->assertNotNull($result);
        $this->assertEquals('42', $result->hadith_number);
        $this->assertEquals($hadith->id, $result->id);
    }

    public function test_get_by_book_and_number_returns_null_for_wrong_number(): void
    {
        $hadith = $this->createHadithWithTranslation('1');

        $repo = app(HadithRepositoryInterface::class);
        $result = $repo->getByBookAndNumber($hadith->book_id, '999');

        $this->assertNull($result);
    }

    public function test_get_by_book_and_number_returns_null_for_wrong_book(): void
    {
        $this->createHadithWithTranslation('1');

        $repo = app(HadithRepositoryInterface::class);
        $result = $repo->getByBookAndNumber(999999, '1');

        $this->assertNull($result);
    }

    public function test_get_by_book_and_number_eager_loads_translations(): void
    {
        $hadith = $this->createHadithWithTranslation('7');

        $repo = app(HadithRepositoryInterface::class);
        $result = $repo->getByBookAndNumber($hadith->book_id, '7');

        $this->assertNotNull($result);
        // Translations must be loaded without triggering a lazy query
        $this->assertTrue($result->relationLoaded('translations'));
        $this->assertNotEmpty($result->translations);
    }

    // ---------------------------------------------------------------------------
    // Eager-loading / N+1 check — paginateWithFilters()
    // ---------------------------------------------------------------------------

    public function test_paginate_with_filters_does_not_cause_n_plus_one(): void
    {
        // Create 10 hadiths, each with an Arabic translation.
        $collection = HadithCollection::create([
            'name_ar' => 'صحيح مسلم',
            'name_en' => 'Sahih Muslim',
            'slug_ar' => 'صحيح-مسلم',
            'slug_en' => 'sahih-muslim',
            'abbreviation' => 'MUS',
        ]);

        $book = HadithBook::create([
            'collection_id' => $collection->id,
            'title_ar' => 'كتاب الإيمان',
            'title_en' => 'Book of Faith',
        ]);

        for ($i = 1; $i <= 10; $i++) {
            $hadith = Hadith::create([
                'book_id' => $book->id,
                'hadith_number' => (string) $i,
                'grading_status' => Hadith::GRADING_SAHIH,
            ]);

            HadithTranslation::create([
                'hadith_id' => $hadith->id,
                'language_code' => 'ar',
                'text' => "نص الحديث رقم {$i}",
            ]);
        }

        $queryCount = 0;
        DB::listen(static function () use (&$queryCount): void {
            $queryCount++;
        });

        $repo = app(HadithRepositoryInterface::class);
        $repo->paginateWithFilters(10);

        // With proper eager loading: 1 count query + 1 data query + 1 translations
        // batch = 3 queries maximum. The repository also loads book relation, so
        // allow up to 4 to be safe, but never 10+ (one-per-row).
        $this->assertLessThanOrEqual(
            4,
            $queryCount,
            "paginateWithFilters() issued {$queryCount} queries for 10 rows — check for N+1."
        );
    }

    // ---------------------------------------------------------------------------
    // Filter behaviour — paginateWithFilters()
    // ---------------------------------------------------------------------------

    public function test_paginate_with_filters_filters_by_grading_status(): void
    {
        $collection = HadithCollection::create([
            'name_ar' => 'سنن أبي داود',
            'name_en' => 'Sunan Abu Dawud',
            'slug_ar' => 'سنن-ابي-داود',
            'slug_en' => 'sunan-abu-dawud',
            'abbreviation' => 'ABD',
        ]);

        $book = HadithBook::create([
            'collection_id' => $collection->id,
            'title_ar' => 'كتاب الصلاة',
            'title_en' => 'Book of Prayer',
        ]);

        Hadith::create(['book_id' => $book->id, 'hadith_number' => '1', 'grading_status' => Hadith::GRADING_SAHIH]);
        Hadith::create(['book_id' => $book->id, 'hadith_number' => '2', 'grading_status' => Hadith::GRADING_DAIF]);
        Hadith::create(['book_id' => $book->id, 'hadith_number' => '3', 'grading_status' => Hadith::GRADING_DAIF]);

        $repo = app(HadithRepositoryInterface::class);
        $results = $repo->paginateWithFilters(15, '', 'hadith_number', ['grading_status' => Hadith::GRADING_DAIF]);

        $this->assertEquals(2, $results->total());
        foreach ($results->items() as $item) {
            $this->assertEquals(Hadith::GRADING_DAIF, $item->grading_status);
        }
    }

    public function test_paginate_with_filters_returns_all_when_no_filters_applied(): void
    {
        $collection = HadithCollection::create([
            'name_ar' => 'جامع الترمذي',
            'name_en' => 'Jami at-Tirmidhi',
            'slug_ar' => 'جامع-الترمذي',
            'slug_en' => 'jami-at-tirmidhi',
            'abbreviation' => 'TIR',
        ]);

        $book = HadithBook::create([
            'collection_id' => $collection->id,
            'title_ar' => 'كتاب الزهد',
            'title_en' => 'Book of Zuhd',
        ]);

        for ($i = 1; $i <= 5; $i++) {
            Hadith::create(['book_id' => $book->id, 'hadith_number' => (string) $i, 'grading_status' => Hadith::GRADING_HASAN]);
        }

        $repo = app(HadithRepositoryInterface::class);
        $results = $repo->paginateWithFilters(15);

        $this->assertEquals(5, $results->total());
    }

    // ---------------------------------------------------------------------------
    // FULLTEXT search — search()
    // ---------------------------------------------------------------------------

    public function test_search_returns_hadith_whose_translation_contains_term(): void
    {
        if ($this->isUsingSqlite()) {
            $this->markTestSkipped(
                'MATCH...AGAINST FULLTEXT search is MySQL-only. '.
                'Run with TEST_DB_CONNECTION=mysql to enable this test.'
            );
        }

        $hadith = $this->createHadithWithTranslation(
            '1',
            Hadith::GRADING_SAHIH,
            'إنما الأعمال بالنيات وإنما لكل امرئ ما نوى'
        );

        $repo = app(HadithRepositoryInterface::class);
        DB::commit(); // InnoDB FULLTEXT indexes expose committed rows only.
        $results = $repo->search('الأعمال');

        $this->assertGreaterThan(0, $results->total());
        $ids = collect($results->items())->pluck('id')->toArray();
        $this->assertContains($hadith->id, $ids);
    }

    public function test_search_returns_empty_when_term_has_no_matches(): void
    {
        if ($this->isUsingSqlite()) {
            $this->markTestSkipped(
                'MATCH...AGAINST FULLTEXT search is MySQL-only. '.
                'Run with TEST_DB_CONNECTION=mysql to enable this test.'
            );
        }

        $this->createHadithWithTranslation('1', Hadith::GRADING_SAHIH, 'نص الحديث المعروف');

        $repo = app(HadithRepositoryInterface::class);
        $results = $repo->search('xyznonexistent');

        $this->assertEquals(0, $results->total());
    }

    // ---------------------------------------------------------------------------
    // getByBook() — column projection
    // ---------------------------------------------------------------------------

    public function test_get_by_book_returns_only_projected_columns(): void
    {
        $hadith = $this->createHadithWithTranslation('1');

        $repo = app(HadithRepositoryInterface::class);
        $results = $repo->getByBook($hadith->book_id);

        $this->assertNotEmpty($results);

        // The repository selects id, book_id, hadith_number, grading_status,
        // grading_agreement, companion_id — confirm the critical ones are present.
        $first = $results->first();
        $this->assertNotNull($first->id);
        $this->assertNotNull($first->hadith_number);
        $this->assertNotNull($first->grading_status);
    }

    // ---------------------------------------------------------------------------
    // bulkDelete()
    // ---------------------------------------------------------------------------

    public function test_bulk_delete_removes_specified_hadiths(): void
    {
        $collection = HadithCollection::create([
            'name_ar' => 'صحيح ابن حبان',
            'name_en' => 'Sahih Ibn Hibban',
            'slug_ar' => 'صحيح-ابن-حبان',
            'slug_en' => 'sahih-ibn-hibban',
            'abbreviation' => 'IBH',
        ]);

        $book = HadithBook::create([
            'collection_id' => $collection->id,
            'title_ar' => 'كتاب الجهاد',
            'title_en' => 'Book of Jihad',
        ]);

        $h1 = Hadith::create(['book_id' => $book->id, 'hadith_number' => '1', 'grading_status' => Hadith::GRADING_SAHIH]);
        $h2 = Hadith::create(['book_id' => $book->id, 'hadith_number' => '2', 'grading_status' => Hadith::GRADING_SAHIH]);
        $h3 = Hadith::create(['book_id' => $book->id, 'hadith_number' => '3', 'grading_status' => Hadith::GRADING_SAHIH]);

        $repo = app(HadithRepositoryInterface::class);
        $deleted = $repo->bulkDelete([$h1->id, $h2->id]);

        $this->assertEquals(2, $deleted);
        $this->assertDatabaseMissing('hadith_hadiths', ['id' => $h1->id]);
        $this->assertDatabaseMissing('hadith_hadiths', ['id' => $h2->id]);
        $this->assertDatabaseHas('hadith_hadiths', ['id' => $h3->id]);
    }
}
