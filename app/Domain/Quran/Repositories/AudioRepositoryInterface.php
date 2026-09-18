<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

use App\Domain\Quran\DTOs\AudioFileData;
use App\Domain\Quran\DTOs\ImportReport;
use App\Domain\Quran\DTOs\TimestampRowData;
use App\Domain\Quran\Models\AudioFile;
use App\Domain\Quran\Models\ChapterAudioFile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface AudioRepositoryInterface
{
    public function paginateAyahFiles(int $resourceId, ?int $chapter, int $perPage): LengthAwarePaginator;

    /**
     * @param  array<string, int>  $axisFilter  verse columns, e.g. ['chapter_id' => 1]
     * @return Collection<int, object> verse_key, url, segments, duration_ms, format, id
     */
    public function ayahFilesWhere(int $resourceId, array $axisFilter): Collection;

    public function findAyahFile(int $resourceId, int $id): ?AudioFile;

    public function saveAyahFile(int $resourceId, AudioFileData $data): AudioFile;

    public function deleteAyahFile(int $resourceId, int $id): void;

    /**
     * @param  list<AudioFileData>  $files
     */
    public function upsertAyahFiles(int $resourceId, array $files): ImportReport;

    /**
     * @return Collection<int, ChapterAudioFile>
     */
    public function chapterFiles(int $resourceId): Collection;

    public function chapterFile(int $resourceId, int $chapter, bool $withTimestamps = false): ?ChapterAudioFile;

    public function findChapterFile(int $resourceId, int $id): ?ChapterAudioFile;

    /**
     * Chapter files of every active chapter reciter for one chapter.
     *
     * @return Collection<int, ChapterAudioFile>
     */
    public function activeChapterFiles(int $chapter): Collection;

    /**
     * Playable (chapter, reciter) pairs for playlist building: a chapter file whose
     * resource is an active chapter reciter. One query for every chapter in scope,
     * so assigning a 114-chapter playlist never queries per chapter.
     *
     * @param  list<int>  $chapterIds
     * @param  list<int>|null  $resourceIds  restrict to these reciters
     * @return Collection<int, object{resource_id: int, chapter_id: int, url: string, duration_ms: ?int, display_order: int}>
     */
    public function playableChapterFiles(array $chapterIds, ?array $resourceIds = null): Collection;

    public function saveChapterFile(int $resourceId, AudioFileData $data): ChapterAudioFile;

    public function deleteChapterFile(int $resourceId, int $id): void;

    /**
     * @param  list<AudioFileData>  $files
     */
    public function upsertChapterFiles(int $resourceId, array $files): ImportReport;

    /**
     * @param  list<TimestampRowData>  $rows
     */
    public function replaceTimestamps(ChapterAudioFile $file, array $rows): ImportReport;
}
