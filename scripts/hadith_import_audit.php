<?php

declare(strict_types=1);

/**
 * Hadith import reconciliation audit.
 *
 * Compares every hadith_*_final.json file in the prophet-data-generator
 * production directory against hadith_hadiths rows (matched on the
 * source_file column written by HadithImportPersister::compactSourceFile).
 *
 * Each source file that has no DB row is classified as:
 *   - invalid_json            file cannot be JSON-decoded
 *   - unsupported_structure   JsonDataTransformer rejects/throws on the structure
 *   - collision_same_text     another file with the same (book, hadith_number) won the
 *                             updateOrCreate upsert AND carries identical Arabic text
 *                             (true duplicate — correct to skip)
 *   - collision_diff_text     same (book, hadith_number) key but different Arabic text
 *                             (numbering collision in the source data — importing would
 *                             overwrite the existing row, so it must NOT be blind-imported)
 *   - importable_missing      no DB row and no key collision — safe to import
 *
 * Usage: php scripts/hadith_import_audit.php
 * Output: table on stdout + storage/app/hadith_audit_missing.json (detail per file)
 *
 * Read-only: performs no database writes.
 */

use App\Services\Json\HadithJsonTransformer;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$sourceRoot = '/Volumes/Data/projects/the-last-massenger/prophet-data-generator/hadith/production';

if (! is_dir($sourceRoot)) {
    fwrite(STDERR, "Source directory not found: {$sourceRoot}\n");
    exit(1);
}

$transformer = $app->make(HadithJsonTransformer::class);

// ---------------------------------------------------------------------------
// Load the full DB picture into memory (50k rows — fine for a one-off audit).
// ---------------------------------------------------------------------------
$dbRows = DB::table('hadith_hadiths as h')
    ->join('hadith_books as b', 'b.id', '=', 'h.book_id')
    ->select([
        'h.id',
        'h.source_file',
        'h.hadith_number',
        'h.text_hash',
        'h.book_id',
        'b.title_ar as book_title_ar',
        'b.title_en as book_title_en',
    ])
    ->get();

$byFile = [];          // "dir/file.json" => row
$byKey = [];           // "dir|book_title_ar|hadith_number" => row(s)
foreach ($dbRows as $row) {
    $byFile[$row->source_file] = $row;
    $dir = explode('/', (string) $row->source_file, 2)[0];
    $key = $dir.'|'.$row->book_title_ar.'|'.$row->hadith_number;
    $byKey[$key][] = $row;
}

// ---------------------------------------------------------------------------
// Walk source directories.
// ---------------------------------------------------------------------------
$collections = array_values(array_filter(
    scandir($sourceRoot),
    fn (string $entry): bool => $entry[0] !== '.' && is_dir($sourceRoot.'/'.$entry)
));
sort($collections);

$report = [];
$missingDetail = [];

