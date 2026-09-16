<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Foundation\Testing\DatabaseTruncation;

/**
 * For tests that exercise MySQL FULLTEXT search.
 *
 * WHY THIS EXISTS
 *
 * InnoDB does not populate a FULLTEXT index until the inserting transaction COMMITS.
 * `RefreshDatabase` wraps each test in a transaction it rolls back, so `MATCH…AGAINST`
 * against rows the test just seeded matches nothing — the query is valid and silently
 * returns zero rows, which reads as a search bug rather than a test-isolation artefact.
 * Any test touching unified search therefore has to commit, which means truncation.
 *
 * WHY IT IS NOT JUST `use DatabaseTruncation`
 *
 * DatabaseTruncation truncates BEFORE each test and never after, so the last test in the
 * class leaves its committed rows in the database. Classes using RefreshDatabase open a
 * transaction over whatever state they find and assume it is clean — so those leftovers
 * surface as inflated counts in unrelated tests (this is how a passing
 * SeerahEventRepositoryTest started reporting 3 events where it seeded 2).
 *
 * Truncating in tearDown as well keeps the two strategies able to coexist in one suite.
 */
trait CommitsForFullTextSearch
{
    use DatabaseTruncation;

    /**
     * Reference tables populated by DATA MIGRATIONS, not by seeders.
     *
     * This is the subtle half. RefreshDatabase runs migrations once for the whole suite, so
     * anything a migration INSERTed exists exactly once — truncating it destroys it
     * permanently for every later test, and nothing restores it. That is not hypothetical:
     * wiping `seerah_event_types` (seeded by
     * 2026_06_18_100001_seed_event_types_and_link_seerah_events) made
     * SeerahEventRepositoryTest::test_get_by_type fail with 0 results, several classes later,
     * for no visible reason.
     *
     * The data tables those same migrations backfill (seerah_events,
     * quran_verse_translations, quran_verse_tafsirs) are deliberately NOT listed — tests
     * populate those themselves and they must be truncated.
     *
     * @var array<int, string>
     */
    protected array $exceptTables = [
        'seerah_event_types',
        'seerah_event_type_translations',
        'media_size_presets',
        'quran_translation_resources',
        'quran_tafsir_resources',
        'languages',
        'language_translations',
    ];

    protected function tearDown(): void
    {
        // DatabaseTruncation truncates BEFORE each test and never after, so without this the
        // last test in the class leaves its committed rows behind for RefreshDatabase classes
        // to trip over. Runs before parent::tearDown(), which destroys the application —
        // truncation needs a live container to resolve connections.
        $this->truncateDatabaseTables();

        parent::tearDown();
    }
}
