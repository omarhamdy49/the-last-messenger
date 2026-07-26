<?php

declare(strict_types=1);

namespace Tests\Feature\Repositories;

use App\Domain\Quran\Models\Surah;
use App\Domain\Quran\Models\Verse;
use App\Domain\Quran\Models\VerseTranslation;
use App\Domain\Quran\Repositories\VerseRepositoryInterface;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Integration tests for VerseRepository.
 *
 * Key concerns:
 *  - searchArabic() / search() use a single JOIN, not a two-step pluck+whereIn.
 *    We verify this by counting DB queries: one paginated query + one count = 2
 *    total for the search path (plus 1 for the eager-loaded surah relation = 3 max).
 *  - paginateWithFilters() applies surah/juz filters correctly.
 *  - The VERSE_COLUMNS projection is respected — 'id' (as quran_verses.id) must
 *    be present on results; no SELECT * is used.
 *
 * FULLTEXT (MATCH...AGAINST) is MySQL-only. Any test that invokes
 * FullTextSearchHelper with a term >= 3 chars will be skipped on SQLite.
 */
class VerseRepositoryTest extends TestCase
{
    use DatabaseTruncation;

    protected array $tablesToTruncate = [
        'quran_verse_translations',
        'quran_verses',
        'quran_surahs',
    ];

    protected function tearDown(): void
    {
        $this->truncateTablesForAllConnections();

        parent::tearDown();
    }

    // ---------------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------------

    private function isUsingSqlite(): bool
    {
        return DB::getDriverName() === 'sqlite';
    }

    /**
     * Create a Surah row, N Verse rows, and an Arabic VerseTranslation per verse.
     * Returns the Surah model.
     */
    private function createSurahWithVerses(
        int $revelationOrder,
        int $verseCount = 3,
        int $juz = 1
    ): Surah {
        $surah = Surah::create([
            'revelation_place' => 'makkah',
            'revelation_order' => $revelationOrder,
            'bismillah_pre' => true,
            'name_ar' => "سورة {$revelationOrder}",
            'name_en' => "Surah {$revelationOrder}",
            'transliteration' => "Surah-{$revelationOrder}",
            'verses_count' => $verseCount,
        ]);

        for ($v = 1; $v <= $verseCount; $v++) {
            $verse = Verse::create([
                'surah_id' => $surah->id,
                'verse_number' => $v,
                'verse_key' => "{$revelationOrder}:{$v}",
                'juz_number' => $juz,
                'hizb_number' => 1,
                'rub_el_hizb_number' => 1,
                'ruku_number' => 1,
                'manzil_number' => 1,
                'sajdah_number' => null,
                'page_number' => 1,
            ]);

            VerseTranslation::create([
                'verse_id' => $verse->id,
                'lang' => 'ar',
                'text' => "بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيمِ آية {$v}",
                'text_notashkeel' => "بسم الله الرحمن الرحيم اية {$v}",
            ]);
        }

        return $surah->fresh();
    }

    // ---------------------------------------------------------------------------
    // Column projection — verse_id / id is present in results
    // ---------------------------------------------------------------------------

    public function test_paginate_with_filters_result_contains_verse_id(): void
    {
        $surah = $this->createSurahWithVerses(1, 3);

        $repo = app(VerseRepositoryInterface::class);
        $results = $repo->paginateWithFilters(10);

        $this->assertNotEmpty($results->items());

        // The SELECT lists quran_verses.id (not SELECT *).
        // After hydration the id attribute must be accessible.
        $first = $results->first();
        $this->assertNotNull($first->id, 'verse id must be present — SELECT * was removed from VerseRepository.');
        $this->assertNotNull($first->verse_key, 'verse_key must be present in the projected columns.');
    }

    // ---------------------------------------------------------------------------
    // surah_id filter — paginateWithFilters()
    // ---------------------------------------------------------------------------

    public function test_paginate_with_filters_by_surah_id_returns_only_matching_verses(): void
    {
        $surah1 = $this->createSurahWithVerses(1, 3, 1);
        $surah2 = $this->createSurahWithVerses(2, 5, 1);

        $repo = app(VerseRepositoryInterface::class);
        $results = $repo->paginateWithFilters(20, '', 'verse_key', ['surah_id' => $surah1->id]);

        $this->assertEquals(3, $results->total());
        foreach ($results->items() as $verse) {
            $this->assertEquals($surah1->id, $verse->surah_id);
        }
    }

    // ---------------------------------------------------------------------------
    // juz_number filter — paginateWithFilters()
    // ---------------------------------------------------------------------------

    public function test_paginate_with_filters_by_juz_returns_only_matching_verses(): void
    {
        // Surah 1: juz 1, Surah 2: juz 2
        $this->createSurahWithVerses(1, 4, 1);
        $this->createSurahWithVerses(2, 2, 2);

        $repo = app(VerseRepositoryInterface::class);
        $results = $repo->paginateWithFilters(20, '', 'verse_key', ['juz_number' => 2]);

        $this->assertEquals(2, $results->total());
        foreach ($results->items() as $verse) {
            $this->assertEquals(2, $verse->juz_number);
        }
    }

    // ---------------------------------------------------------------------------
    // getBySurah() — eager loading
    // ---------------------------------------------------------------------------

    public function test_get_by_surah_returns_verses_in_order(): void
    {
        $surah = $this->createSurahWithVerses(1, 5);

        $repo = app(VerseRepositoryInterface::class);
        $results = $repo->getBySurah($surah->id);

        $this->assertCount(5, $results);

        // Verify ascending verse_number order
        $numbers = $results->pluck('verse_number')->toArray();
        $this->assertEquals([1, 2, 3, 4, 5], $numbers);
    }

