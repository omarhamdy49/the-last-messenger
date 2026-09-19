<?php

declare(strict_types=1);

namespace Tests\Feature\Quran;

use App\Domain\Quran\DTOs\AudioFileData;
use App\Domain\Quran\DTOs\ContentRowData;
use App\Domain\Quran\DTOs\ReaderOptions;
use App\Domain\Quran\DTOs\TimestampRowData;
use App\Domain\Quran\Models\Chapter;
use App\Domain\Quran\Models\ChapterAudioFile;
use App\Domain\Quran\Models\Mushaf;
use App\Domain\Quran\Models\QuranResource;
use App\Domain\Quran\Models\Translation;
use App\Domain\Quran\Models\Verse;
use App\Domain\Quran\Models\Word;
use App\Domain\Quran\Repositories\ChapterRepositoryInterface;
use App\Domain\Quran\Repositories\ContentRowRepositoryInterface;
use App\Domain\Quran\Repositories\MushafRepositoryInterface;
use App\Domain\Quran\Repositories\ResourceRepositoryInterface;
use App\Domain\Quran\Repositories\VerseRepositoryInterface;
use App\Domain\Quran\Services\AudioService;
use App\Domain\Quran\Services\QuranCacheKeys;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * The US3 admin/read repositories against the real schema: default exclusivity,
 * hide keeps the row while delete removes it, locked content rows, audio templates and
 * timings, and a fixed query count for the verse payload.
 */
