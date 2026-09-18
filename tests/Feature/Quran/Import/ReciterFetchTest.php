<?php

declare(strict_types=1);

namespace Tests\Feature\Quran\Import;

use App\Domain\Quran\Enums\ExternalSource;
use App\Domain\Quran\Enums\RecitationStyle;
use App\Domain\Quran\Enums\ResourceKind;
use App\Domain\Quran\Import\Reciters\Mp3QuranReciterFetcher;
use App\Domain\Quran\Import\Reciters\QfReciterFetcher;
use App\Domain\Quran\Import\Reciters\ReciterFetchService;
use App\Domain\Quran\Import\Support\Throttle;
use App\Domain\Quran\Models\Chapter;
use App\Domain\Quran\Models\ChapterAudioFile;
use App\Domain\Quran\Models\QuranResource;
use App\Domain\Quran\Models\SyncRun;
use App\Domain\Quran\Repositories\ChapterRepositoryInterface;
use App\Jobs\FetchReciterFromSourceJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Tests\Feature\Quran\Import\Concerns\FakesQuranFoundation;
use Tests\TestCase;

/**
 * US4: source-backed reciters (Quran Foundation and mp3quran) — lookup, data, audio,
 * timestamps, local download, background job and admin endpoints.
 */
final class ReciterFetchTest extends TestCase
{
    use FakesQuranFoundation;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpQuranFoundation();
        $this->app->instance(Mp3QuranReciterFetcher::class, new Mp3QuranReciterFetcher(app(ChapterRepositoryInterface::class), new Throttle(0)));

