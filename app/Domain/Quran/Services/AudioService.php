<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use App\Domain\Quran\DTOs\AudioFileData;
use App\Domain\Quran\DTOs\ImportReport;
use App\Domain\Quran\DTOs\TimestampRowData;
use App\Domain\Quran\Enums\ResourceKind;
use App\Domain\Quran\Import\QuranFoundation\Mappers\VerseMapper;
use App\Domain\Quran\Models\AudioFile;
use App\Domain\Quran\Models\ChapterAudioFile;
use App\Domain\Quran\Models\QuranResource;
use App\Domain\Quran\Repositories\AudioRepositoryInterface;
use App\Domain\Quran\Repositories\VerseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

/**
 * Recitation audio: ayah-by-ayah files, whole-chapter files and their verse timings.
 * Files stream from their source URL unless uploaded or downloaded to the public disk.
 */
final class AudioService
{
    private const AUDIO_MIME = ['audio/mpeg', 'audio/mp3', 'audio/x-mpeg', 'audio/mpeg3'];

    public function __construct(
        private readonly AudioRepositoryInterface $audio,
        private readonly VerseRepositoryInterface $verses,
    ) {}

    public static function absolutize(?string $url): ?string
    {
        return VerseMapper::absoluteAudio($url);
    }

    public function paginateAyahFiles(QuranResource $recitation, ?int $chapter, int $perPage): LengthAwarePaginator
    {
        $this->assertKind($recitation, ResourceKind::RECITATION);

        return $this->audio->paginateAyahFiles($recitation->id, $chapter, $perPage);
    }

    /**
     * @param  array<string, int>  $axisFilter
     * @return Collection<int, object>
     */
    public function ayahFilesWhere(QuranResource $recitation, array $axisFilter): Collection
    {
        return $this->audio->ayahFilesWhere($recitation->id, $axisFilter);
    }

    public function findAyahFile(QuranResource $recitation, int $id): ?AudioFile
    {
        return $this->audio->findAyahFile($recitation->id, $id);
    }

    public function saveAyahFile(QuranResource $recitation, AudioFileData $data): AudioFile
    {
        $this->assertKind($recitation, ResourceKind::RECITATION);
        $file = $this->audio->saveAyahFile($recitation->id, $data);
        QuranCacheKeys::flushReciters();

        return $file;
    }

    public function deleteAyahFile(QuranResource $recitation, int $id): void
    {
        $this->audio->deleteAyahFile($recitation->id, $id);
        QuranCacheKeys::flushReciters();
    }

    /**
     * Create ayah files for every verse of a chapter (or the whole Quran) from a URL
     * template with `{surah:03}`/`{chapter:03}` and `{ayah:03}` placeholders.
     */
    public function fillAyahFromTemplate(QuranResource $recitation, string $template, ?int $chapter): ImportReport
    {
        $this->assertKind($recitation, ResourceKind::RECITATION);
        $this->assertTemplate($template, needsAyah: true);

        $files = [];
        foreach ($this->verses->references($chapter) as $verse) {
            $files[] = new AudioFileData(
                target: $verse['verse_key'],
                url: $this->expand($template, $verse['chapter_id'], $verse['verse_number']),
            );
        }

        $report = $this->audio->upsertAyahFiles($recitation->id, $files);
        QuranCacheKeys::flushReciters();

        return $report;
    }

    /**
     * @return Collection<int, ChapterAudioFile>
     */
    public function chapterFiles(QuranResource $reciter): Collection
    {
        return $this->audio->chapterFiles($reciter->id);
    }

    public function chapterFile(QuranResource $reciter, int $chapter, bool $withTimestamps = false): ?ChapterAudioFile
    {
        return $this->audio->chapterFile($reciter->id, $chapter, $withTimestamps);
    }

    public function findChapterFile(QuranResource $reciter, int $id): ?ChapterAudioFile
    {
        return $this->audio->findChapterFile($reciter->id, $id);
    }

    /**
     * @return Collection<int, ChapterAudioFile>
     */
    public function activeChapterFiles(int $chapter): Collection
    {
        return Cache::remember(QuranCacheKeys::chapterAudio($chapter), 3600, fn (): Collection => $this->audio->activeChapterFiles($chapter));
    }

    public function saveChapterFile(QuranResource $reciter, AudioFileData $data): ChapterAudioFile
    {
        $this->assertKind($reciter, ResourceKind::CHAPTER_RECITER);
        $file = $this->audio->saveChapterFile($reciter->id, $data);
        QuranCacheKeys::flushReciters();

        return $file;
    }

    public function deleteChapterFile(QuranResource $reciter, int $id): void
    {
        $this->audio->deleteChapterFile($reciter->id, $id);
        QuranCacheKeys::flushReciters();
    }

