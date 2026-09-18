<?php

declare(strict_types=1);

namespace App\Domain\Quran\Import\QuranFoundation\Sync\Handlers;

use App\Domain\Quran\DTOs\ImportReport;
use App\Domain\Quran\Enums\CharType;
use App\Domain\Quran\Enums\SyncResourceGroup;
use App\Domain\Quran\Import\QuranFoundation\Mappers\StructureMapper;
use App\Domain\Quran\Import\QuranFoundation\Sync\SyncGroupHandler;
use App\Domain\Quran\Repositories\ImportRepositoryInterface;
use App\Domain\Quran\Repositories\SyncRepositoryInterface;
use App\Domain\Quran\Services\QuranCacheKeys;
use Illuminate\Support\Facades\DB;

/**
 * mushafs → quran_mushafs (descriptive columns only; editor flags are kept),
 * quran_mushaf_pages and quran_mushaf_words. Word rows must reference imported
 * words; unknown word ids are itemised, never inserted.
 */
final class MushafsHandler implements SyncGroupHandler
{
    private const PAGE_COLUMNS = ['id', 'first_verse_id', 'last_verse_id', 'first_word_id', 'last_word_id', 'verses_count', 'verse_mapping'];

    private const WORD_COLUMNS = ['id', 'verse_id', 'text', 'char_type', 'page_number', 'line_number', 'position_in_verse', 'position_in_line', 'position_in_page', 'css_class', 'css_style'];

    /** @var array<int, true>|null */
    private ?array $wordIds = null;

    public function __construct(
        private readonly ImportRepositoryInterface $rows,
        private readonly SyncRepositoryInterface $sync,
    ) {}

    public function group(): SyncResourceGroup
    {
        return SyncResourceGroup::MUSHAFS;
    }

    public function replace(int $qfResourceId, array $records, ImportReport $report): void
    {
        $report->expected += count($records);
        [$mushaf, $pages, $words] = $this->split($qfResourceId, $records, $report);

        if ($mushaf !== null) {
            $report->add($this->rows->upsert('quran_mushafs', [$mushaf], ['id'],
                ['name', 'description', 'pages_count', 'lines_per_page', 'default_font_name', 'mapping_mode', 'qirat_name']));
        } elseif (! DB::table('quran_mushafs')->where('id', $qfResourceId)->exists()) {
            $report->fail("mushafs:{$qfResourceId}", 'snapshot has no mushaf record and the mushaf is unknown');

            return;
        }

        $report->add($this->rows->replaceForOwner('quran_mushaf_pages', 'mushaf_id', $qfResourceId, $pages, ['mushaf_id', 'page_number'], self::PAGE_COLUMNS));
        $report->add($this->rows->replaceForOwner('quran_mushaf_words', 'mushaf_id', $qfResourceId, $words, ['mushaf_id', 'word_id'], self::WORD_COLUMNS));

        $this->sync->touchMushaf($qfResourceId);
        QuranCacheKeys::flushMushafs();
    }

    public function upsertRows(int $qfResourceId, string $recordType, array $records, ImportReport $report): void
    {
        foreach ($records as &$record) {
            $record['record_type'] ??= $recordType;
        }
        unset($record);

        [$mushaf, $pages, $words] = $this->split($qfResourceId, $records, $report);

        if ($mushaf !== null) {
            $report->add($this->rows->upsert('quran_mushafs', [$mushaf], ['id'],
                ['name', 'description', 'pages_count', 'lines_per_page', 'default_font_name', 'mapping_mode', 'qirat_name']));
        }
        if ($pages !== []) {
            $report->add($this->rows->upsert('quran_mushaf_pages', $pages, ['mushaf_id', 'page_number'], self::PAGE_COLUMNS));
        }
        if ($words !== []) {
            $report->add($this->rows->upsert('quran_mushaf_words', $words, ['mushaf_id', 'word_id'], self::WORD_COLUMNS));
        }

        $this->sync->touchMushaf($qfResourceId);
        QuranCacheKeys::flushMushafs();
    }

