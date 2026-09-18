<?php

declare(strict_types=1);

namespace App\Domain\Quran\Import\QuranFoundation\Sync\Handlers;

use App\Domain\Quran\DTOs\ImportReport;
use App\Domain\Quran\Enums\ResourceKind;
use App\Domain\Quran\Enums\SyncResourceGroup;
use App\Domain\Quran\Models\WordGloss;
use App\Domain\Quran\Repositories\ImportRepositoryInterface;
use App\Domain\Quran\Repositories\SyncRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * word_by_word_translations / word_by_word_transliterations → quran_word_glosses.
 * These resources are not in any QF catalogue, so the snapshot's language fills the
 * resource's `language_iso` when it is unknown.
 */
final class WordGlossesHandler extends ResourceRowsHandler
{
    /** @var array<string, string>|null lower-case language name => iso */
    private ?array $isoByName = null;

    public function __construct(
        ImportRepositoryInterface $rows,
        SyncRepositoryInterface $sync,
        private readonly SyncResourceGroup $group,
    ) {
        parent::__construct($rows, $sync);
    }

    public function group(): SyncResourceGroup
    {
        return $this->group;
    }

    protected function kind(): ResourceKind
    {
        return $this->group === SyncResourceGroup::WORD_BY_WORD_TRANSLATIONS
            ? ResourceKind::WORD_TRANSLATION
            : ResourceKind::WORD_TRANSLITERATION;
    }

    protected function table(): string
    {
        return 'quran_word_glosses';
    }

    protected function uniqueBy(): array
    {
        return ['word_id', 'resource_id'];
    }

    protected function updateColumns(): array
    {
        return ['kind', 'language_iso', 'text', 'priority', 'qf_id'];
    }

    protected function recordTypes(): array
    {
        return ['word_translation', 'word_transliteration'];
    }

    protected function map(int $resourceId, array $record, ImportReport $report): ?array
    {
        $wordId = (int) ($record['word_id'] ?? 0);
        if (! $this->wordExists($wordId)) {
            $report->fail('gloss:'.($record['id'] ?? '?'), "word {$wordId} not imported");

            return null;
        }

        $text = trim((string) ($record['text'] ?? ''));
        if ($text === '') {
            return null;
        }

        return [
            'word_id' => $wordId,
            'resource_id' => $resourceId,
            'kind' => $this->kind() === ResourceKind::WORD_TRANSLATION ? WordGloss::KIND_TRANSLATION : WordGloss::KIND_TRANSLITERATION,
            'language_iso' => $this->iso((string) ($record['language_name'] ?? '')) ?? 'en',
            'text' => mb_substr($text, 0, 500),
            'priority' => isset($record['priority']) ? (int) $record['priority'] : null,
            'qf_id' => isset($record['id']) ? (int) $record['id'] : null,
        ];
    }

    protected function afterWrite(int $resourceId, array $records): void
    {
        $language = $this->iso((string) ($records[0]['language_name'] ?? ''));
        if ($language !== null) {
            DB::table('quran_resources')->where('id', $resourceId)->whereNull('language_iso')->update(['language_iso' => $language]);
        }
    }

    private function iso(string $languageName): ?string
    {
        $this->isoByName ??= DB::table('quran_languages')->pluck('iso_code', 'name')
            ->mapWithKeys(static fn (string $iso, string $name): array => [strtolower($name) => $iso])->all();

        return $this->isoByName[strtolower(trim($languageName))] ?? null;
    }
}
