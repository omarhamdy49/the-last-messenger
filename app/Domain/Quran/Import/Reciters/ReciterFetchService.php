<?php

declare(strict_types=1);

namespace App\Domain\Quran\Import\Reciters;

use App\Domain\Quran\DTOs\AudioFileData;
use App\Domain\Quran\DTOs\ImportReport;
use App\Domain\Quran\DTOs\ReciterPreview;
use App\Domain\Quran\Enums\ExternalSource;
use App\Domain\Quran\Enums\ResourceKind;
use App\Domain\Quran\Import\QuranFoundation\Exceptions\QfApiException;
use App\Domain\Quran\Models\QuranResource;
use App\Domain\Quran\Repositories\AudioRepositoryInterface;
use App\Domain\Quran\Repositories\ResourceRepositoryInterface;
use App\Domain\Quran\Services\QuranCacheKeys;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Admin "Fetch data / audio / timestamps" for one reciter resource, from the source
 * named by its `external_source` + `external_ref_id`. Each step is idempotent; the
 * outcome is written to `last_fetch_report`.
 */
final class ReciterFetchService
{
    public function __construct(
        private readonly ResourceRepositoryInterface $resources,
        private readonly AudioRepositoryInterface $audio,
    ) {}

    public function fetcher(ExternalSource $source): ReciterFetcher
    {
        return match ($source) {
            ExternalSource::QF => app(QfReciterFetcher::class),
            ExternalSource::MP3QURAN => app(Mp3QuranReciterFetcher::class),
        };
    }

    public function lookup(ExternalSource $source, string $refId, ResourceKind $kind): ReciterPreview
    {
        return $this->fetcher($source)->lookup(trim($refId), $kind);
    }

    public function fetch(QuranResource $resource, bool $data, bool $audio, bool $timestamps, bool $download): ImportReport
    {
        $report = new ImportReport('reciter-fetch:'.$resource->id);

        // A QF catalogue row without an explicit source is fetched from QF by its qf_id.
        $source = $resource->external_source ?? ($resource->qf_id !== null ? ExternalSource::QF : null);
        $refId = (string) ($resource->external_ref_id ?? $resource->qf_id ?? '');

        if (! $resource->kind->isReciter() || $source === null || $refId === '') {
            $report->fail('resource:'.$resource->id, 'The resource has no source and reference id.');
            $this->resources->recordFetch($resource->id, $report->toArray());

            return $report;
        }

        $fetcher = $this->fetcher($source);

        try {
            if ($data) {
                $this->resources->applyFetchedData($resource->id, $fetcher->data($refId, $resource->kind));
                $report->note('data updated');
            }
            if ($audio) {
                $this->fetchAudio($resource, $fetcher, $refId, $download || $resource->store_audio_locally, $report);
            }
            if ($timestamps) {
                $this->fetchTimestamps($resource, $fetcher, $refId, $report);
            }
        } catch (InvalidArgumentException|RuntimeException|QfApiException $e) {
            $report->fail('source', $e->getMessage());
        } finally {
            $this->resources->recordFetch($resource->id, $report->toArray());
            QuranCacheKeys::flushResources();
            QuranCacheKeys::flushReciters();
        }

        return $report;
    }

    private function fetchAudio(QuranResource $resource, ReciterFetcher $fetcher, string $refId, bool $download, ImportReport $report): void
    {
        $isChapter = $resource->kind === ResourceKind::CHAPTER_RECITER;
        $batch = [];

        foreach ($fetcher->files($refId, $resource->kind) as $file) {
            if ($download) {
                $file = $this->downloaded($resource, $file, $report);
            }
            $batch[] = $file;

            if (count($batch) === 500) {
                $report->merge($isChapter ? $this->audio->upsertChapterFiles($resource->id, $batch) : $this->audio->upsertAyahFiles($resource->id, $batch));
                $batch = [];
            }
        }

        if ($batch !== []) {
            $report->merge($isChapter ? $this->audio->upsertChapterFiles($resource->id, $batch) : $this->audio->upsertAyahFiles($resource->id, $batch));
        }
    }

    private function fetchTimestamps(QuranResource $resource, ReciterFetcher $fetcher, string $refId, ImportReport $report): void
    {
        if ($resource->kind !== ResourceKind::CHAPTER_RECITER) {
            $report->note('ayah recitations carry word segments on each file; no chapter timestamps to fetch');

            return;
        }

        $files = $this->audio->chapterFiles($resource->id);
        if ($files->isEmpty()) {
            $report->fail('timestamps', 'Fetch the audio files first.');

            return;
        }

        foreach ($files as $file) {
            try {
                $rows = $fetcher->timestamps($refId, (int) $file->chapter_id);
            } catch (RuntimeException $e) {
                $report->fail('chapter:'.$file->chapter_id, $e->getMessage());

                continue;
            }
            if ($rows === []) {
                continue;
            }
            $report->merge($this->audio->replaceTimestamps($file, $rows));
        }
    }

    /**
     * Stream a file to the public disk; on any problem keep the remote URL and report it.
     */
    private function downloaded(QuranResource $resource, AudioFileData $file, ImportReport $report): AudioFileData
    {
        $name = is_int($file->target) ? sprintf('%03d', $file->target) : str_replace(':', '_', (string) $file->target);
        $temp = tempnam(sys_get_temp_dir(), 'quran-audio-');
        $maxBytes = (int) config('quran.audio.max_file_mb', 80) * 1024 * 1024;

        try {
            $response = Http::timeout(600)->sink($temp)->get($file->url);
            $size = (int) filesize($temp);
            if (! $response->successful() || $size === 0 || $size > $maxBytes || ! self::looksLikeMp3($temp)) {
                throw new RuntimeException('download rejected (HTTP '.$response->status().', '.$size.' bytes)');
            }

            $directory = trim((string) config('quran.audio.path', 'quran/audio'), '/').'/'.$resource->id;
            $disk = (string) config('quran.audio.disk', 'public');
            $path = Storage::disk($disk)->putFileAs($directory, new File($temp), $name.'.mp3');

            return new AudioFileData($file->target, Storage::disk($disk)->url((string) $path), $file->format, $file->durationMs, $size, $file->segments, isLocal: true);
        } catch (Throwable $e) {
            $report->fail('download:'.$file->target, $e->getMessage());

            return $file;
        } finally {
            @unlink($temp);
        }
    }

    /**
     * An ID3 tag or an MPEG audio frame sync at the start of the file.
     */
    private static function looksLikeMp3(string $path): bool
    {
        $head = (string) file_get_contents($path, false, null, 0, 3);

        return str_starts_with($head, 'ID3') || (strlen($head) >= 2 && ord($head[0]) === 0xFF && (ord($head[1]) & 0xE0) === 0xE0);
    }
}