    public function deleteRow(int $qfResourceId, string $recordType, string $recordKey, ImportReport $report): void
    {
        $table = match ($recordType) {
            'mushaf_page' => 'quran_mushaf_pages',
            'mushaf_word' => 'quran_mushaf_words',
            default => null,
        };

        if ($table === null) {
            $report->fail("mushafs:{$qfResourceId}:{$recordType}", 'record type not handled');

            return;
        }

        $report->deleted += DB::table($table)->where('mushaf_id', $qfResourceId)->where('id', (int) $recordKey)->delete();
        QuranCacheKeys::flushMushafs();
    }

    public function deactivate(int $qfResourceId, ?string $reason, ImportReport $report): void
    {
        $this->sync->deactivateMushaf($qfResourceId, $reason);
        $report->note("mushafs:{$qfResourceId} deactivated".($reason !== null ? " ({$reason})" : ''));
    }

    public function touch(int $qfResourceId): void
    {
        DB::table('quran_mushafs')->where('id', $qfResourceId)->update(['last_synced_at' => now()]);
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @return array{0: array<string, mixed>|null, 1: list<array<string, mixed>>, 2: list<array<string, mixed>>}
     */
    private function split(int $mushafId, array $records, ImportReport $report): array
    {
        $this->wordIds ??= array_fill_keys(DB::table('quran_words')->pluck('id')->map(static fn (mixed $v): int => (int) $v)->all(), true);

        $mushaf = null;
        $pages = [];
        $words = [];
        $unknownWords = 0;

        foreach ($records as $record) {
            switch ($record['record_type'] ?? null) {
                case 'mushaf':
                    $mushaf = [
                        'id' => $mushafId,
                        'name' => mb_substr((string) ($record['name'] ?? "Mushaf {$mushafId}"), 0, 100),
                        'description' => isset($record['description']) ? mb_substr((string) $record['description'], 0, 255) : null,
                        'pages_count' => (int) ($record['pages_count'] ?? 604),
                        'lines_per_page' => (int) ($record['lines_per_page'] ?? 15),
                        'default_font_name' => isset($record['default_font_name']) ? (string) $record['default_font_name'] : null,
                        'mapping_mode' => isset($record['mapping_mode']) ? (string) $record['mapping_mode'] : null,
                        'qirat_name' => isset($record['qirat']['name']) ? (string) $record['qirat']['name'] : null,
                    ];
                    break;

                case 'mushaf_page':
                    $pages[] = StructureMapper::page($mushafId, $record);
                    break;

                case 'mushaf_word':
                    $wordId = (int) ($record['word_id'] ?? 0);
                    if (! isset($this->wordIds[$wordId])) {
                        $unknownWords++;
                        break;
                    }
                    $words[] = [
                        'id' => (int) $record['id'],
                        'mushaf_id' => $mushafId,
                        'word_id' => $wordId,
                        'verse_id' => (int) ($record['verse_id'] ?? 0),
                        'text' => isset($record['text']) ? mb_substr((string) $record['text'], 0, 64) : null,
                        'char_type' => CharType::fromExternal($record['char_type_name'] ?? null)->value,
                        'page_number' => (int) ($record['page_number'] ?? 0),
                        'line_number' => (int) ($record['line_number'] ?? 0),
                        'position_in_verse' => (int) ($record['position_in_verse'] ?? 0),
                        'position_in_line' => (int) ($record['position_in_line'] ?? 0),
                        'position_in_page' => (int) ($record['position_in_page'] ?? 0),
                        'css_class' => isset($record['css_class']) ? mb_substr((string) $record['css_class'], 0, 64) : null,
                        'css_style' => isset($record['css_style']) ? mb_substr((string) $record['css_style'], 0, 255) : null,
                    ];
                    break;

                case 'font_asset':
                    // Fonts are downloaded by quran:qf:download-assets, not synced.
                    break;

                default:
                    $report->fail("mushafs:{$mushafId}:".($record['record_type'] ?? '(none)'), 'record type not handled');
            }
        }

        if ($unknownWords > 0) {
            $report->fail("mushafs:{$mushafId}:words", "{$unknownWords} word(s) reference words that are not imported — run quran:qf:import-words first");
        }

        return [$mushaf, $pages, $words];
    }
}
