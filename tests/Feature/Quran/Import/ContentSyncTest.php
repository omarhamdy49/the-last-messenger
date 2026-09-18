<?php

declare(strict_types=1);

namespace Tests\Feature\Quran\Import;

use App\Domain\Quran\Import\QuranFoundation\Sync\ContentSyncService;
use App\Domain\Quran\Models\Chapter;
use App\Domain\Quran\Models\Mushaf;
use App\Domain\Quran\Models\QuranResource;
use App\Domain\Quran\Models\SyncRun;
use App\Domain\Quran\Models\SyncState;
use App\Domain\Quran\Models\Tafsir;
use App\Domain\Quran\Models\Translation;
use App\Domain\Quran\Models\Word;
use App\Domain\Quran\Models\WordGloss;
use Database\Seeders\QuranMushafSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Quran\Import\Concerns\FakesQuranFoundation;
use Tests\TestCase;

/**
 * Content Sync against real QF snapshot records (chapter 1 trimmed).
 */
final class ContentSyncTest extends TestCase
{
    use FakesQuranFoundation;
    use RefreshDatabase;

    private QuranResource $saheeh;

    private QuranResource $ibnKathir;

    private QuranResource $transliteration;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpQuranFoundation();
        $this->seed(QuranMushafSeeder::class);
        DB::table('quran_languages')->insert(['id' => 38, 'name' => 'English', 'iso_code' => 'en', 'direction' => 'ltr']);

        Chapter::factory()->number(1)->withVerses(7)->create();
        $this->wordsForChapterOne();

