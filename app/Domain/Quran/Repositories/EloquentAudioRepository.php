<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

use App\Domain\Quran\DTOs\AudioFileData;
use App\Domain\Quran\DTOs\ImportReport;
use App\Domain\Quran\Enums\ResourceKind;
use App\Domain\Quran\Models\AudioFile;
use App\Domain\Quran\Models\ChapterAudioFile;
use App\Domain\Quran\Models\Verse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class EloquentAudioRepository implements AudioRepositoryInterface
{
    public function __construct(private readonly ImportRepositoryInterface $bulk) {}

    public function paginateAyahFiles(int $resourceId, ?int $chapter, int $perPage): LengthAwarePaginator
    {
        return AudioFile::query()
            ->select(['quran_audio_files.id', 'quran_audio_files.resource_id', 'quran_audio_files.verse_id', 'quran_audio_files.url',
                'quran_audio_files.format', 'quran_audio_files.duration_ms', 'quran_audio_files.is_local', 'v.verse_key', 'v.chapter_id'])
            ->join('quran_verses as v', 'v.id', '=', 'quran_audio_files.verse_id')
            ->where('quran_audio_files.resource_id', $resourceId)
            ->when($chapter !== null, fn (Builder $q) => $q->where('v.chapter_id', $chapter))
            ->orderBy('quran_audio_files.verse_id')
            ->paginate(min(100, max(1, $perPage)))
            ->withQueryString();
    }

    public function ayahFilesWhere(int $resourceId, array $axisFilter): Collection
    {
        $allowed = ['chapter_id', 'juz_number', 'hizb_number', 'rub_el_hizb_number', 'manzil_number', 'ruku_number', 'page_number', 'verse_key'];
        $filter = [];
        foreach (array_intersect_key($axisFilter, array_flip($allowed)) as $column => $value) {
            $filter['v.'.$column] = $value;
        }

        return DB::table('quran_audio_files as a')
            ->join('quran_verses as v', 'v.id', '=', 'a.verse_id')
            ->select(['a.id', 'v.verse_key', 'a.url', 'a.segments', 'a.duration_ms', 'a.format'])
            ->where('a.resource_id', $resourceId)
            ->where($filter)
            ->orderBy('a.verse_id')
            ->get();
    }

    public function findAyahFile(int $resourceId, int $id): ?AudioFile
    {
        return AudioFile::query()->where('resource_id', $resourceId)->find($id);
    }

    public function saveAyahFile(int $resourceId, AudioFileData $data): AudioFile
    {
        $verseId = Verse::query()->where('verse_key', (string) $data->target)->value('id')
            ?? throw new InvalidArgumentException('Unknown verse key '.$data->target);

        return DB::transaction(function () use ($resourceId, $verseId, $data): AudioFile {
            $file = AudioFile::query()->firstOrNew(['resource_id' => $resourceId, 'verse_id' => (int) $verseId]);
            $file->fill([
                'url' => $data->url,
                'format' => $data->format,
                'duration_ms' => $data->durationMs,
                'segments' => $data->segments,
                'is_local' => $data->isLocal,
            ])->save();
            $this->touchCount($resourceId, 'quran_audio_files');

            return $file;
        });
    }

    public function deleteAyahFile(int $resourceId, int $id): void
    {
        AudioFile::query()->where('resource_id', $resourceId)->whereKey($id)->delete();
        $this->touchCount($resourceId, 'quran_audio_files');
    }

    public function upsertAyahFiles(int $resourceId, array $files): ImportReport
    {
        $report = new ImportReport('audio:ayah');
        $verseIds = DB::table('quran_verses')->pluck('id', 'verse_key')->all();

        $rows = [];
        foreach ($files as $file) {
            $report->expected++;
            $verseId = $verseIds[(string) $file->target] ?? null;
            if ($verseId === null) {
                $report->fail('verse:'.$file->target, 'unknown verse key');

                continue;
            }
            $rows[] = [
                'resource_id' => $resourceId,
                'verse_id' => (int) $verseId,
                'url' => mb_substr($file->url, 0, 500),
                'format' => $file->format,
                'duration_ms' => $file->durationMs,
                'segments' => $file->segments === null ? null : json_encode($file->segments),
                'is_local' => $file->isLocal,
            ];
        }

        if ($rows !== []) {
            $report->add($this->bulk->upsert('quran_audio_files', $rows, ['resource_id', 'verse_id'], ['url', 'format', 'duration_ms', 'segments', 'is_local']));
        }
        $this->touchCount($resourceId, 'quran_audio_files');

        return $report;
    }

    public function chapterFiles(int $resourceId): Collection
    {
        return ChapterAudioFile::query()
            ->select(['id', 'resource_id', 'chapter_id', 'url', 'file_size', 'format', 'duration_ms', 'is_local', 'has_timestamps', 'updated_at'])
            ->with('chapter:id,name_arabic,name_simple,verses_count')
            ->where('resource_id', $resourceId)
            ->orderBy('chapter_id')
            ->get();
    }

    public function chapterFile(int $resourceId, int $chapter, bool $withTimestamps = false): ?ChapterAudioFile
    {
        return ChapterAudioFile::query()
            ->select(['id', 'resource_id', 'chapter_id', 'url', 'file_size', 'format', 'duration_ms', 'is_local', 'has_timestamps'])
            ->when($withTimestamps, fn (Builder $q) => $q->with([
                'verseTimestamps' => fn ($t) => $t->select(['id', 'chapter_audio_file_id', 'verse_id', 'timestamp_from', 'timestamp_to', 'duration', 'segments']),
                'verseTimestamps.verse:id,verse_key',
            ]))
            ->where('resource_id', $resourceId)
            ->where('chapter_id', $chapter)
            ->first();
    }

    public function findChapterFile(int $resourceId, int $id): ?ChapterAudioFile
    {
        return ChapterAudioFile::query()->where('resource_id', $resourceId)->find($id);
    }

    public function activeChapterFiles(int $chapter): Collection
    {
        return ChapterAudioFile::query()
            ->select(['quran_chapter_audio_files.id', 'quran_chapter_audio_files.resource_id', 'quran_chapter_audio_files.chapter_id',
                'quran_chapter_audio_files.url', 'quran_chapter_audio_files.format', 'quran_chapter_audio_files.has_timestamps'])
            ->join('quran_resources as r', 'r.id', '=', 'quran_chapter_audio_files.resource_id')
            ->where('quran_chapter_audio_files.chapter_id', $chapter)
            ->where('r.kind', ResourceKind::CHAPTER_RECITER->value)
            ->where('r.is_active', true)
            ->orderByDesc('r.is_default')
            ->orderBy('r.display_order')
            ->get();
    }

    public function playableChapterFiles(array $chapterIds, ?array $resourceIds = null): Collection
    {
        if ($chapterIds === []) {
            return new Collection;
        }

        return DB::table('quran_chapter_audio_files as f')
            ->join('quran_resources as r', 'r.id', '=', 'f.resource_id')
            ->select(['f.resource_id', 'f.chapter_id', 'f.url', 'f.duration_ms', 'r.display_order'])
            ->where('r.kind', ResourceKind::CHAPTER_RECITER->value)
            ->where('r.is_active', true)
            ->whereIn('f.chapter_id', $chapterIds)
            ->when($resourceIds !== null, fn (QueryBuilder $q): QueryBuilder => $q->whereIn('f.resource_id', $resourceIds))
            ->orderBy('r.display_order')
            ->orderBy('f.resource_id')
            ->get();
    }

    public function saveChapterFile(int $resourceId, AudioFileData $data): ChapterAudioFile
    {
        $chapter = (int) $data->target;
        if ($chapter < 1 || $chapter > 114) {
            throw new InvalidArgumentException('Unknown chapter '.$data->target);
        }

        return DB::transaction(function () use ($resourceId, $chapter, $data): ChapterAudioFile {
            $file = ChapterAudioFile::query()->firstOrNew(['resource_id' => $resourceId, 'chapter_id' => $chapter]);
            $urlChanged = $file->exists && $file->url !== $data->url;
            $file->fill([
                'url' => $data->url,
                'format' => $data->format ?? 'mp3',
                'file_size' => $data->fileSize,
                'duration_ms' => $data->durationMs,
                'is_local' => $data->isLocal,
            ]);
            if ($urlChanged) {
                // Timestamps describe the old recording; they no longer match.
                $file->verseTimestamps()->delete();
                $file->has_timestamps = false;
            }
            $file->save();
            $this->touchCount($resourceId, 'quran_chapter_audio_files');

            return $file;
        });
    }

    public function deleteChapterFile(int $resourceId, int $id): void
    {
        ChapterAudioFile::query()->where('resource_id', $resourceId)->whereKey($id)->delete();
        $this->touchCount($resourceId, 'quran_chapter_audio_files');
    }

    public function upsertChapterFiles(int $resourceId, array $files): ImportReport
    {
        $report = new ImportReport('audio:chapter');
        $rows = [];
        foreach ($files as $file) {
            $report->expected++;
            $chapter = (int) $file->target;
            if ($chapter < 1 || $chapter > 114) {
                $report->fail('chapter:'.$file->target, 'unknown chapter');

                continue;
            }
            $rows[] = [
                'resource_id' => $resourceId,
                'chapter_id' => $chapter,
                'url' => mb_substr($file->url, 0, 500),
                'format' => $file->format ?? 'mp3',
                'file_size' => $file->fileSize,
                'duration_ms' => $file->durationMs,
                'is_local' => $file->isLocal,
            ];
        }

        if ($rows !== []) {
            $report->add($this->bulk->upsert('quran_chapter_audio_files', $rows, ['resource_id', 'chapter_id'], ['url', 'format', 'file_size', 'duration_ms', 'is_local']));
        }
        $this->touchCount($resourceId, 'quran_chapter_audio_files');

        return $report;
    }

    public function replaceTimestamps(ChapterAudioFile $file, array $rows): ImportReport
    {
        $report = new ImportReport('audio:timestamps');
        $verseIds = DB::table('quran_verses')->where('chapter_id', $file->chapter_id)->pluck('id', 'verse_key')->all();

        $prepared = [];
        foreach ($rows as $row) {
            $report->expected++;
            $verseId = $verseIds[$row->verseKey] ?? null;
            if ($verseId === null) {
                $report->fail('verse:'.$row->verseKey, 'not a verse of chapter '.$file->chapter_id);

                continue;
            }
            $prepared[] = [
                'chapter_audio_file_id' => $file->id,
                'verse_id' => (int) $verseId,
                'timestamp_from' => max(0, $row->from),
                'timestamp_to' => max(0, $row->to),
                'duration' => $row->to - $row->from,
                'segments' => $row->segments === null ? null : json_encode($row->segments),
            ];
        }

        $report->add($this->bulk->replaceForOwner('quran_chapter_audio_timestamps', 'chapter_audio_file_id', $file->id, $prepared,
            ['chapter_audio_file_id', 'verse_id'], ['timestamp_from', 'timestamp_to', 'duration', 'segments']));

        DB::table('quran_chapter_audio_files')->where('id', $file->id)->update(['has_timestamps' => $prepared !== []]);

        return $report;
    }

    private function touchCount(int $resourceId, string $table): void
    {
        DB::table('quran_resources')->where('id', $resourceId)
            ->update(['records_count' => DB::table($table)->where('resource_id', $resourceId)->count()]);
    }
}
