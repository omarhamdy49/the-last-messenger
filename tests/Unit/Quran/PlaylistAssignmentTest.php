<?php

declare(strict_types=1);

namespace Tests\Unit\Quran;

use App\Domain\Quran\Models\Chapter;
use App\Domain\Quran\Models\ChapterAudioFile;
use App\Domain\Quran\Models\QuranResource;
use App\Domain\Quran\Repositories\AudioRepositoryInterface;
use App\Domain\Quran\Repositories\ResourceRepositoryInterface;
use App\Domain\Quran\Services\PlaylistService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Random\Engine\Mt19937;
use Random\Randomizer;
use Tests\TestCase;

/**
 * The balanced-random surah → recording assignment behind playlist previews.
 */
final class PlaylistAssignmentTest extends TestCase
{
    use RefreshDatabase;

    /** @return list<int> chapter numbers 1..n */
    private function surahs(int $n): array
    {
        $ids = [];
        for ($i = 1; $i <= $n; $i++) {
            $ids[] = Chapter::factory()->number($i)->create(['name_arabic' => 'س'.$i, 'name_simple' => 'S'.$i, 'verses_count' => 3])->id;
        }

        return $ids;
    }

    /**
     * One chapter reciter with a file for each covered chapter. In 006 the "person" and
     * the "recording" are the same resource.
     *
     * @param  list<int>  $covers
     * @return array{0: int, 1: int} [reciter id, reciter id]
     */
    private function person(string $slug, array $covers, bool $default = true): array
    {
        $reciter = QuranResource::factory()->chapterReciter()->create(['slug' => $slug, 'name' => $slug, 'is_active' => true, 'display_order' => 1]);
        $reciter->syncTranslations(['name' => ['ar' => 'قارئ '.$slug]]);
        foreach ($covers as $chapter) {
            ChapterAudioFile::factory()->create(['resource_id' => $reciter->id, 'chapter_id' => $chapter, 'url' => "https://cdn.test/{$slug}/{$chapter}.mp3", 'duration_ms' => 90000]);
        }

        return [$reciter->id, $reciter->id];
    }

    private function service(int $seed = 7): PlaylistService
    {
        return new PlaylistService(
            app(AudioRepositoryInterface::class),
            app(ResourceRepositoryInterface::class),
            new Randomizer(new Mt19937($seed)),
        );
    }

    public function test_assignment_is_balanced_across_full_coverage(): void
    {
        $surahs = $this->surahs(30);
        [$a] = $this->person('a', $surahs);
        [$b] = $this->person('b', $surahs);
        [$c] = $this->person('c', $surahs);

        $result = $this->service()->assign([$a, $b, $c], $surahs);

        $this->assertCount(30, $result->items);
        $this->assertSame([], $result->uncovered);
        $perRecording = array_count_values($result->items);
        $this->assertCount(3, $perRecording);
        $this->assertLessThanOrEqual(1, max($perRecording) - min($perRecording));
    }

    public function test_only_covering_recordings_are_assigned_and_uncovered_reported(): void
    {
        $surahs = $this->surahs(4);
        [$a, $recA] = $this->person('a', [1, 2]);
        [$b, $recB] = $this->person('b', [2, 3]);

        $result = $this->service()->assign([$a, $b], $surahs);

        $this->assertSame($recA, $result->items[1]);
        $this->assertSame($recB, $result->items[3]);
        $this->assertContains($result->items[2], [$recA, $recB]);
        $this->assertSame([4], $result->uncovered);
        $this->assertSame([$a => [$recA], $b => [$recB]], $result->alternatives[2]);
    }

    public function test_keep_pins_a_recording_when_it_still_covers(): void
    {
        $surahs = $this->surahs(2);
        [$a] = $this->person('a', $surahs);
        [$b, $recB] = $this->person('b', $surahs);

        $result = $this->service()->assign([$a, $b], $surahs, [1 => $recB, 2 => $recB]);

        $this->assertSame([1 => $recB, 2 => $recB], $result->items);
    }

    public function test_keep_is_ignored_when_the_recording_no_longer_covers(): void
    {
        $surahs = $this->surahs(1);
        [$a, $recA] = $this->person('a', $surahs);
        [$b, $recB] = $this->person('b', []);

        $result = $this->service()->assign([$a, $b], $surahs, [1 => $recB]);

        $this->assertSame([1 => $recA], $result->items);
    }

    public function test_seeded_rng_is_deterministic(): void
    {
        $surahs = $this->surahs(10);
        [$a] = $this->person('a', $surahs);
        [$b] = $this->person('b', $surahs);

        $this->assertSame(
            $this->service(3)->assign([$a, $b], $surahs)->items,
            $this->service(3)->assign([$a, $b], $surahs)->items,
        );
    }

    public function test_inactive_reciter_does_not_count_as_coverage(): void
    {
        $surahs = $this->surahs(1);
        [$a, $recA] = $this->person('a', $surahs);
        DB::table('quran_resources')->where('id', $recA)->update(['is_active' => false]);

        $result = $this->service()->assign([$a], $surahs);

        $this->assertSame([], $result->items);
        $this->assertSame([1], $result->uncovered);
    }

    public function test_invalid_pairs_flags_missing_audio(): void
    {
        $surahs = $this->surahs(2);
        [$a, $recA] = $this->person('a', [1]);

        $bad = $this->service()->invalidPairs([1 => $recA, 2 => $recA, 3 => null]);

        $this->assertSame([['surah' => 2, 'recording' => $recA]], $bad);
    }
}
