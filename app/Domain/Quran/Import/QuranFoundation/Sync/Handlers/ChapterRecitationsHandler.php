<?php

declare(strict_types=1);

namespace App\Domain\Quran\Import\QuranFoundation\Sync\Handlers;

use App\Domain\Quran\DTOs\ImportReport;
use App\Domain\Quran\Enums\ResourceKind;
use App\Domain\Quran\Enums\SyncResourceGroup;
use App\Domain\Quran\Import\QuranFoundation\Mappers\VerseMapper;

/**
 * chapter_recitations → quran_chapter_audio_files (one whole-chapter file per
 * chapter). Timestamps are not in snapshots: `quran:qf:import-timestamps` adds them.
 */
final class ChapterRecitationsHandler extends ResourceRowsHandler
{
    public function group(): SyncResourceGroup
    {
        return SyncResourceGroup::CHAPTER_RECITATIONS;
    }

    protected function kind(): ResourceKind
    {
        return ResourceKind::CHAPTER_RECITER;
    }

    protected function table(): string
    {
        return 'quran_chapter_audio_files';
    }

    protected function uniqueBy(): array
    {
        return ['resource_id', 'chapter_id'];
    }

    protected function updateColumns(): array
    {
        return ['qf_id', 'url', 'file_size', 'format'];
    }

    protected function recordTypes(): array
    {
        return ['chapter_audio_file'];
    }

    protected function map(int $resourceId, array $record, ImportReport $report): ?array
    {
        return self::chapterFileRow($resourceId, $record, $report);
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>|null
     */
    public static function chapterFileRow(int $resourceId, array $record, ImportReport $report): ?array
    {
        $chapter = (int) ($record['chapter_id'] ?? 0);
        $url = VerseMapper::absoluteAudio($record['audio_url'] ?? null);
        if ($chapter < 1 || $chapter > 114 || $url === null) {
            $report->fail('chapter_audio_file:'.($record['id'] ?? '?'), 'missing chapter_id or audio_url');

            return null;
        }

        return [
            'resource_id' => $resourceId,
            'chapter_id' => $chapter,
            'qf_id' => isset($record['id']) ? (int) $record['id'] : null,
            'url' => mb_substr($url, 0, 500),
            'file_size' => isset($record['file_size']) ? (int) $record['file_size'] : null,
            'format' => (string) ($record['format'] ?? 'mp3'),
        ];
    }
}
