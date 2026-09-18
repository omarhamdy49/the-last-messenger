<?php

declare(strict_types=1);

namespace Tests\Feature\Quran\Import;

use App\Domain\Quran\Import\QuranFoundation\Importers\ChapterAudioTimestampsImporter;
use App\Domain\Quran\Import\QuranFoundation\Importers\ChapterInfosImporter;
use App\Domain\Quran\Models\Chapter;
use App\Domain\Quran\Models\ChapterAudioFile;
use App\Domain\Quran\Models\ChapterInfo;
use App\Domain\Quran\Models\QuranResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Quran\Import\Concerns\FakesQuranFoundation;
use Tests\TestCase;

final class ChapterInfosAndTimestampsTest extends TestCase
{
    use FakesQuranFoundation;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpQuranFoundation();
        Chapter::factory()->number(1)->withVerses(7)->create();
        Chapter::factory()->number(2)->create(['verses_count' => 286]);
    }

    public function test_chapter_infos_are_imported_for_enabled_sources_and_uncovered_chapters_noted(): void
    {
        $resource = QuranResource::factory()->chapterInfo('en')->qf(1030)->create(['slug' => 'ibn-ashur', 'sync_enabled' => true]);
        $info = $this->qfFixture('chapter-info-1-en');

        Http::fake(function (Request $request) use ($info) {
            $url = urldecode($request->url());
            if (str_contains($url, 'chapters/1/info')) {
                return Http::response($info);
            }
            if (str_contains($url, 'chapters/2/info')) {
                return Http::response(['chapter_info' => null, 'resources' => []]);
            }

            return null;
        });

        $report = app(ChapterInfosImporter::class)->run(null, 1, 2);

        $this->assertTrue($report->passed(), json_encode($report->toArray()) ?: '');
        $row = ChapterInfo::query()->where('resource_id', $resource->id)->where('chapter_id', 1)->firstOrFail();
        $this->assertStringContainsString('Themes and Purpose', $row->text);
        $this->assertSame('Adapted from Tafsir Ibn Ashur', $row->source);
        $this->assertNull($row->short_text); // QF sends ''
        $this->assertFalse(ChapterInfo::query()->where('chapter_id', 2)->exists());
        $this->assertNotEmpty(array_filter($report->notes, static fn (string $n): bool => str_contains($n, 'chapter(s) 2')));

        Http::assertSent(fn (Request $r): bool => str_contains(urldecode($r->url()), 'resource_id=1030'));
    }

    public function test_a_locked_chapter_info_is_not_overwritten(): void
    {
        $resource = QuranResource::factory()->chapterInfo('en')->qf(1030)->create(['slug' => 'ibn-ashur', 'sync_enabled' => true]);
        ChapterInfo::factory()->create(['resource_id' => $resource->id, 'chapter_id' => 1, 'text' => 'editor', 'is_locked' => true]);
        Http::fake(['apis.test/*' => Http::response($this->qfFixture('chapter-info-1-en'))]);

        $report = app(ChapterInfosImporter::class)->run(null, 1, 1);

        $this->assertSame('editor', ChapterInfo::query()->where('chapter_id', 1)->value('text'));
        $this->assertSame(1, $report->skippedLocked);
    }

    public function test_timestamps_import_writes_the_chapter_file_and_a_row_per_verse(): void
    {
        $reciter = QuranResource::factory()->chapterReciter()->qf(7)->create(['slug' => 'afasy', 'is_active' => true]);
        Http::fake(['apis.test/content/api/v4/chapter_recitations/7/1*' => Http::response($this->qfFixture('chapter-recitation-7-1-segments'))]);

        $report = app(ChapterAudioTimestampsImporter::class)->run(null, 1, 1);

        $this->assertTrue($report->passed(), json_encode($report->toArray()) ?: '');
        $file = ChapterAudioFile::query()->where('resource_id', $reciter->id)->where('chapter_id', 1)->firstOrFail();
        $this->assertSame('https://download.quranicaudio.com/qdc/mishari_al_afasy/murattal/1.mp3', $file->url);
        $this->assertTrue($file->has_timestamps);
        $this->assertCount(7, $file->verseTimestamps);

        $first = $file->verseTimestamps->first();
        $this->assertSame(0, $first->timestamp_from);
        $this->assertSame(6090, $first->timestamp_to);
        $this->assertSame([1, 0, 580], $first->segments[0]);

        $again = app(ChapterAudioTimestampsImporter::class)->run(null, 1, 1);
        $this->assertSame(0, $again->written + $again->updated);
    }

    public function test_a_short_timestamp_list_fails_the_gate(): void
    {
        QuranResource::factory()->chapterReciter()->qf(7)->create(['slug' => 'afasy', 'is_active' => true]);
        $payload = $this->qfFixture('chapter-recitation-7-1-segments');
        $payload['audio_file']['timestamps'] = array_slice($payload['audio_file']['timestamps'], 0, 3);
        Http::fake(['apis.test/*' => Http::response($payload)]);

        $report = app(ChapterAudioTimestampsImporter::class)->run(null, 1, 1);

        $this->assertFalse($report->passed());
    }
}
