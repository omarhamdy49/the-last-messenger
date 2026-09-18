<?php

declare(strict_types=1);

namespace App\Domain\Quran\Import\QuranFoundation\Importers;

use App\Domain\Quran\DTOs\ImportReport;
use App\Domain\Quran\Enums\Script;
use App\Domain\Quran\Import\QuranFoundation\Endpoints;
use App\Domain\Quran\Import\QuranFoundation\Exceptions\QfPayloadException;
use App\Domain\Quran\Import\QuranFoundation\Mappers\VerseMapper;
use App\Domain\Quran\Models\Chapter;
use App\Domain\Quran\Models\Verse;
use App\Domain\Quran\Repositories\ImportRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * Verses: axis numbers/metadata from `/verses/by_chapter/{n}` and the text of every
 * script from `/quran/verses/{script}` (whole Quran per call when the full range is
 * imported). Script columns of an editor-locked verse are never overwritten.
 */
final class VersesImporter
{
    private const META_COLUMNS = [
        'chapter_id', 'verse_number', 'verse_key', 'verse_index', 'juz_number', 'hizb_number', 'rub_el_hizb_number',
        'ruku_number', 'manzil_number', 'page_number', 'sajdah_type', 'sajdah_number', 'image_url', 'image_width',
    ];

    private const META_FIELDS = 'chapter_id,verse_index,sajdah_type,sajdah_number,image_url,image_width,ruku_number,manzil_number,page_number,juz_number,hizb_number,rub_el_hizb_number';

    public function __construct(
        private readonly Endpoints $qf,
        private readonly ImportRepositoryInterface $repository,
    ) {}

    /**
     * @param  list<Script>|null  $scripts  null = all
     */
    public function run(int $from = 1, int $to = Chapter::COUNT, ?array $scripts = null, bool $dryRun = false): ImportReport
    {
        $report = new ImportReport('verses');
        $scripts ??= Script::cases();
        $fullRange = $from === 1 && $to === Chapter::COUNT;

        $expectedByChapter = DB::table('quran_chapters')->whereBetween('id', [$from, $to])->pluck('verses_count', 'id')->all();
        if (count($expectedByChapter) !== $to - $from + 1) {
            $report->fail('chapters', 'chapters missing for the range — run quran:qf:import-structure first');

            return $report;
        }
        $report->expected = (int) array_sum($expectedByChapter);

        $scriptColumns = $this->scriptColumns($scripts, $from, $to, $fullRange, $report);

        for ($chapter = $from; $chapter <= $to; $chapter++) {
            $meta = $this->chapterMeta($chapter);

            if ($dryRun) {
                continue;
            }

            // Metadata is never editorial, so it always refreshes.
            $report->add($this->repository->upsert('quran_verses', $meta, ['id'], self::META_COLUMNS));

            $merged = [];
            foreach ($meta as $row) {
                $merged[] = $row + ($scriptColumns[$row['id']] ?? []);
            }
            $updateColumns = array_values(array_unique(array_merge(...array_map(
                static fn (array $columns): array => array_keys($columns),
                array_values($scriptColumns) ?: [[]],
            ))));

            if ($updateColumns !== []) {
                $counts = $this->repository->upsert('quran_verses', $merged, ['id'], $updateColumns, 'is_locked');
                // Rows were created by the metadata pass; only report what the script pass changed.
                $report->updated += $counts['updated'];
                $report->skippedLocked += $counts['skipped_locked'];
            }
        }

        if (! $dryRun) {
            $this->gates($report, $expectedByChapter, $scripts);
        }

        return $report;
    }

    /**
     * @param  list<Script>  $scripts
     * @return array<int, array<string, mixed>> verse id => script columns
     */
    private function scriptColumns(array $scripts, int $from, int $to, bool $fullRange, ImportReport $report): array
    {
        $columns = [];
        foreach ($scripts as $script) {
            $chapters = $fullRange ? [null] : range($from, $to);
            foreach ($chapters as $chapter) {
                $payload = $this->qf->scriptVerses($script, $chapter === null ? [] : ['chapter_number' => $chapter]);
                if (! isset($payload['verses']) || ! is_array($payload['verses'])) {
                    throw new QfPayloadException('quran/verses/'.$script->qfPath(), 'verses');
                }

                $received = 0;
                foreach ($payload['verses'] as $item) {
                    $mapped = VerseMapper::script($script, $item);
                    $columns[$mapped['id']] = ($columns[$mapped['id']] ?? []) + $mapped['columns'];
                    $received++;
                }

                if ($received === 0) {
                    $report->fail('script:'.$script->qfPath().($chapter !== null ? ":chapter:{$chapter}" : ''), 'QF returned no verses');
                }
            }
        }

        return $columns;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function chapterMeta(int $chapter): array
    {
        $rows = [];
        $page = 1;
        do {
            $payload = $this->qf->versesByChapter($chapter, ['per_page' => 50, 'page' => $page, 'fields' => self::META_FIELDS]);
            if (! isset($payload['verses']) || ! is_array($payload['verses'])) {
                throw new QfPayloadException("verses/by_chapter/{$chapter}", 'verses');
            }
            foreach ($payload['verses'] as $item) {
                $rows[] = VerseMapper::meta($item);
            }
            $next = $payload['pagination']['next_page'] ?? null;
            $page = is_numeric($next) ? (int) $next : 0;
        } while ($page > 0);

        return $rows;
    }

    /**
     * @param  array<int, int>  $expectedByChapter
     * @param  list<Script>  $scripts
     */
    private function gates(ImportReport $report, array $expectedByChapter, array $scripts): void
    {
        $chapterIds = array_keys($expectedByChapter);
        $stored = DB::table('quran_verses')->whereIn('chapter_id', $chapterIds)
            ->selectRaw('chapter_id, COUNT(*) as n')->groupBy('chapter_id')->pluck('n', 'chapter_id')->all();

        $mismatched = [];
        foreach ($expectedByChapter as $chapter => $expected) {
            if ((int) ($stored[$chapter] ?? 0) !== (int) $expected) {
                $mismatched[] = "{$chapter}: ".($stored[$chapter] ?? 0)."/{$expected}";
            }
        }
        $report->gate($mismatched === [], $mismatched === []
            ? 'verse count matches verses_count for '.count($expectedByChapter).' chapter(s)'
            : 'verse count mismatch in chapters '.implode(', ', $mismatched));

        if (count($expectedByChapter) === Chapter::COUNT) {
            $total = array_sum(array_map('intval', $stored));
            $report->gate($total === Verse::COUNT, "verses stored: {$total} of ".Verse::COUNT);
        }

        foreach ($scripts as $script) {
            $missing = DB::table('quran_verses')->whereIn('chapter_id', $chapterIds)->whereNull($script->value)->count();
            if (in_array($script, [Script::UTHMANI, Script::UTHMANI_SIMPLE, Script::CODE_V2], true)) {
                $report->gate($missing === 0, "{$script->value}: {$missing} verse(s) without text");
            } elseif ($missing > 0) {
                $report->note("{$script->value}: {$missing} verse(s) without text");
            }
        }
    }
}