        Chapter::factory()->number(1)->withVerses(7)->create();
        for ($n = 2; $n <= 114; $n++) {
            Chapter::factory()->number($n)->create();
        }
    }

    private function fakeSources(): void
    {
        $fixtures = [
            'mp3q-ar' => $this->fixture('mp3quran/reciters-ar'),
            'mp3q-eng' => $this->fixture('mp3quran/reciters-eng'),
            'reads' => $this->fixture('mp3quran/ayat-timing-reads'),
            'timing' => $this->fixture('mp3quran/ayat-timing-1-1'),
            'cr-en' => $this->qfFixture('catalogue-chapter_reciter-en'),
            'cr-ar' => $this->qfFixture('catalogue-chapter_reciter-ar'),
            'files-159' => $this->qfFixture('chapter-recitations-159'),
            'files-7' => $this->qfFixture('chapter-recitations-7'),
            'segments' => $this->qfFixture('chapter-recitation-7-1-segments'),
            'rec-en' => $this->qfFixture('catalogue-recitation-en'),
            'rec-files' => $this->qfFixture('recitation-files-7-chapter-1'),
        ];

        Http::fake(function ($request) use ($fixtures) {
            $url = urldecode($request->url());

            return match (true) {
                str_contains($url, 'mp3quran.net/api/v3/reciters') => Http::response(str_contains($url, 'language=eng') ? $fixtures['mp3q-eng'] : $fixtures['mp3q-ar']),
                str_contains($url, 'mp3quran.net/api/v3/ayat_timing/reads') => Http::response($fixtures['reads']),
                str_contains($url, 'mp3quran.net/api/v3/ayat_timing') => Http::response(str_contains($url, 'surah=1&') ? $fixtures['timing'] : []),
                str_contains($url, 'resources/chapter_reciters') => Http::response(str_contains($url, 'language=ar') ? $fixtures['cr-ar'] : $fixtures['cr-en']),
                str_contains($url, 'resources/recitations/') && str_contains($url, '/info') => Http::response(['message' => 'nf', 'type' => 'not_found', 'success' => false], 404),
                str_contains($url, 'resources/recitations') => Http::response($fixtures['rec-en']),
                str_contains($url, 'chapter_recitations/159') => Http::response($fixtures['files-159']),
                (bool) preg_match('#chapter_recitations/7/1\b#', $url) => Http::response($fixtures['segments']),
                (bool) preg_match('#chapter_recitations/7/\d+#', $url) => Http::response(['message' => 'nf', 'type' => 'not_found', 'success' => false], 404),
                str_contains($url, 'chapter_recitations/7') => Http::response($fixtures['files-7']),
                str_contains($url, 'quran/recitations/7') => Http::response(str_contains($url, 'chapter_number=1&') || str_ends_with($url, 'chapter_number=1') ? $fixtures['rec-files'] : ['audio_files' => []]),
                str_ends_with($url, '.mp3') => Http::response("ID3\x04\x00fake-mp3-bytes"),
                default => Http::response(['message' => 'unexpected '.$url], 500),
            };
        });
    }

    /**
     * @return array<int|string, mixed>
     */
    private function fixture(string $name): array
    {
        return json_decode((string) file_get_contents(base_path("tests/Fixtures/{$name}.json")), true, 512, JSON_THROW_ON_ERROR);
    }

    public function test_qf_chapter_reciter_lookup_and_files(): void
    {
        $this->fakeSources();
        $fetcher = app(QfReciterFetcher::class);

        $preview = $fetcher->lookup('159', ResourceKind::CHAPTER_RECITER);
        $this->assertSame(114, $preview->filesCount);
        $this->assertCount(3, $preview->previewUrls);

        $this->assertCount(7, $fetcher->timestamps('7', 1));
        $this->assertSame([], $fetcher->timestamps('7', 2));

        $this->expectException(InvalidArgumentException::class);
        $fetcher->lookup('99999', ResourceKind::CHAPTER_RECITER);
    }

    public function test_qf_recitation_files_are_absolute_with_segments(): void
    {
        $this->fakeSources();
        $files = iterator_to_array(app(QfReciterFetcher::class)->files('7', ResourceKind::RECITATION), false);

        $this->assertCount(7, $files);
        $this->assertSame('1:1', $files[0]->target);
        $this->assertStringStartsWith('https://verses.quran.foundation/', $files[0]->url);
        $this->assertCount(4, $files[0]->segments);
        $this->assertCount(3, $files[0]->segments[0]);
    }

    public function test_mp3quran_lookup_files_and_reconciled_timings(): void
    {
        $this->fakeSources();
        $fetcher = app(Mp3QuranReciterFetcher::class);

        $preview = $fetcher->lookup('1', ResourceKind::CHAPTER_RECITER);
        $this->assertSame(114, $preview->filesCount);
        $this->assertTrue($preview->hasTimestamps);
        $this->assertSame('Hafs', $preview->qirat);
        $this->assertSame(RecitationStyle::MURATTAL, $preview->style);

        $files = iterator_to_array($fetcher->files('1', ResourceKind::CHAPTER_RECITER), false);
        $this->assertCount(114, $files);
        $this->assertSame('https://server6.mp3quran.net/akdr/001.mp3', $files[0]->url);

        $timings = $fetcher->timestamps('1', 1);
        $this->assertSame(['1:1', '1:2', '1:3', '1:4', '1:5', '1:6', '1:7'], array_map(fn ($t) => $t->verseKey, $timings));

        $data = $fetcher->data('1', ResourceKind::CHAPTER_RECITER);
        $this->assertSame('إبراهيم الأخضر', $data->names['ar']);
        $this->assertSame('Ibrahim Al-Akdar', $data->name);

        $this->expectException(InvalidArgumentException::class);
        $fetcher->lookup('1', ResourceKind::RECITATION);
    }

    public function test_fetch_service_imports_mp3quran_data_audio_and_timestamps(): void
    {
        $this->fakeSources();
        $resource = QuranResource::factory()->chapterReciter()->manual()->externallySourced(ExternalSource::MP3QURAN, '1')->create(['name' => 'Placeholder']);

        $report = app(ReciterFetchService::class)->fetch($resource, data: true, audio: true, timestamps: true, download: false);

        $this->assertSame([], $report->failed);
        $resource->refresh();
        $this->assertSame('Ibrahim Al-Akdar', $resource->name);
        $this->assertSame('إبراهيم الأخضر', $resource->getTranslation('name', 'ar', false));
        $this->assertSame(114, ChapterAudioFile::query()->where('resource_id', $resource->id)->count());
        $this->assertTrue((bool) ChapterAudioFile::query()->where('resource_id', $resource->id)->where('chapter_id', 1)->value('has_timestamps'));
        $this->assertNotNull($resource->last_fetched_at);
        // 114 chapter files + 7 verse timings of chapter 1.
        $this->assertSame(121, $resource->last_fetch_report['written']);
    }

    public function test_download_stores_files_on_the_public_disk(): void
    {
        Storage::fake('public');
        $this->fakeSources();
        $resource = QuranResource::factory()->chapterReciter()->qf(7)->create();

        $report = app(ReciterFetchService::class)->fetch($resource, data: false, audio: true, timestamps: false, download: true);

        $this->assertSame([], $report->failed);
        Storage::disk('public')->assertExists("quran/audio/{$resource->id}/001.mp3");
        $this->assertTrue((bool) DB::table('quran_chapter_audio_files')->where('resource_id', $resource->id)->where('chapter_id', 1)->value('is_local'));
    }

    public function test_job_records_the_run_and_is_unique_per_resource(): void
    {
        $this->fakeSources();
        $resource = QuranResource::factory()->chapterReciter()->manual()->externallySourced(ExternalSource::MP3QURAN, '1')->create();
        $run = SyncRun::query()->create(['mode' => 'fetch', 'resources_filter' => 'reciter:'.$resource->id, 'started_at' => now(), 'status' => 'running']);

        $job = new FetchReciterFromSourceJob($resource->id, $run->id, false, true, false, false);
        $this->assertSame('reciter-fetch:'.$resource->id, $job->uniqueId());

        dispatch_sync($job);

        $run->refresh();
        $this->assertSame('succeeded', $run->status);
        $this->assertSame(114, $run->rows_upserted);
    }

    public function test_admin_lookup_fetch_and_status(): void
    {
        $this->fakeSources();
        $this->actingAs($this->createAdminUser(), 'admin');

        $this->postJson(route('admin.quran.resources.lookup'), ['external_source' => 'mp3quran', 'external_ref_id' => '1', 'kind' => 'chapter_reciter'])
            ->assertOk()->assertJsonPath('data.files_count', 114);
        $this->postJson(route('admin.quran.resources.lookup'), ['external_source' => 'mp3quran', 'external_ref_id' => '424242', 'kind' => 'chapter_reciter'])
            ->assertUnprocessable();

        Bus::fake();
        $resource = QuranResource::factory()->chapterReciter()->manual()->externallySourced(ExternalSource::MP3QURAN, '1')->create();
        $this->postJson(route('admin.quran.resources.fetch', $resource->id), ['audio' => true, 'timestamps' => true])->assertStatus(202);
        Bus::assertDispatched(FetchReciterFromSourceJob::class);

        $this->getJson(route('admin.quran.resources.fetch-status', $resource->id))->assertOk()->assertJsonPath('data.status', 'running');
        $this->get(route('admin.quran.resources.show', $resource->id))->assertOk()->assertSee(__('quran.manage.source_actions', [], 'ar'));
        $this->get(route('admin.quran.resources.edit', $resource->id))->assertOk();

        $this->actingAs($this->createAdminUser(role: 'editor'), 'admin');
        $this->postJson(route('admin.quran.resources.fetch', $resource->id), ['audio' => true])->assertForbidden();
    }

    public function test_mp3quran_catalogue_lists_imports_and_links_recordings(): void
    {
        $this->fakeSources();
        Bus::fake();
        $this->actingAs($this->createAdminUser(), 'admin');

        $this->get(route('admin.quran.resources.mp3quran'))->assertOk()->assertSee(__('quran.manage.import_as_new', [], 'ar'));

        // Import as a new reciter: inactive, tied to the recording, and fetched straight away.
        $this->post(route('admin.quran.resources.mp3quran.import'), ['ref_id' => '1'])->assertRedirect();
        $created = QuranResource::query()->where('external_source', 'mp3quran')->where('external_ref_id', '1')->firstOrFail();
        $this->assertFalse($created->is_active);
        $this->assertSame('mp3quran-1', $created->slug);
        Bus::assertDispatched(FetchReciterFromSourceJob::class);

        // Importing the same recording twice lands on the reciter that already has it.
        $this->post(route('admin.quran.resources.mp3quran.import'), ['ref_id' => '1'])->assertRedirect(route('admin.quran.resources.show', $created->id));
        $this->assertSame(1, QuranResource::query()->where('external_source', 'mp3quran')->count());

        // An id mp3quran does not have never becomes a row.
        $this->post(route('admin.quran.resources.mp3quran.import'), ['ref_id' => '424242'])->assertSessionHas('error');
        $this->post(route('admin.quran.resources.mp3quran.import'), ['ref_id' => 'abc'])->assertSessionHasErrors('ref_id');
    }

    public function test_an_existing_reciter_can_be_linked_to_a_source(): void
    {
        $this->fakeSources();
        $this->actingAs($this->createAdminUser(), 'admin');
        $reciter = QuranResource::factory()->chapterReciter()->manual()->create();

        $this->get(route('admin.quran.resources.show', $reciter->id))->assertOk()->assertSee(__('quran.manage.link_source_help', [], 'ar'));

        $this->post(route('admin.quran.resources.link-source', $reciter->id), ['external_source' => 'mp3quran', 'external_ref_id' => '424242'])->assertSessionHas('error');
        $this->assertNull($reciter->fresh()->external_source);

        $this->post(route('admin.quran.resources.link-source', $reciter->id), ['external_source' => 'mp3quran', 'external_ref_id' => '1'])
            ->assertRedirect(route('admin.quran.resources.show', $reciter->id));
        $this->assertSame(ExternalSource::MP3QURAN, $reciter->fresh()->external_source);
        $this->assertSame('1', $reciter->fresh()->external_ref_id);

        // One recording feeds one reciter.
        $other = QuranResource::factory()->chapterReciter()->manual()->create();
        $this->post(route('admin.quran.resources.link-source', $other->id), ['external_source' => 'mp3quran', 'external_ref_id' => '1'])->assertSessionHas('error');
        $this->assertNull($other->fresh()->external_source);
    }
}