foreach ($collections as $dir) {
    $files = glob($sourceRoot.'/'.$dir.'/hadith_*_final.json');
    sort($files);

    $stats = [
        'source_files' => count($files),
        'db_rows' => 0,
        'matched' => 0,
        'invalid_json' => 0,
        'unsupported_structure' => 0,
        'collision_same_text' => 0,
        'collision_diff_text' => 0,
        'importable_missing' => 0,
    ];

    $stats['db_rows'] = count(array_filter(
        array_keys($byFile),
        fn (string $sourceFile): bool => str_starts_with($sourceFile, $dir.'/')
    ));

    foreach ($files as $filePath) {
        $compact = $dir.'/'.basename($filePath);

        if (isset($byFile[$compact])) {
            $stats['matched']++;

            continue;
        }

        // --- Missing from DB: diagnose why -----------------------------------
        $raw = file_get_contents($filePath);
        $json = json_decode((string) $raw, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $stats['invalid_json']++;
            $missingDetail[] = ['file' => $compact, 'reason' => 'invalid_json', 'error' => json_last_error_msg()];

            continue;
        }

        try {
            $transformed = $transformer->transform($json);
        } catch (Throwable $e) {
            $stats['unsupported_structure']++;
            $missingDetail[] = ['file' => $compact, 'reason' => 'unsupported_structure', 'error' => $e->getMessage()];

            continue;
        }

        $record = $transformed['records'][0] ?? null;
        if ($record === null) {
            $stats['unsupported_structure']++;
            $missingDetail[] = ['file' => $compact, 'reason' => 'unsupported_structure', 'error' => 'transformer produced no records'];

            continue;
        }

        $bookTitleAr = $record['book']['title_ar'] ?? '';
        $hadithNumber = (string) ($record['hadith']['hadith_number'] ?? '');

        // text_hash the persister would compute: sha256 of first non-empty translation text
        $candidateHash = null;
        foreach ($record['translations'] ?? [] as $translation) {
            $text = $translation['text'] ?? $translation['matn'] ?? null;
            if (is_string($text) && trim($text) !== '') {
                $candidateHash = hash('sha256', trim($text));
                break;
            }
        }

        $key = $dir.'|'.$bookTitleAr.'|'.$hadithNumber;
        $collisions = $byKey[$key] ?? [];

        if ($collisions !== []) {
            $winner = $collisions[0];
            $sameText = $candidateHash !== null && $winner->text_hash === $candidateHash;
            $reason = $sameText ? 'collision_same_text' : 'collision_diff_text';
            $stats[$reason]++;
            $missingDetail[] = [
                'file' => $compact,
                'reason' => $reason,
                'book_title_ar' => $bookTitleAr,
                'hadith_number' => $hadithNumber,
                'winner_file' => $winner->source_file,
                'winner_id' => $winner->id,
                'text_hash_file' => $candidateHash,
                'text_hash_db' => $winner->text_hash,
            ];

            continue;
        }

        $stats['importable_missing']++;
        $missingDetail[] = [
            'file' => $compact,
            'reason' => 'importable_missing',
            'path' => $filePath,
            'book_title_ar' => $bookTitleAr,
            'hadith_number' => $hadithNumber,
            'text_hash' => $candidateHash,
        ];
    }

    $report[$dir] = $stats;
}

// ---------------------------------------------------------------------------
// Detect importable files that would collide with EACH OTHER on import.
// ---------------------------------------------------------------------------
$importableKeys = [];
foreach ($missingDetail as $i => $detail) {
    if ($detail['reason'] !== 'importable_missing') {
        continue;
    }
    $dir = explode('/', $detail['file'], 2)[0];
    $key = $dir.'|'.$detail['book_title_ar'].'|'.$detail['hadith_number'];
    $importableKeys[$key][] = $i;
}
foreach ($importableKeys as $key => $indexes) {
    if (count($indexes) > 1) {
        foreach ($indexes as $i) {
            $missingDetail[$i]['intra_missing_collision'] = $key;
        }
    }
}

// ---------------------------------------------------------------------------
// Output.
// ---------------------------------------------------------------------------
$header = ['collection', 'source_files', 'db_rows', 'matched', 'invalid_json', 'unsupported', 'collision_same_text', 'collision_diff_text', 'importable_missing'];
echo implode("\t", $header)."\n";

$totals = array_fill_keys(array_slice($header, 1), 0);
foreach ($report as $dir => $stats) {
    echo implode("\t", [
        $dir,
        $stats['source_files'],
        $stats['db_rows'],
        $stats['matched'],
        $stats['invalid_json'],
        $stats['unsupported_structure'],
        $stats['collision_same_text'],
        $stats['collision_diff_text'],
        $stats['importable_missing'],
    ])."\n";

    $totals['source_files'] += $stats['source_files'];
    $totals['db_rows'] += $stats['db_rows'];
    $totals['matched'] += $stats['matched'];
    $totals['invalid_json'] += $stats['invalid_json'];
    $totals['unsupported'] += $stats['unsupported_structure'];
    $totals['collision_same_text'] += $stats['collision_same_text'];
    $totals['collision_diff_text'] += $stats['collision_diff_text'];
    $totals['importable_missing'] += $stats['importable_missing'];
}
echo implode("\t", array_merge(['TOTAL'], array_values($totals)))."\n";

$outputPath = storage_path('app/hadith_audit_missing.json');
file_put_contents($outputPath, json_encode($missingDetail, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
echo "\nDetail written to: {$outputPath}\n";