final class ReadWriteRepositoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_one_default_per_kind_and_language(): void
    {
        $repo = app(ResourceRepositoryInterface::class);
        $a = QuranResource::factory()->translation('en')->asDefault()->create();
        $b = QuranResource::factory()->translation('en')->create();
        $fr = QuranResource::factory()->translation('fr')->asDefault()->create();

        $repo->toggle($b->id, 'is_default');

        $this->assertFalse($a->fresh()->is_default);
        $this->assertTrue($b->fresh()->is_default);
        $this->assertTrue($b->fresh()->is_active);
        $this->assertTrue($fr->fresh()->is_default, 'another language keeps its default');
    }

    public function test_hiding_keeps_the_row_and_deleting_removes_qf_and_manual_alike(): void
    {
        $repo = app(ResourceRepositoryInterface::class);
        $qf = QuranResource::factory()->translation()->qf(20)->asDefault()->create();
        $manual = QuranResource::factory()->translation()->manual()->create();

        $repo->hide($qf->id);
        $qf->refresh();
        $this->assertFalse($qf->is_active);
        $this->assertFalse($qf->is_default);
        $this->assertTrue($qf->metadata['hidden_by_admin']);

        $repo->delete($qf->id);
        $repo->delete($manual->id);
        $this->assertNull(QuranResource::query()->find($qf->id));
        $this->assertNull(QuranResource::query()->find($manual->id));
    }

    public function test_sync_cannot_be_enabled_on_a_manual_resource(): void
    {
        $manual = QuranResource::factory()->translation()->manual()->create();

        $this->expectException(InvalidArgumentException::class);
        app(ResourceRepositoryInterface::class)->toggle($manual->id, 'sync_enabled');
    }

    public function test_content_row_import_writes_known_verses_and_reports_unknown_ones(): void
    {
        Chapter::factory()->number(1)->withVerses(7)->create();
        $resource = QuranResource::factory()->translation()->manual()->create();

        $report = app(ContentRowRepositoryInterface::class)->import($resource, [
            new ContentRowData('1:1', 'In the name of God'),
            new ContentRowData('1:2', 'Praise be to God', footNotes: ['1' => 'note']),
            new ContentRowData('9:999', 'nope'),
        ]);

        $this->assertSame(3, $report->expected);
        $this->assertCount(1, $report->failed);
        $this->assertSame(2, Translation::query()->where('resource_id', $resource->id)->count());
        $this->assertSame(['1' => 'note'], Translation::query()->where('resource_id', $resource->id)->where('verse_id', 2)->value('foot_notes'));
    }

    public function test_ayah_template_fill_is_idempotent_and_counts_records(): void
    {
        Chapter::factory()->number(1)->withVerses(7)->create();
        $recitation = QuranResource::factory()->recitation()->manual()->create();
        $audio = app(AudioService::class);

        $first = $audio->fillAyahFromTemplate($recitation, 'https://cdn.test/r/{surah:03}{ayah:03}.mp3', 1);
        $second = $audio->fillAyahFromTemplate($recitation, 'https://cdn.test/r/{surah:03}{ayah:03}.mp3', 1);

        $this->assertSame(7, $first->written);
        $this->assertSame(0, $second->written);
        $this->assertSame(7, $recitation->fresh()->records_count);
        $this->assertSame('https://cdn.test/r/001007.mp3', DB::table('quran_audio_files')->where('verse_id', 7)->value('url'));
    }

    public function test_template_without_placeholders_is_rejected(): void
    {
        $recitation = QuranResource::factory()->recitation()->manual()->create();

        $this->expectException(InvalidArgumentException::class);
        app(AudioService::class)->fillAyahFromTemplate($recitation, 'https://cdn.test/{surah:03}.mp3', null);
    }

    public function test_timestamps_replace_and_url_change_clears_them(): void
    {
        Chapter::factory()->number(1)->withVerses(7)->create();
        $reciter = QuranResource::factory()->chapterReciter()->manual()->create();
        $audio = app(AudioService::class);
        $file = ChapterAudioFile::factory()->create(['resource_id' => $reciter->id, 'chapter_id' => 1, 'url' => 'https://cdn.test/001.mp3']);

        $report = $audio->replaceTimestamps($file, [
            new TimestampRowData('1:1', 0, 6000),
            new TimestampRowData('1:2', 6000, 11000, [[1, 6000, 8000]]),
            new TimestampRowData('2:1', 0, 1),
        ]);

        $this->assertCount(1, $report->failed);
        $this->assertTrue($file->fresh()->has_timestamps);
        $timings = $audio->timings($reciter->id, 1);
        $this->assertSame('1:2', AudioService::timingAt($timings, 6000)['verse_key']);
        $this->assertNull(AudioService::timingAt($timings, 11000));

        $audio->saveChapterFile($reciter, new AudioFileData(target: 1, url: 'https://cdn.test/001-new.mp3'));

        $this->assertFalse($file->fresh()->has_timestamps);
        $this->assertSame(0, DB::table('quran_chapter_audio_timestamps')->where('chapter_audio_file_id', $file->id)->count());
    }

    public function test_chapter_list_loads_translations_in_two_queries(): void
    {
        Chapter::factory()->number(1)->create();
        Chapter::factory()->number(2)->create();

        DB::enableQueryLog();
        $chapters = app(ChapterRepositoryInterface::class)->all(publishedOnly: false);
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertCount(2, $chapters);
        $this->assertLessThanOrEqual(2, $queries);
    }

    /**
     * Regression: surah 11 read as if words were missing, because the renderer ordered
     * words inside a line by Quran Foundation's `position_in_line`. That index
     * contradicts the page order on 8,191 of the 8,820 lines — the opening word of
     * 11:1 carries 9 — so words landed in the wrong place. `position_in_page` is the
     * reading order.
     */
    public function test_chapter_lines_follow_the_page_order_not_the_line_index(): void
    {
        Chapter::factory()->number(11)->withVerses(1)->create();
        $mushaf = Mushaf::factory()->create();
        $verseId = (int) Verse::query()->where('chapter_id', 11)->value('id');

        // Exactly the shape QF sends for 11:1: the first word of the line carries a
        // position_in_line of 9 while the rest run 1, 2, 3.
        $layout = [
            ['text' => 'alif-lam-ra', 'page' => 9, 'line' => 9],
            ['text' => 'kitabun', 'page' => 10, 'line' => 1],
            ['text' => 'uhkimat', 'page' => 11, 'line' => 2],
            ['text' => 'ayatuhu', 'page' => 12, 'line' => 3],
        ];
        foreach ($layout as $i => $row) {
            $word = Word::factory()->create(['verse_id' => $verseId, 'text_uthmani' => $row['text']]);
            DB::table('quran_mushaf_words')->insert([
                'id' => $i + 1, 'mushaf_id' => $mushaf->id, 'word_id' => $word->id, 'verse_id' => $verseId,
                'text' => $row['text'], 'char_type' => 'word', 'page_number' => 221, 'line_number' => 9,
                'position_in_verse' => $i + 1, 'position_in_line' => $row['line'], 'position_in_page' => $row['page'],
            ]);
        }

        // The layout is cached for a day and the array store survives between tests.
        QuranCacheKeys::flushMushafs();
        // The layout is cached for a day and the array store survives between tests.
        QuranCacheKeys::flushMushafs();

        $pages = app(MushafRepositoryInterface::class)->chapterLines($mushaf->id, 11);
        $words = array_column($pages[0]['lines'][0]['words'], 'text');

        $this->assertSame(221, $pages[0]['page']);
        $this->assertSame(9, $pages[0]['lines'][0]['line']);
        $this->assertSame(array_column($layout, 'text'), $words);
    }

    public function test_page_lines_group_words_by_line_in_one_query(): void
    {
        Chapter::factory()->number(1)->withVerses(1)->create();
        $mushaf = Mushaf::factory()->create();
        foreach ([[1, 1], [2, 1], [3, 2]] as [$position, $line]) {
            $word = Word::factory()->create(['verse_id' => 1]);
            DB::table('quran_mushaf_words')->insert([
                'id' => $position, 'mushaf_id' => $mushaf->id, 'word_id' => $word->id, 'verse_id' => 1, 'text' => 'x', 'char_type' => 'word',
                'page_number' => 1, 'line_number' => $line, 'position_in_verse' => $position, 'position_in_line' => $position,
                'position_in_page' => $position,
            ]);
        }

        DB::enableQueryLog();
        $lines = app(MushafRepositoryInterface::class)->pageLines($mushaf->id, 1);
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame([1, 2], array_keys($lines));
        $this->assertCount(2, $lines[1]);
        $this->assertLessThanOrEqual(1, $queries);
    }

    public function test_upload_rejects_non_mp3_files(): void
    {
        Storage::fake('public');
        $recitation = QuranResource::factory()->recitation()->manual()->create();

        $this->expectException(InvalidArgumentException::class);
        app(AudioService::class)->storeUpload(UploadedFile::fake()->create('notes.txt', 1, 'text/plain'), $recitation, '001001');
    }

    public function test_verse_payload_uses_a_fixed_number_of_queries(): void
    {
        Chapter::factory()->number(1)->withVerses(7)->create();
        $verses = Verse::query()->pluck('id')->all();
        foreach ($verses as $verseId) {
            // ponytail: one at a time; the factory's max(id)+1 is evaluated before a batch insert.
            Word::factory()->create(['verse_id' => $verseId]);
            Word::factory()->end()->create(['verse_id' => $verseId]);
        }
        $translation = QuranResource::factory()->translation()->create();
        $tafsir = QuranResource::factory()->tafsir()->create();
        $recitation = QuranResource::factory()->recitation()->create();
        foreach ($verses as $verseId) {
            Translation::factory()->create(['resource_id' => $translation->id, 'verse_id' => $verseId]);
        }

        DB::enableQueryLog();
        $payload = app(VerseRepositoryInterface::class)->payload($verses, new ReaderOptions(
            translationIds: [$translation->id], tafsirIds: [$tafsir->id], words: true, recitationId: $recitation->id,
        ));
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(5, $queries);
        $this->assertCount(7, $payload);
        $this->assertCount(2, $payload[$verses[0]]['words']);
        $this->assertArrayHasKey($translation->id, $payload[$verses[0]]['translations']);
    }
}
