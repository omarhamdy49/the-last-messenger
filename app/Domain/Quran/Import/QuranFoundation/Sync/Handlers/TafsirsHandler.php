<?php

declare(strict_types=1);

namespace App\Domain\Quran\Import\QuranFoundation\Sync\Handlers;

use App\Domain\Quran\DTOs\ImportReport;
use App\Domain\Quran\Enums\ResourceKind;
use App\Domain\Quran\Enums\SyncResourceGroup;

/**
 * tafsirs → quran_tafsirs. A tafsir may cover a verse group
 * (`group_verse_key_from/to`); QF sends one record per verse and the text on the
 * group's first verse, so records are stored as sent.
 */
final class TafsirsHandler extends ResourceRowsHandler
{
    public function group(): SyncResourceGroup
    {
        return SyncResourceGroup::TAFSIRS;
    }

    protected function kind(): ResourceKind
    {
        return ResourceKind::TAFSIR;
    }

    protected function table(): string
    {
        return 'quran_tafsirs';
    }

    protected function uniqueBy(): array
    {
        return ['resource_id', 'verse_id'];
    }

    protected function updateColumns(): array
    {
        return ['qf_id', 'text'];
    }

    protected function lockColumn(): ?string
    {
        return 'is_locked';
    }

    protected function recordTypes(): array
    {
        return ['tafsir'];
    }

    protected function map(int $resourceId, array $record, ImportReport $report): ?array
    {
        $verseId = (int) ($record['verse_id'] ?? 0);
        if (! $this->verseExists($verseId)) {
            $report->fail('tafsir:'.($record['id'] ?? '?'), "verse {$verseId} not imported");

            return null;
        }

        return [
            'resource_id' => $resourceId,
            'verse_id' => $verseId,
            'qf_id' => isset($record['id']) ? (int) $record['id'] : null,
            'text' => (string) ($record['text'] ?? ''),
        ];
    }
}
