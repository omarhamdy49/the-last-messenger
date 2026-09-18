<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Domain\Quran\Models\Chapter;
use App\Domain\Quran\Models\ChapterAudioFile;
use App\Domain\Quran\Models\QuranResource;
use App\Domain\Quran\Models\Translation;
use App\Domain\Quran\Models\Verse;
use App\Domain\Quran\Models\Word;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * T116: public Quran pages use a fixed number of queries — the count for a small data
 * set equals the count for a large one (no per-verse / per-reciter queries). Measured
 * with a cold cache.
 */
final class QuranPublicQueryCountTest extends TestCase
{
    use RefreshDatabase;

    private function seedChapter(int $number, int $verses): void
    {
        Chapter::factory()->number($number)->withVerses($verses)->create();
        $ids = Verse::query()->where('chapter_id', $number)->pluck('id');
        foreach ($ids as $verseId) {
            Word::factory()->create(['verse_id' => $verseId]);
            foreach (QuranResource::query()->where('kind', 'translation')->pluck('id') as $resourceId) {
                Translation::factory()->create(['resource_id' => $resourceId, 'verse_id' => $verseId]);
            }
        }
        DB::table('quran_verses')->where('chapter_id', $number)->update(['juz_number' => $number, 'page_number' => $number]);
    }

    private function queries(string $url): int
    {
        Cache::flush();
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get($url)->assertOk();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    public function test_reader_pages_do_not_grow_with_verses(): void
    {
        QuranResource::factory()->translation('en')->asDefault()->create();
        QuranResource::factory()->translation('fr')->create();
        $this->seedChapter(1, 7);
        $this->seedChapter(2, 50);
        DB::table('quran_sections')->insert([
            ['type' => 'juz', 'number' => 1, 'first_verse_id' => 1, 'last_verse_id' => 7, 'verses_count' => 7, 'verse_mapping' => json_encode(['1' => '1-7'])],
            ['type' => 'juz', 'number' => 2, 'first_verse_id' => 8, 'last_verse_id' => 57, 'verses_count' => 50, 'verse_mapping' => json_encode(['2' => '1-50'])],
        ]);

        foreach ([
            'surah' => [route('public.quran.surah', [1, 'wbw' => 1]), route('public.quran.surah', [2, 'wbw' => 1])],
            'juz' => [route('public.quran.juz', [1, 'per_page' => 50]), route('public.quran.juz', [2, 'per_page' => 50])],
            'page' => [route('public.quran.page', 1), route('public.quran.page', 2)],
            'search' => [route('public.quran.search', ['q' => 'name', 'page' => 1]), route('public.quran.search', ['q' => 'name', 'page' => 2])],
        ] as $page => [$small, $large]) {
            $this->assertSame($this->queries($small), $this->queries($large), "{$page}: query count grows with the number of verses");
        }

        $this->assertLessThanOrEqual(25, $this->queries(route('public.quran')), 'index');
    }

    public function test_reciter_pages_do_not_grow_with_reciters_or_files(): void
    {
        Chapter::factory()->number(1)->withVerses(3)->create();
        Chapter::factory()->number(2)->withVerses(3)->create();

        $one = QuranResource::factory()->chapterReciter()->create(['slug' => 'one']);
        ChapterAudioFile::factory()->create(['resource_id' => $one->id, 'chapter_id' => 1]);
        $few = $this->queries(route('public.quran.reciters'));
        $fewFiles = $this->queries(route('public.quran.reciter', 'one'));

        foreach (range(1, 5) as $i) {
            QuranResource::factory()->chapterReciter()->create(['slug' => 'r'.$i]);
        }
        ChapterAudioFile::factory()->create(['resource_id' => $one->id, 'chapter_id' => 2]);

        $this->assertSame($few, $this->queries(route('public.quran.reciters')), 'reciters directory');
        $this->assertSame($fewFiles, $this->queries(route('public.quran.reciter', 'one')), 'reciter page');
    }
}
