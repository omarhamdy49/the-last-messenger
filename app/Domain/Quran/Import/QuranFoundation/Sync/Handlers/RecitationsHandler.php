<?php

declare(strict_types=1);

namespace App\Domain\Quran\Import\QuranFoundation\Sync\Handlers;

use App\Domain\Quran\DTOs\ImportReport;
use App\Domain\Quran\Enums\ResourceKind;
use App\Domain\Quran\Enums\SyncResourceGroup;
use App\Domain\Quran\Import\QuranFoundation\Mappers\VerseMapper;
use Illuminate\Support\Facades\DB;

/**
 * recitations (ayah-by-ayah) → quran_audio_files.
 *
 * The legacy group also carries `chapter_audio_file` records for backward
 * compatibility; those belong to the chapter reciter whose QF id is
 * `audio_recitation_id` and are routed to quran_chapter_audio_files (reported when
 * that reciter is not imported).
 */
final class RecitationsHandler extends ResourceRowsHandler
{
    /** @var array<string, int>|null verse_key => id */
    private ?array $verseIdsByKey = null;

    public function group(): SyncResourceGroup
    {
        return SyncResourceGroup::RECITATIONS;
    }

    protected function kind(): ResourceKind
    {
        return ResourceKind::RECITATION;
    }

    protected function table(): string
    {
        return 'quran_audio_files';
    }

    protected function uniqueBy(): array
    {
        return ['resource_id', 'verse_id'];
    }

    protected function updateColumns(): array
    {
        return ['qf_id', 'url', 'format', 'duration_ms', 'segments'];
    }

    protected function recordTypes(): array
    {
        return ['audio_file', 'chapter_audio_file'];
    }

    public function replace(int $qfResourceId, array $records, ImportReport $report): void
    {
        [$ayahRecords, $chapterRecords] = $this->split($records);
        parent::replace($qfResourceId, $ayahRecords, $report);
        $this->writeChapterFiles($chapterRecords, $report);
    }

    public function upsertRows(int $qfResourceId, string $recordType, array $records, ImportReport $report): void
    {
        if ($recordType === 'chapter_audio_file') {
            $this->writeChapterFiles($records, $report);

            return;
        }

        parent::upsertRows($qfResourceId, $recordType, $records, $report);
    }

    protected function map(int $resourceId, array $record, ImportReport $report): ?array
    {
        $this->verseIdsByKey ??= DB::table('quran_verses')->pluck('id', 'verse_key')->map(static fn (mixed $v): int => (int) $v)->all();
        $verseId = $this->verseIdsByKey[(string) ($record['verse_key'] ?? '')] ?? null;
        $url = VerseMapper::absoluteAudio($record['url'] ?? null);

        if ($verseId === null || $url === null) {
            $report->fail('audio_file:'.($record['id'] ?? '?'), 'unknown verse_key or missing url');

            return null;
        }

        $segments = AudioSegments::normalize($record['segments'] ?? null);

        return [
            'resource_id' => $resourceId,
            'verse_id' => $verseId,
            'qf_id' => isset($record['id']) ? (int) $record['id'] : null,
            'url' => mb_substr($url, 0, 500),
            'format' => isset($record['format']) ? (string) $record['format'] : null,
            // QF reports ayah durations in seconds.
            'duration_ms' => isset($record['duration']) ? (int) round((float) $record['duration'] * 1000) : null,
            'segments' => $segments === null ? null : json_encode($segments),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @return array{0: list<array<string, mixed>>, 1: list<array<string, mixed>>}
     */
    private function split(array $records): array
    {
        $ayah = [];
        $chapter = [];
        foreach ($records as $record) {
            if (($record['record_type'] ?? 'audio_file') === 'chapter_audio_file') {
                $chapter[] = $record;
            } else {
                $ayah[] = $record;
            }
        }

        return [$ayah, $chapter];
    }

    /**
     * @param  list<array<string, mixed>>  $records
     */
    private function writeChapterFiles(array $records, ImportReport $report): void
    {
        $byReciter = [];
        foreach ($records as $record) {
            $byReciter[(int) ($record['audio_recitation_id'] ?? 0)][] = $record;
        }

        foreach ($byReciter as $qfReciterId => $group) {
            $resourceId = $this->sync->resourceId(ResourceKind::CHAPTER_RECITER, $qfReciterId);
            if ($resourceId === null) {
                $report->note(count($group)." chapter file(s) for chapter reciter {$qfReciterId} skipped: reciter not imported");

                continue;
            }

            $rows = array_values(array_filter(array_map(
                static fn (array $r): ?array => ChapterRecitationsHandler::chapterFileRow($resourceId, $r, $report),
                $group,
            )));
            $report->add($this->rows->upsert('quran_chapter_audio_files', $rows, ['resource_id', 'chapter_id'], ['qf_id', 'url', 'file_size', 'format']));
            $this->sync->touchResource($resourceId, 'quran_chapter_audio_files');
        }
    }
}
