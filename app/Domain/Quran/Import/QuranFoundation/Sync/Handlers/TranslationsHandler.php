<?php

declare(strict_types=1);

namespace App\Domain\Quran\Import\QuranFoundation\Sync\Handlers;

use App\Domain\Quran\DTOs\ImportReport;
use App\Domain\Quran\Enums\ResourceKind;
use App\Domain\Quran\Enums\SyncResourceGroup;

/**
 * translations → quran_translations. Footnotes arrive as a list of
 * {id, text, …}; they are stored as an id => text map (the text references them
 * as `<sup foot_note=ID>`).
 */
final class TranslationsHandler extends ResourceRowsHandler
{
    public function group(): SyncResourceGroup
    {
        return SyncResourceGroup::TRANSLATIONS;
    }

    protected function kind(): ResourceKind
    {
        return ResourceKind::TRANSLATION;
    }

    protected function table(): string
    {
        return 'quran_translations';
    }

    protected function uniqueBy(): array
    {
        return ['resource_id', 'verse_id'];
    }

    protected function updateColumns(): array
    {
        return ['qf_id', 'text', 'foot_notes'];
    }

    protected function lockColumn(): ?string
    {
        return 'is_locked';
    }

    protected function recordTypes(): array
    {
        return ['translation'];
    }

    protected function map(int $resourceId, array $record, ImportReport $report): ?array
    {
        $verseId = (int) ($record['verse_id'] ?? 0);
        if (! $this->verseExists($verseId)) {
            $report->fail('translation:'.($record['id'] ?? '?'), "verse {$verseId} not imported");

            return null;
        }

        $footNotes = [];
        foreach ((array) ($record['foot_notes'] ?? []) as $key => $note) {
            if (is_array($note) && isset($note['id'], $note['text'])) {
                $footNotes[(string) $note['id']] = (string) $note['text'];
            } elseif (is_string($note)) {
                $footNotes[(string) $key] = $note;
            }
        }

        return [
            'resource_id' => $resourceId,
            'verse_id' => $verseId,
            'qf_id' => isset($record['id']) ? (int) $record['id'] : null,
            'text' => (string) ($record['text'] ?? ''),
            'foot_notes' => $footNotes === [] ? null : json_encode($footNotes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];
    }
}