    public function test_get_by_surah_eager_loads_translations(): void
    {
        $surah = $this->createSurahWithVerses(1, 2);

        $repo = app(VerseRepositoryInterface::class);
        $results = $repo->getBySurah($surah->id);

        $this->assertNotEmpty($results);
        $this->assertTrue($results->first()->relationLoaded('translations'));
    }

    // ---------------------------------------------------------------------------
    // searchArabic() — single JOIN, not two queries
    // ---------------------------------------------------------------------------

    public function test_search_arabic_uses_join_not_two_separate_queries(): void
    {
        if ($this->isUsingSqlite()) {
            $this->markTestSkipped(
                'MATCH...AGAINST FULLTEXT search is MySQL-only. '.
                'Run with TEST_DB_CONNECTION=mysql to enable this test.'
            );
        }

        $this->createSurahWithVerses(1, 3);

        $queryCount = 0;
        DB::listen(static function () use (&$queryCount): void {
            $queryCount++;
        });

        $repo = app(VerseRepositoryInterface::class);
        $repo->searchArabic('بسم الله');

        // Single-JOIN paginator issues: 1 data query + 1 count query, plus
        // the surah and translation eager loads = 4.
        // The old two-step approach adds another translations query.
        $this->assertLessThanOrEqual(
            4,
            $queryCount,
            "searchArabic() issued {$queryCount} queries — expected max 4 (JOIN approach). ".
            'The old pluck+whereIn approach would issue at least 5.'
        );
    }

    public function test_search_arabic_returns_matching_verses(): void
    {
        if ($this->isUsingSqlite()) {
            $this->markTestSkipped(
                'MATCH...AGAINST FULLTEXT search is MySQL-only. '.
                'Run with TEST_DB_CONNECTION=mysql to enable this test.'
            );
        }

        $surah = $this->createSurahWithVerses(1, 2);

        $repo = app(VerseRepositoryInterface::class);
        $results = $repo->searchArabic('بسم');

        $this->assertGreaterThan(0, $results->total());
        $surahIds = collect($results->items())->pluck('surah_id')->unique()->toArray();
        $this->assertContains($surah->id, $surahIds);
    }

    // ---------------------------------------------------------------------------
    // paginateWithFilters() with search — JOIN path, N+1 check
    // ---------------------------------------------------------------------------

    public function test_paginate_with_search_does_not_cause_n_plus_one(): void
    {
        if ($this->isUsingSqlite()) {
            $this->markTestSkipped(
                'MATCH...AGAINST FULLTEXT search is MySQL-only. '.
                'Run with TEST_DB_CONNECTION=mysql to enable this test.'
            );
        }

        // 2 surahs x 5 verses each = 10 verses
        $this->createSurahWithVerses(1, 5);
        $this->createSurahWithVerses(2, 5);

        $queryCount = 0;
        DB::listen(static function () use (&$queryCount): void {
            $queryCount++;
        });

        $repo = app(VerseRepositoryInterface::class);
        $repo->paginateWithFilters(10, 'بسم');

        // With JOIN approach: 1 count + 1 data + 1 surah eager load = 3 max.
        $this->assertLessThanOrEqual(
            3,
            $queryCount,
            "paginateWithFilters(search) issued {$queryCount} queries — expected ≤ 3."
        );
    }

    // ---------------------------------------------------------------------------
    // paginateWithFilters() without search — no JOIN, still efficient
    // ---------------------------------------------------------------------------

    public function test_paginate_without_search_does_not_join_translations_table(): void
    {
        $this->createSurahWithVerses(1, 5);

        $queryCount = 0;
        DB::listen(static function () use (&$queryCount): void {
            $queryCount++;
        });

        $repo = app(VerseRepositoryInterface::class);
        $repo->paginateWithFilters(10, '');

        // No JOIN means: 1 count + 1 data + 1 surah eager = 3 max.
        $this->assertLessThanOrEqual(
            3,
            $queryCount,
            "paginateWithFilters (no search) issued {$queryCount} queries — expected ≤ 3."
        );
    }

    // ---------------------------------------------------------------------------
    // getBySurahAndVerse()
    // ---------------------------------------------------------------------------

    public function test_get_by_surah_and_verse_returns_correct_verse(): void
    {
        $surah = $this->createSurahWithVerses(1, 7);

        $repo = app(VerseRepositoryInterface::class);
        $result = $repo->getBySurahAndVerse(1, 4);

        $this->assertNotNull($result);
        $this->assertEquals(4, $result->verse_number);
        $this->assertEquals($surah->id, $result->surah_id);
    }

    public function test_get_by_surah_and_verse_returns_null_when_not_found(): void
    {
        $this->createSurahWithVerses(1, 3);

        $repo = app(VerseRepositoryInterface::class);
        $result = $repo->getBySurahAndVerse(1, 999);

        $this->assertNull($result);
    }

    // ---------------------------------------------------------------------------
    // bulkDelete()
    // ---------------------------------------------------------------------------

    public function test_bulk_delete_removes_specified_verses(): void
    {
        $surah = $this->createSurahWithVerses(1, 4);
        $verses = Verse::where('surah_id', $surah->id)->orderBy('verse_number')->get();

        $repo = app(VerseRepositoryInterface::class);
        $deleted = $repo->bulkDelete([$verses[0]->id, $verses[1]->id]);

        $this->assertEquals(2, $deleted);
        $this->assertDatabaseMissing('quran_verses', ['id' => $verses[0]->id]);
        $this->assertDatabaseMissing('quran_verses', ['id' => $verses[1]->id]);
        $this->assertDatabaseHas('quran_verses', ['id' => $verses[2]->id]);
    }
}