        $this->saheeh = QuranResource::factory()->translation()->qf(20)->create(['slug' => 'saheeh', 'sync_enabled' => true]);
        $this->ibnKathir = QuranResource::factory()->tafsir('en')->qf(169)->create(['slug' => 'ibn-kathir', 'sync_enabled' => true]);
        $this->transliteration = QuranResource::factory()->wordTransliteration()->qf(60)->create(['slug' => 'tl', 'sync_enabled' => true, 'language_iso' => null]);
        // A resource that is imported but not enabled must stay out of the filter.
        QuranResource::factory()->translation()->qf(85)->create(['slug' => 'haleem', 'sync_enabled' => false]);
    }

    public function test_the_filter_is_canonical_and_only_contains_enabled_resources(): void
    {
        $this->assertSame(
            'mushafs:1;tafsirs:169;translations:20;word_by_word_transliterations:60',
            app(ContentSyncService::class)->canonicalFilter(),
        );
    }

    public function test_a_two_page_bootstrap_fills_every_group_and_stores_the_token_from_the_last_page(): void
    {
        $this->fakeBootstrap();

        ['run' => $run, 'report' => $report] = app(ContentSyncService::class)->run();

        $this->assertTrue($report->passed(), json_encode($report->toArray(), JSON_PRETTY_PRINT) ?: '');
        $this->assertSame(SyncRun::STATUS_SUCCEEDED, $run->fresh()->status);
        $this->assertSame(4, $run->snapshots_fetched);

        // translations: 7 rows, footnotes as an id => text map
        $this->assertSame(7, Translation::query()->where('resource_id', $this->saheeh->id)->count());
        $first = Translation::query()->where('resource_id', $this->saheeh->id)->where('verse_id', 1)->firstOrFail();
        $this->assertStringContainsString('<sup foot_note=254011>', $first->text);
        $this->assertArrayHasKey('254011', $first->foot_notes);
        $this->assertSame(7, $this->saheeh->fresh()->records_count);

        $this->assertSame(7, Tafsir::query()->where('resource_id', $this->ibnKathir->id)->count());
        $this->assertGreaterThan(0, WordGloss::query()->where('resource_id', $this->transliteration->id)->count());
        $this->assertSame('en', $this->transliteration->fresh()->language_iso);

        $this->assertSame(1, DB::table('quran_mushaf_pages')->where('mushaf_id', 1)->count());
        $this->assertGreaterThan(0, DB::table('quran_mushaf_words')->where('mushaf_id', 1)->count());
        $this->assertTrue(Mushaf::query()->findOrFail(1)->is_active); // editor flag kept

        $state = SyncState::query()->firstOrFail();
        $this->assertSame('token-final', $state->sync_token);
        $this->assertSame(SyncState::STATUS_IDLE, $state->status);
    }

    public function test_a_second_bootstrap_writes_nothing_new(): void
    {
        $this->fakeBootstrap();
        app(ContentSyncService::class)->run();

        ['report' => $report] = app(ContentSyncService::class)->run(forceBootstrap: true);

        $this->assertTrue($report->passed());
        $this->assertSame(0, $report->written + $report->updated + $report->deleted);
    }

    public function test_a_failure_mid_run_keeps_the_previous_token(): void
    {
        $filter = app(ContentSyncService::class)->canonicalFilter();
        SyncState::query()->create(['resources_filter' => $filter, 'filter_hash' => SyncState::hashFilter($filter), 'sync_token' => 'token-old', 'status' => 'idle']);

        Http::fake(function (Request $request) {
            $url = urldecode($request->url());
            if (str_contains($url, 'cursor=page2')) {
                return Http::response(['message' => 'boom', 'type' => 'internal_server_error'], 500);
            }
            if (str_contains($url, 'resources/sync')) {
                return Http::response(['sync' => ['has_more' => true, 'next_page_url' => '/api/v4/resources/sync?cursor=page2', 'next_sync_token' => null, 'mutations' => []]]);
            }

            return null;
        });
        config(['quran.qf.max_retries' => 0]);

        ['run' => $run, 'report' => $report] = app(ContentSyncService::class)->run();

        $this->assertFalse($report->passed());
        $this->assertSame(SyncRun::STATUS_FAILED, $run->fresh()->status);
        $state = SyncState::query()->firstOrFail();
        $this->assertSame('token-old', $state->sync_token);
        $this->assertSame(SyncState::STATUS_FAILED, $state->status);
    }

    /**
     * A snapshot that dies mid-download (a large tafsir on a flaky link) used to be
     * reported as a bare "resources/sync:follow returned 0", which named neither the
     * resource nor even whether it was a feed page or a snapshot. The failure now
     * carries the group and resource id, and still refuses to advance the token.
     */
    public function test_a_failed_snapshot_names_the_resource_it_belongs_to(): void
    {
        $filter = app(ContentSyncService::class)->canonicalFilter();
        SyncState::query()->create(['resources_filter' => $filter, 'filter_hash' => SyncState::hashFilter($filter), 'sync_token' => 'token-old', 'status' => 'idle']);

        Http::fake(function (Request $request) {
            $url = urldecode($request->url());
            if (str_contains($url, 'snapshots/translations/20') || str_contains($url, 'snapshot-url')) {
                return Http::response(['message' => 'gone', 'type' => 'service_error'], 503);
            }
            if (str_contains($url, 'resources/sync')) {
                return Http::response(['sync' => [
                    'has_more' => false,
                    'next_sync_token' => 'token-new',
                    'sync_until_sequence' => 9,
                    'mutations' => [[
                        'sequence' => 9,
                        'type' => 'RESOURCE_INVALIDATE',
                        'resource_group' => 'translations',
                        'resource_id' => 20,
                        'snapshot_url' => '/api/v4/resources/snapshots/translations/20',
                    ]],
                ]]);
            }

            return null;
        });
        config(['quran.qf.max_retries' => 0]);

        ['run' => $run, 'report' => $report] = app(ContentSyncService::class)->run();

        $this->assertFalse($report->passed());
        $messages = implode(' ', array_column($report->failed, 'reason'));
        $this->assertStringContainsString('translations:20', $messages);
        $this->assertStringContainsString('snapshot', $messages);

        // The token must not move past a change that was never applied.
        $this->assertSame('token-old', SyncState::query()->firstOrFail()->sync_token);
        $this->assertSame(SyncRun::STATUS_FAILED, $run->fresh()->status);
    }

    public function test_incremental_sends_the_token_and_applies_every_mutation_type(): void
    {
        $this->fakeBootstrap();
        app(ContentSyncService::class)->run();

        Translation::query()->where('resource_id', $this->saheeh->id)->where('verse_id', 3)->update(['text' => 'editor text', 'is_locked' => true]);
        $row4 = Translation::query()->where('resource_id', $this->saheeh->id)->where('verse_id', 4)->firstOrFail();

        $sent = [];
        Http::fake(function (Request $request) use (&$sent, $row4) {
            $url = urldecode($request->url());
            if (! str_contains($url, 'resources/sync')) {
                return null;
            }
            $sent[] = $url;

            return Http::response(['sync' => [
                'sync_until_sequence' => 500, 'has_more' => false, 'next_page_url' => null, 'next_sync_token' => 'token-2',
                'mutations' => [
                    ['type' => 'ROW_UPDATE', 'resource_group' => 'translations', 'resource_id' => 20, 'record_type' => 'translation', 'record_key' => '1',
                        'data' => ['id' => 96343, 'verse_id' => 1, 'verse_key' => '1:1', 'text' => 'Updated 1:1', 'foot_notes' => []]],
                    ['type' => 'ROW_UPDATE', 'resource_group' => 'translations', 'resource_id' => 20, 'record_type' => 'translation', 'record_key' => '3',
                        'data' => ['id' => 99, 'verse_id' => 3, 'verse_key' => '1:3', 'text' => 'QF text for a locked row', 'foot_notes' => []]],
                    ['type' => 'ROW_DELETE', 'resource_group' => 'translations', 'resource_id' => 20, 'record_type' => 'translation', 'record_key' => (string) $row4->qf_id],
                    ['type' => 'RESOURCE_UPDATE', 'resource_group' => 'tafsirs', 'resource_id' => 169],
                    ['type' => 'RESOURCE_DELETE', 'resource_group' => 'word_by_word_transliterations', 'resource_id' => 60, 'unavailable_reason' => 'license withdrawn'],
                    ['type' => 'ROW_CREATE', 'resource_group' => 'translations', 'resource_id' => 20, 'record_type' => 'footnote_blob', 'record_key' => '5', 'data' => ['x' => 1]],
                    ['type' => 'ROW_UPDATE', 'resource_group' => 'articles', 'resource_id' => 123, 'record_type' => 'article_localization', 'record_key' => '1', 'data' => []],
                ],
            ]]);
        });

        ['report' => $report] = app(ContentSyncService::class)->run();

        $this->assertStringContainsString('sync_token=token-final', $sent[0]);
        $this->assertStringNotContainsString('bootstrap', $sent[0]);

        $this->assertSame('Updated 1:1', Translation::query()->where('resource_id', $this->saheeh->id)->where('verse_id', 1)->value('text'));
        $this->assertSame('editor text', Translation::query()->where('resource_id', $this->saheeh->id)->where('verse_id', 3)->value('text'));
        $this->assertSame(1, $report->skippedLocked);
        $this->assertFalse(Translation::query()->whereKey($row4->id)->exists());

        $transliteration = $this->transliteration->fresh();
        $this->assertFalse($transliteration->is_active);
        $this->assertFalse($transliteration->sync_enabled);
        $this->assertSame('license withdrawn', $transliteration->metadata['unavailable_reason']);

        // Unknown record type and the unhandled `articles` group are itemised, not dropped.
        $keys = array_column($report->failed, 'key');
        $this->assertContains('translations:20:footnote_blob', $keys); // QF resource id
        $this->assertNotEmpty(array_filter($keys, static fn (string $k): bool => str_starts_with($k, 'articles:123')));

        $this->assertSame('token-2', SyncState::query()->firstOrFail()->sync_token);
    }

    public function test_nothing_to_sync_is_a_clean_no_op(): void
    {
        QuranResource::query()->update(['sync_enabled' => false]);
        Mushaf::query()->update(['is_active' => false]);
        Http::fake();

        ['report' => $report] = app(ContentSyncService::class)->run();

        $this->assertTrue($report->passed());
        Http::assertNothingSent();
    }

    private function fakeBootstrap(): void
    {
        $snapshots = [
            'translations/20' => $this->qfFixture('snapshot-translations-20'),
            'tafsirs/169' => $this->qfFixture('snapshot-tafsirs-169'),
            'word_by_word_transliterations/60' => $this->qfFixture('snapshot-word_by_word_transliterations-60'),
            'mushafs/1' => $this->qfFixture('snapshot-mushafs-1'),
        ];
        $mutation = static fn (string $group, int $id): array => [
            'sequence' => 1, 'type' => 'RESOURCE_CREATE', 'resource_group' => $group, 'resource_id' => $id,
            'record_type' => null, 'record_key' => null, 'data' => null,
            'snapshot_url' => "/api/v4/resources/snapshots/{$group}/{$id}",
        ];

        Http::fake(function (Request $request) use ($snapshots, $mutation) {
            $url = urldecode($request->url());

            foreach ($snapshots as $path => $body) {
                if (str_ends_with($url, 'resources/snapshots/'.$path)) {
                    return Http::response($body);
                }
            }
            if (str_contains($url, 'cursor=page2')) {
                return Http::response(['sync' => [
                    'sync_until_sequence' => 433775, 'has_more' => false, 'next_page_url' => null, 'next_sync_token' => 'token-final',
                    'mutations' => [$mutation('tafsirs', 169), $mutation('word_by_word_transliterations', 60)],
                ]]);
            }
            if (str_contains($url, 'resources/sync') && str_contains($url, 'bootstrap=true')) {
                return Http::response(['sync' => [
                    'sync_until_sequence' => 433775, 'has_more' => true, 'next_page_url' => '/api/v4/resources/sync?cursor=page2', 'next_sync_token' => null,
                    'mutations' => [$mutation('mushafs', 1), $mutation('translations', 20)],
                ]]);
            }

            return null;
        });
    }

    /**
     * Word ids 1..29 exist in chapter 1 (the mushaf and transliteration fixtures reference them).
     */
    private function wordsForChapterOne(): void
    {
        $verses = DB::table('quran_verses')->orderBy('id')->pluck('id')->all();
        $wordId = 0;
        foreach ($verses as $index => $verseId) {
            for ($position = 1; $position <= 5 && $wordId < 29; $position++) {
                $wordId++;
                Word::factory()->create(['id' => $wordId, 'verse_id' => $verseId, 'position' => $position, 'location' => '1:'.($index + 1).':'.$position]);
            }
        }
    }
}
