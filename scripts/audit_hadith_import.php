<?php

declare(strict_types=1);

/**
 * Hadith import reconciliation audit.
 *
 * Compares every hadith_*_final.json source file under the production data
 * directory against hadith_hadiths rows (matched on the source_file column,
 * which stores "<collection_dir>/<filename>"). Files without a DB row are
 * re-run through the real import parser (HadithJsonParser + HadithImportMapper)
 * to resolve the exact (collection, book, hadith_number) the importer would
 * target, then classified:
 *
 *   invalid_json     — file cannot be parsed by the import pipeline
 *   dup_identical    — owning row exists and its Arabic text is identical
 *                      (normalized) to this file: correct dedup skip
 *   conflict_shorter — owning row exists with DIFFERENT text, and this file's
 *                      Arabic text is <= the row's text (fragment/addendum
 *                      lost the (book, hadith_number) slot): correct skip
 *   conflict_longer  — owning row exists with DIFFERENT text and this file's
 *                      text is LONGER than the row's (main matn was
 *                      overwritten by a fragment): candidate for re-import
 *   import_gap       — no owning row at all: safe to import
 *
 * Usage: php scripts/audit_hadith_import.php [--source=/path/to/production]
 *
 * Read-only: performs no writes to hadith tables. Writes machine-readable
 * results to storage/app/hadith_reconcile/.
 */

use App\Domain\Hadith\Importers\HadithImportMapper;
use App\Domain\Hadith\Importers\HadithJsonParser;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$sourceRoot = '/Volumes/Data/projects/the-last-massenger/prophet-data-generator/hadith/production';
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--source=')) {
        $sourceRoot = substr($arg, strlen('--source='));
    }
}

if (! is_dir($sourceRoot)) {
    fwrite(STDERR, "Source directory not found: {$sourceRoot}\n");
    exit(1);
}

$parser = $app->make(HadithJsonParser::class);
$mapper = $app->make(HadithImportMapper::class);

$normalize = fn (string $text): string => trim((string) preg_replace('/\s+/u', ' ', $text));

$outDir = storage_path('app/hadith_reconcile');
if (! is_dir($outDir)) {
    mkdir($outDir, 0775, true);
}

$report = [];
$reimportCandidates = [];
$importGaps = [];

$dirs = array_values(array_filter(
    scandir($sourceRoot),
    fn (string $d): bool => $d[0] !== '.' && is_dir("{$sourceRoot}/{$d}")
));

