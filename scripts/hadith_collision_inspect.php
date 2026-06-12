<?php

declare(strict_types=1);

/**
 * Deep inspection of collision_diff_text entries from hadith_import_audit.php.
 *
 * For every missing source file whose (book, hadith_number) key is occupied by a
 * different-text winner, compares:
 *   - the sunnah.com source URL claimed by the missing file vs the winner row
 *   - Arabic text similarity (similar_text percentage on the sanitized ar text)
 *
 * Helps decide whether the losers are (a) text variants of the same canonical
 * reference (skip is correct) or (b) genuinely distinct narrations lost to a
 * numbering collision (needs manual resolution).
 *
 * Usage: php scripts/hadith_collision_inspect.php
 * Read-only: performs no database writes.
 */

use App\Services\Json\HadithJsonTransformer;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$sourceRoot = '/Volumes/Data/projects/the-last-massenger/prophet-data-generator/hadith/production';
$auditPath = storage_path('app/hadith_audit_missing.json');

$details = json_decode((string) file_get_contents($auditPath), true);
$transformer = $app->make(HadithJsonTransformer::class);

$buckets = [
    'same_url_high_similarity' => 0,   // same sunnah ref, >=90% similar text — variant of same hadith
    'same_url_low_similarity' => 0,   // same sunnah ref, text diverges — generator data quality issue
    'diff_url_high_similarity' => 0,
    'diff_url_low_similarity' => 0,   // likely a genuinely distinct narration that was lost
];

$rows = [];

foreach ($details as $detail) {
    if ($detail['reason'] !== 'collision_diff_text') {
        continue;
    }

    $filePath = $sourceRoot.'/'.$detail['file'];
    $json = json_decode((string) file_get_contents($filePath), true);
    $transformed = $transformer->transform($json);
    $record = $transformed['records'][0];

    $fileArText = '';
    foreach ($record['translations'] as $translation) {
        if (($translation['language_code'] ?? '') === 'ar') {
            $fileArText = (string) ($translation['text'] ?? '');
            break;
        }
    }
    $fileUrl = $record['hadith']['source_url'] ?? null;

    $winner = DB::table('hadith_hadiths')
        ->where('id', $detail['winner_id'])
        ->select(['id', 'source_url', 'source_file'])
        ->first();

    $winnerArText = (string) DB::table('hadith_translations')
        ->where('hadith_id', $detail['winner_id'])
        ->where('language_code', 'ar')
        ->value('text');

    similar_text(
        mb_substr($fileArText, 0, 1500),
        mb_substr($winnerArText, 0, 1500),
        $percent
    );

    $sameUrl = $fileUrl !== null && $winner->source_url !== null && $fileUrl === $winner->source_url;
    $high = $percent >= 90.0;

    $bucket = ($sameUrl ? 'same_url' : 'diff_url').'_'.($high ? 'high' : 'low').'_similarity';
    $buckets[$bucket]++;

    $rows[] = [
        'file' => $detail['file'],
        'winner_file' => $winner->source_file,
        'number' => $detail['hadith_number'],
        'file_url' => $fileUrl,
        'winner_url' => $winner->source_url,
        'similarity' => round($percent, 1),
        'bucket' => $bucket,
    ];
}

usort($rows, fn (array $a, array $b): int => $a['similarity'] <=> $b['similarity']);

echo "Bucket summary:\n";
foreach ($buckets as $bucket => $count) {
    echo "  {$bucket}: {$count}\n";
}

echo "\nLowest-similarity 25 cases:\n";
foreach (array_slice($rows, 0, 25) as $row) {
    echo "{$row['similarity']}%\t{$row['bucket']}\t{$row['file']}\tvs\t{$row['winner_file']}\t#{$row['number']}\n";
    echo "\tfile_url:   {$row['file_url']}\n\twinner_url: {$row['winner_url']}\n";
}

$outputPath = storage_path('app/hadith_collision_inspection.json');
file_put_contents($outputPath, json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
echo "\nDetail written to: {$outputPath}\n";