    public function fillChaptersFromTemplate(QuranResource $reciter, string $template): ImportReport
    {
        $this->assertKind($reciter, ResourceKind::CHAPTER_RECITER);
        $this->assertTemplate($template, needsAyah: false);

        $files = [];
        for ($chapter = 1; $chapter <= 114; $chapter++) {
            $files[] = new AudioFileData(target: $chapter, url: $this->expand($template, $chapter, 0));
        }

        $report = $this->audio->upsertChapterFiles($reciter->id, $files);
        QuranCacheKeys::flushReciters();

        return $report;
    }

    /**
     * Store an uploaded mp3 on the public disk and return its public URL.
     */
    public function storeUpload(UploadedFile $file, QuranResource $resource, string $name): string
    {
        $maxBytes = (int) config('quran.audio.max_file_mb', 80) * 1024 * 1024;
        if ($file->getSize() > $maxBytes) {
            throw new InvalidArgumentException('The audio file is larger than '.config('quran.audio.max_file_mb').' MB.');
        }
        if (! in_array((string) $file->getMimeType(), self::AUDIO_MIME, true)) {
            throw new InvalidArgumentException('Only MP3 audio files are accepted.');
        }

        $disk = (string) config('quran.audio.disk', 'public');
        $directory = trim((string) config('quran.audio.path', 'quran/audio'), '/').'/'.$resource->id;
        $path = $file->storeAs($directory, preg_replace('/[^a-z0-9_-]/i', '', $name).'.mp3', $disk);

        return Storage::disk($disk)->url((string) $path);
    }

    /**
     * @param  list<TimestampRowData>  $rows
     */
    public function replaceTimestamps(ChapterAudioFile $file, array $rows): ImportReport
    {
        $report = $this->audio->replaceTimestamps($file, $rows);
        Cache::forget(QuranCacheKeys::chapterTimestamps($file->resource_id, $file->chapter_id));
        QuranCacheKeys::flushReciters();

        return $report;
    }

    /**
     * Verse timings for a reciter's chapter, for the player.
     *
     * @return list<array{verse_key: string, from: int, to: int, segments: ?array<int, array<int, int>>}>
     */
    public function timings(int $reciterId, int $chapter): array
    {
        return Cache::remember(QuranCacheKeys::chapterTimestamps($reciterId, $chapter), 3600, function () use ($reciterId, $chapter): array {
            $file = $this->audio->chapterFile($reciterId, $chapter, withTimestamps: true);
            if ($file === null) {
                return [];
            }

            return $file->verseTimestamps->map(static fn ($t): array => [
                'verse_key' => (string) $t->verse?->verse_key,
                'from' => (int) $t->timestamp_from,
                'to' => (int) $t->timestamp_to,
                'segments' => $t->segments,
            ])->values()->all();
        });
    }

    /**
     * The timing entry that contains `$ms` (binary search over sorted timings).
     *
     * @param  list<array{verse_key: string, from: int, to: int, segments: ?array<int, array<int, int>>}>  $timings
     * @return array{verse_key: string, from: int, to: int, segments: ?array<int, array<int, int>>}|null
     */
    public static function timingAt(array $timings, int $ms): ?array
    {
        $lo = 0;
        $hi = count($timings) - 1;
        while ($lo <= $hi) {
            $mid = intdiv($lo + $hi, 2);
            if ($ms < $timings[$mid]['from']) {
                $hi = $mid - 1;
            } elseif ($ms >= $timings[$mid]['to']) {
                $lo = $mid + 1;
            } else {
                return $timings[$mid];
            }
        }

        return null;
    }

    private function expand(string $template, int $chapter, int $ayah): string
    {
        return strtr($template, [
            '{surah:03}' => sprintf('%03d', $chapter),
            '{chapter:03}' => sprintf('%03d', $chapter),
            '{surah}' => (string) $chapter,
            '{chapter}' => (string) $chapter,
            '{ayah:03}' => sprintf('%03d', $ayah),
            '{ayah}' => (string) $ayah,
        ]);
    }

    private function assertTemplate(string $template, bool $needsAyah): void
    {
        if (! preg_match('#^https?://#i', $template)) {
            throw new InvalidArgumentException('The URL template must start with http:// or https://.');
        }
        if (! str_contains($template, '{surah') && ! str_contains($template, '{chapter')) {
            throw new InvalidArgumentException('The URL template needs a {surah:03} or {chapter:03} placeholder.');
        }
        if ($needsAyah && ! str_contains($template, '{ayah')) {
            throw new InvalidArgumentException('An ayah template needs an {ayah:03} placeholder.');
        }
    }

    private function assertKind(QuranResource $resource, ResourceKind $kind): void
    {
        if ($resource->kind !== $kind) {
            throw new InvalidArgumentException("Resource {$resource->id} is not a {$kind->value}.");
        }
    }
}
