<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Quran;

use App\Domain\Quran\Models\Chapter;
use App\Domain\Quran\Models\ChapterAudioFile;
use App\Domain\Quran\Models\ChapterInfo;
use App\Domain\Quran\Models\QuranResource;
use App\Domain\Quran\Models\Translation;
use App\Domain\Quran\Models\Verse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * N+1 guard for the Quran admin screens.
 *
 * A fixed query budget only catches a regression once it crosses the number someone
 * guessed. These tests render each screen twice — once over a small data set and once
 * over a larger one — and assert the query count did not grow. That is the actual
 * definition of an N+1, and it cannot be satisfied by tuning a constant.
 */
final class QuranAdminQueryCountTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->createAdminUser();
        $this->actingAs($this->admin, 'admin');
    }

    public function test_the_chapters_index_does_not_query_per_chapter(): void
    {
        $this->assertQueryCountIsFlat(
            route('admin.quran.chapters.index'),
            small: fn () => $this->chapters(1, 3),
            grow: fn () => $this->chapters(4, 30),
        );
    }

    public function test_the_verses_index_does_not_query_per_verse(): void
    {
        $chapter = Chapter::factory()->number(1)->withVerses(3)->create();
        $url = route('admin.quran.verses.index', ['chapter' => $chapter->id]);

        $this->assertQueryCountIsFlat(
            $url,
            small: static fn () => null,
            grow: function () use ($chapter): void {
                $last = (int) Verse::query()->max('id');
                foreach (range(1, 40) as $i) {
                    Verse::factory()->create([
                        'id' => $last + $i,
                        'chapter_id' => $chapter->id,
                        'verse_number' => 3 + $i,
                        'verse_key' => $chapter->id.':'.(3 + $i),
                    ]);
                }
            },
        );
    }

    public function test_the_resources_index_does_not_query_per_resource(): void
    {
        $this->assertQueryCountIsFlat(
            route('admin.quran.resources.index', ['kind' => 'translations']),
            small: static fn () => QuranResource::factory()->count(2)->translation('en')->create(),
            grow: static fn () => QuranResource::factory()->count(20)->translation('en')->create(),
        );
    }

    public function test_the_translation_rows_screen_does_not_query_per_row(): void
    {
        $chapter = Chapter::factory()->number(1)->withVerses(7)->create();
        $resource = QuranResource::factory()->translation('en')->create();
        $verses = Verse::query()->where('chapter_id', $chapter->id)->pluck('id');

        $url = route('admin.quran.resources.translations.index', ['resource' => $resource->id, 'chapter' => $chapter->id]);

        $this->assertQueryCountIsFlat(
            $url,
            small: static fn () => Translation::factory()->create(['resource_id' => $resource->id, 'verse_id' => $verses->first()]),
            grow: static function () use ($resource, $verses): void {
                foreach ($verses->skip(1) as $verseId) {
                    Translation::factory()->create(['resource_id' => $resource->id, 'verse_id' => $verseId]);
                }
            },
        );
    }

    public function test_the_chapter_audio_screen_does_not_query_per_file(): void
    {
        $reciter = QuranResource::factory()->chapterReciter()->create();
        $url = route('admin.quran.resources.chapter-audio.index', ['resource' => $reciter->id]);

        $this->assertQueryCountIsFlat(
            $url,
            small: function () use ($reciter): void {
                $this->chapters(1, 2);
                ChapterAudioFile::factory()->create(['resource_id' => $reciter->id, 'chapter_id' => 1]);
            },
            grow: function () use ($reciter): void {
                $this->chapters(3, 20);
                foreach (range(2, 20) as $chapter) {
                    ChapterAudioFile::factory()->create(['resource_id' => $reciter->id, 'chapter_id' => $chapter]);
                }
            },
        );
    }

    public function test_the_chapter_show_screen_does_not_query_per_related_row(): void
    {
        $chapter = Chapter::factory()->number(1)->withVerses(3)->create();
        $url = route('admin.quran.chapters.show', $chapter->id);

        $this->assertQueryCountIsFlat(
            $url,
            // Both relations must exist in the small set too: Eloquent skips an eager
            // load with no parents, so a missing relation would read as a saved query.
            small: static function () use ($chapter): void {
                ChapterInfo::factory()->create([
                    'resource_id' => QuranResource::factory()->chapterInfo('en')->create()->id,
                    'chapter_id' => $chapter->id,
                ]);
                ChapterAudioFile::factory()->create([
                    'resource_id' => QuranResource::factory()->chapterReciter()->create()->id,
                    'chapter_id' => $chapter->id,
                ]);
            },
            grow: static function () use ($chapter): void {
                foreach (range(1, 6) as $i) {
                    ChapterInfo::factory()->create([
                        'resource_id' => QuranResource::factory()->chapterInfo('en')->create()->id,
                        'chapter_id' => $chapter->id,
                    ]);
                    ChapterAudioFile::factory()->create([
                        'resource_id' => QuranResource::factory()->chapterReciter()->create()->id,
                        'chapter_id' => $chapter->id,
                    ]);
                }
            },
        );
    }

    /**
     * Render `$url` after `$small`, then again after `$grow`, and fail if the second
     * render used more queries than the first.
     */
    private function assertQueryCountIsFlat(string $url, callable $small, callable $grow): void
    {
        $small();
        $before = $this->queriesFor($url);

        $grow();
        $after = $this->queriesFor($url);

        $this->assertLessThanOrEqual(
            count($before),
            count($after),
            sprintf(
                "%s queried %d times over the small set and %d over the larger one — that is an N+1.\nExtra queries:\n%s",
                $url,
                count($before),
                count($after),
                implode("\n", array_slice(array_diff($after, $before), 0, 10)),
            )
        );
    }

    /**
     * @return list<string>
     */
    private function queriesFor(string $url): array
    {
        // Caches are per-process in tests, so a warm-up render keeps cached lookups
        // (chapter list, active resources) out of both measurements.
        Cache::flush();
        $this->get($url)->assertOk();

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get($url)->assertOk();
        $queries = array_column(DB::getQueryLog(), 'query');
        DB::disableQueryLog();

        return array_values(array_filter(
            $queries,
            static fn (string $q): bool => ! str_contains($q, 'from `sessions`') && ! str_contains($q, 'from `users`'),
        ));
    }

    private function chapters(int $from, int $to): void
    {
        foreach (range($from, $to) as $number) {
            if (Chapter::query()->whereKey($number)->doesntExist()) {
                Chapter::factory()->number($number)->create();
            }
        }
    }
}