foreach ($dirs as $dir) {
    $files = glob("{$sourceRoot}/{$dir}/hadith_*_final.json") ?: [];
    $fileNames = array_map('basename', $files);
    sort($fileNames);

    // DB rows whose source_file lives in this dir.
    $dbFiles = DB::table('hadith_hadiths')
        ->where('source_file', 'like', "{$dir}/%")
        ->pluck('source_file')
        ->map(fn (string $sf): string => basename($sf))
        ->flip()
        ->all();

    $dbRowCount = count($dbFiles);

    $missing = array_values(array_filter($fileNames, fn (string $f): bool => ! isset($dbFiles[$f])));

    $stats = [
        'dir' => $dir,
        'collection' => null,
        'source_files' => count($fileNames),
        'db_rows' => $dbRowCount,
        'missing' => count($missing),
        'invalid_json' => 0,
        'dup_identical' => 0,
        'conflict_shorter' => 0,
        'conflict_longer' => 0,
        'import_gap' => 0,
        'detail' => [],
    ];

    foreach ($missing as $fileName) {
        $path = "{$sourceRoot}/{$dir}/{$fileName}";

        try {
            $transformed = $parser->parse($path);
            $payload = $mapper->map($transformed, $parser->detectedFormat());
            $record = $payload['records'][0] ?? null;
            if ($record === null) {
                throw new RuntimeException('parser produced no record');
            }
        } catch (Throwable $e) {
            $stats['invalid_json']++;
            $stats['detail'][] = "{$fileName}: PARSE FAILURE — {$e->getMessage()}";

            continue;
        }

        $collectionName = $record['collection']['name_en'] ?? null;
        $bookTitleEn = $record['book']['title_en'] ?? null;
        $hadithNumber = (string) ($record['hadith']['hadith_number'] ?? '');
        $stats['collection'] ??= $collectionName;

        $fileArabic = '';
        foreach ($record['translations'] as $translation) {
            if (($translation['language_code'] ?? null) === 'ar') {
                $fileArabic = $normalize((string) ($translation['text'] ?? ''));
                break;
            }
        }

        // Resolve the row the importer's updateOrCreate would target:
        // book matched on (collection name_en, book title_en), hadith_number exact.
        $owner = DB::table('hadith_hadiths as h')
            ->join('hadith_books as b', 'b.id', '=', 'h.book_id')
            ->join('hadith_collections as c', 'c.id', '=', 'b.collection_id')
            ->where('c.name_en', $collectionName)
            ->where('b.title_en', $bookTitleEn)
            ->where('h.hadith_number', $hadithNumber)
            ->select(['h.id', 'h.source_file'])
            ->first();

        if ($owner === null) {
            $stats['import_gap']++;
            $stats['detail'][] = "{$fileName}: IMPORT GAP — no row for [{$collectionName} / {$bookTitleEn} / #{$hadithNumber}]";
            $importGaps[] = $path;

            continue;
        }

        $ownerArabic = $normalize((string) DB::table('hadith_translations')
            ->where('hadith_id', $owner->id)
            ->where('language_code', 'ar')
            ->value('text'));

        if ($fileArabic === $ownerArabic) {
            $stats['dup_identical']++;
            $stats['detail'][] = "{$fileName}: identical text — #{$hadithNumber} owned by {$owner->source_file} (hadith id {$owner->id})";

            continue;
        }

        if (mb_strlen($fileArabic) > mb_strlen($ownerArabic)) {
            $stats['conflict_longer']++;
            $stats['detail'][] = sprintf(
                '%s: CONFLICT (file longer %d > %d chars) — #%s owned by %s (hadith id %d)',
                $fileName,
                mb_strlen($fileArabic),
                mb_strlen($ownerArabic),
                $hadithNumber,
                $owner->source_file,
                $owner->id
            );
            $reimportCandidates[] = [
                'path' => $path,
                'hadith_id' => $owner->id,
                'number' => $hadithNumber,
                'collection' => $collectionName,
                'file_len' => mb_strlen($fileArabic),
                'db_len' => mb_strlen($ownerArabic),
            ];

            continue;
        }

        $stats['conflict_shorter']++;
        $stats['detail'][] = sprintf(
            '%s: conflict, file shorter/equal (%d <= %d chars) — #%s owned by %s (hadith id %d)',
            $fileName,
            mb_strlen($fileArabic),
            mb_strlen($ownerArabic),
            $hadithNumber,
            $owner->source_file,
            $owner->id
        );
    }

    $report[] = $stats;
}

// ---- Output -----------------------------------------------------------------

$fmt = "%-22s %8s %8s %8s %8s %9s %10s %10s %8s\n";
printf($fmt, 'dir', 'files', 'db_rows', 'missing', 'ident', 'shorter', 'LONGER', 'invalid', 'GAP');
printf("%s\n", str_repeat('-', 100));

$totals = array_fill_keys(['source_files', 'db_rows', 'missing', 'dup_identical', 'conflict_shorter', 'conflict_longer', 'invalid_json', 'import_gap'], 0);
foreach ($report as $s) {
    printf(
        $fmt,
        $s['dir'],
        $s['source_files'],
        $s['db_rows'],
        $s['missing'],
        $s['dup_identical'],
        $s['conflict_shorter'],
        $s['conflict_longer'],
        $s['invalid_json'],
        $s['import_gap']
    );
    foreach ($totals as $k => $v) {
        $totals[$k] += $s[$k];
    }
}
printf("%s\n", str_repeat('-', 100));
printf(
    $fmt,
    'TOTAL',
    $totals['source_files'],
    $totals['db_rows'],
    $totals['missing'],
    $totals['dup_identical'],
    $totals['conflict_shorter'],
    $totals['conflict_longer'],
    $totals['invalid_json'],
    $totals['import_gap']
);

file_put_contents(
    "{$outDir}/audit_detail.json",
    json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
);
file_put_contents(
    "{$outDir}/reimport_candidates.json",
    json_encode($reimportCandidates, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
);
file_put_contents(
    "{$outDir}/import_gaps.json",
    json_encode($importGaps, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
);

echo "\nDetail:    {$outDir}/audit_detail.json\n";
echo 'Re-import candidates (main text lost to fragment): '.count($reimportCandidates)." -> {$outDir}/reimport_candidates.json\n";
echo 'True import gaps: '.count($importGaps)." -> {$outDir}/import_gaps.json\n";
