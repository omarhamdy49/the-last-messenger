<?php

declare(strict_types=1);

/**
 * Repair existing curated collection DB rows from production JSON.
 *
 * Use after scripts/fix_curated_sunnah_collection.php. This updates existing
 * rows by source_file, avoiding the importer's collision-suffix behavior for
 * historical rows whose text_hash changed.
 *
 * Usage:
 *   php scripts/repair_curated_db_from_source.php --collection=nawawi40
 */

use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$collection = null;
$sourceRoot = dirname(__DIR__, 2).'/prophet-data-generator/hadith/production';

foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--collection=')) {
        $collection = substr($arg, strlen('--collection='));
    }
    if (str_starts_with($arg, '--source=')) {
        $sourceRoot = substr($arg, strlen('--source='));
    }
}

if (! is_string($collection) || $collection === '') {
    fwrite(STDERR, "Missing --collection.\n");
    exit(1);
}

$dir = rtrim($sourceRoot, '/')."/{$collection}";
if (! is_dir($dir)) {
    fwrite(STDERR, "Collection directory not found: {$dir}\n");
    exit(1);
}

function cleanText(?string $text): string
{
    return trim((string) preg_replace('/\s+/u', ' ', (string) $text));
}

$files = glob("{$dir}/hadith_*_final.json") ?: [];
sort($files);

$updated = 0;
$missing = [];

foreach ($files as $file) {
    $json = json_decode((string) file_get_contents($file), true);
    if (! is_array($json)) {
        continue;
    }

    $compactSource = $collection.'/'.basename($file);
    $hadithNumber = (string) ($json['metadata']['id_in_book'] ?? $json['collections'][0]['hadith_number'] ?? '');
    $sourceUrl = "https://sunnah.com/{$collection}:{$hadithNumber}";
    $texts = $json['hadith_text'] ?? [];
    $primaryText = (string) ($texts['ar'] ?? $texts['en'] ?? '');

    $row = DB::table('hadith_hadiths')
        ->where('source_file', $compactSource)
        ->where('hadith_number', $hadithNumber)
        ->first();

    if ($row === null) {
        $missing[] = "{$compactSource} #{$hadithNumber}";

        continue;
    }

    DB::transaction(function () use ($row, $texts, $primaryText, $sourceUrl): void {
        DB::table('hadith_hadiths')
            ->where('id', $row->id)
            ->update([
                'source_url' => $sourceUrl,
                'verification_status' => 'source_verified',
                'uncertainty_notes' => null,
                'source_verification_method' => 'sunnah_web',
                'source_verified_at' => now(),
                'text_hash' => hash('sha256', trim($primaryText)),
                'updated_at' => now(),
            ]);

        foreach (['ar', 'ar_notashkeel', 'en'] as $language) {
            if (empty($texts[$language])) {
                continue;
            }

            DB::table('hadith_translations')->updateOrInsert(
                [
                    'hadith_id' => $row->id,
                    'language_code' => $language,
                ],
                [
                    'text' => cleanText((string) $texts[$language]),
                    'source_url' => $sourceUrl,
                    'verified' => true,
                    'is_primary' => $language === 'ar',
                    'updated_at' => now(),
                ]
            );
        }
    });

    $updated++;
}

echo "{$collection}: repaired {$updated} DB row(s)";
if ($missing !== []) {
    echo ', missing '.count($missing);
}
echo "\n";

foreach ($missing as $entry) {
    echo "MISSING {$entry}\n";
}

exit($missing === [] ? 0 : 1);
