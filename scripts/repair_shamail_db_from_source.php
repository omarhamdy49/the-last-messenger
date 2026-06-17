<?php

declare(strict_types=1);

/**
 * Repair Ash-Shama'il Al-Muhammadiyah DB rows from corrected production JSON.
 *
 * Updates by source_file and creates missing rows if needed. The local
 * production directory remains "shamail_muhammadiyah"; Sunnah URLs and
 * references use the official Sunnah API/web slug "shamail".
 *
 * Usage:
 *   php scripts/repair_shamail_db_from_source.php
 */

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$sourceRoot = dirname(__DIR__, 2).'/prophet-data-generator/hadith/production/shamail_muhammadiyah';
foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--source=')) {
        $sourceRoot = substr($arg, strlen('--source='));
    }
}

if (! is_dir($sourceRoot)) {
    fwrite(STDERR, "Collection directory not found: {$sourceRoot}\n");
    exit(1);
}

function cleanShamailDbText(?string $text): string
{
    return trim((string) preg_replace('/\s+/u', ' ', (string) $text));
}

function textHashFromShamailJson(array $texts): string
{
    return hash('sha256', trim((string) ($texts['ar'] ?? $texts['en'] ?? '')));
}

function shamailRepairSourceSequence(string $file): int
{
    if (preg_match('/hadith_(\d+)_final\.json$/', basename($file), $match)) {
        return (int) $match[1];
    }

    return PHP_INT_MAX;
}

function shamailRepairSourceFiles(string $sourceRoot): array
{
    $files = glob(rtrim($sourceRoot, '/').'/hadith_*_final.json') ?: [];
    usort($files, static fn (string $a, string $b): int => shamailRepairSourceSequence($a) <=> shamailRepairSourceSequence($b));

    return $files;
}

$collectionPayload = [
    'name_en' => "Ash-Shama'il Al-Muhammadiyah",
    'name_ar' => 'الشمائل المحمدية',
    'slug_ar' => Str::slug('الشمائل المحمدية', '-', 'ar'),
    'slug_en' => Str::slug("Ash-Shama'il Al-Muhammadiyah"),
    'canonical_code' => 'shamail_muhammadiyah',
    'source_slug' => 'shamail',
    'updated_at' => now(),
];

$existingCollection = DB::table('hadith_collections')
    ->where('canonical_code', 'shamail_muhammadiyah')
    ->orWhere('canonical_code', 'shamail')
    ->orWhere('source_slug', 'shamail')
    ->orWhere('slug_en', $collectionPayload['slug_en'])
    ->orWhere('slug_ar', $collectionPayload['slug_ar'])
    ->first();
if ($existingCollection === null) {
    DB::table('hadith_collections')->insert($collectionPayload + ['created_at' => now()]);
} else {
    DB::table('hadith_collections')->where('id', $existingCollection->id)->update($collectionPayload);
}

$collection = DB::table('hadith_collections')
    ->where('canonical_code', 'shamail_muhammadiyah')
    ->orWhere('source_slug', 'shamail')
    ->orWhere('slug_ar', $collectionPayload['slug_ar'])
    ->first();
if ($collection === null) {
    fwrite(STDERR, "Could not resolve Ash-Shama'il Al-Muhammadiyah collection.\n");
    exit(1);
}

$files = shamailRepairSourceFiles($sourceRoot);
$sourceFiles = array_map(static fn (string $file): string => 'shamail_muhammadiyah/'.basename($file), $files);
$updated = 0;
$created = 0;
$invalid = [];
$missingCanonical = [];
$reservedHadithNumbers = [];

foreach ($files as $file) {
    $json = json_decode((string) file_get_contents($file), true);
    if (! is_array($json)) {
        $invalid[] = basename($file);

        continue;
    }

    $sourceFile = 'shamail_muhammadiyah/'.basename($file);
    $collectionRow = $json['collections'][0] ?? [];
    $hadithNumber = (string) ($collectionRow['hadith_number'] ?? '');
    $bookNumber = (string) ($collectionRow['book_number'] ?? '');
    if ($hadithNumber === '' || $bookNumber === '') {
        $missingCanonical[] = $sourceFile;

        continue;
    }

    $bookNameEn = cleanShamailDbText((string) ($collectionRow['book_name_en'] ?? ''));
    $bookNameAr = cleanShamailDbText((string) ($collectionRow['book_name_ar'] ?? ''));

    DB::table('hadith_books')->updateOrInsert(
        [
            'collection_id' => $collection->id,
            'book_number' => $bookNumber,
        ],
        [
            'title_ar' => $bookNameAr,
            'title_en' => $bookNameEn,
            'slug_ar' => Str::slug($bookNameAr, '-', 'ar'),
            'slug_en' => Str::slug($bookNameEn),
            'updated_at' => now(),
            'created_at' => now(),
        ]
    );

    $book = DB::table('hadith_books')
        ->where('collection_id', $collection->id)
        ->where('book_number', $bookNumber)
        ->orderBy('id')
        ->first();

    if ($book === null) {
        $missingCanonical[] = "{$sourceFile} (book)";

        continue;
    }

    $row = DB::table('hadith_hadiths')
        ->where('source_file', $sourceFile)
        ->orderBy('id')
        ->first();

    $dbHadithNumber = $hadithNumber;
    $reservedKey = "{$book->id}|{$dbHadithNumber}";
    if (isset($reservedHadithNumbers[$reservedKey])) {
        foreach (range('a', 'z') as $suffix) {
            $candidate = "{$hadithNumber} {$suffix}";
            $candidateKey = "{$book->id}|{$candidate}";
            if (! isset($reservedHadithNumbers[$candidateKey])) {
                $dbHadithNumber = $candidate;
                $reservedKey = $candidateKey;
                break;
            }
        }
    }
    $reservedHadithNumbers[$reservedKey] = true;

    $conflictExists = DB::table('hadith_hadiths')
        ->where('book_id', $book->id)
        ->where('hadith_number', $dbHadithNumber)
        ->when($row !== null, fn ($query) => $query->where('id', '<>', $row->id))
        ->exists();
    if ($conflictExists) {
        foreach (range('a', 'z') as $suffix) {
            $candidate = "{$hadithNumber} {$suffix}";
            $candidateExists = DB::table('hadith_hadiths')
                ->where('book_id', $book->id)
                ->where('hadith_number', $candidate)
                ->when($row !== null, fn ($query) => $query->where('id', '<>', $row->id))
                ->exists();
            if (! $candidateExists) {
                $dbHadithNumber = $candidate;
                break;
            }
        }
    }

    $texts = $json['hadith_text'] ?? [];
    $reference = $json['metadata']['sunnah_reference'] ?? [];
    $payload = [
        'book_id' => $book->id,
        'chapter_id' => null,
        'local_source_id' => (string) ($json['metadata']['id'] ?? shamailRepairSourceSequence($file)),
        'source_file' => $sourceFile,
        'source_url' => $collectionRow['source_url'] ?? $reference['url'] ?? null,
        'sunnah_urn_ar' => $reference['urn'] ?? null,
        'sunnah_urn_en' => $reference['urn_en'] ?? null,
        'hadith_number' => $dbHadithNumber,
        'verification_status' => 'source_verified',
        'source_verification_method' => 'source_verified',
        'source_verified_at' => now(),
        'uncertainty_notes' => null,
        'grading_status' => $json['grading']['status'] ?? null,
        'grading_agreement' => $json['grading']['agreement'] ?? null,
        'text_hash' => textHashFromShamailJson($texts),
        'updated_at' => now(),
    ];

    DB::transaction(function () use (&$row, &$created, &$updated, $payload, $texts, $collectionRow, $reference): void {
        if ($row === null) {
            $rowId = DB::table('hadith_hadiths')->insertGetId($payload + ['created_at' => now()]);
            $row = DB::table('hadith_hadiths')->where('id', $rowId)->first();
            $created++;
        } else {
            DB::table('hadith_hadiths')->where('id', $row->id)->update($payload);
            $updated++;
        }

        foreach (['ar', 'ar_notashkeel', 'en'] as $language) {
            if (empty($texts[$language])) {
                continue;
            }

            $urn = $language === 'en' ? ($reference['urn_en'] ?? null) : ($reference['urn'] ?? null);
            DB::table('hadith_translations')->updateOrInsert(
                [
                    'hadith_id' => $row->id,
                    'language_code' => $language,
                ],
                [
                    'text' => cleanShamailDbText((string) $texts[$language]),
                    'source_url' => $collectionRow['source_url'] ?? $reference['url'] ?? null,
                    'urn' => $urn,
                    'verified' => true,
                    'is_primary' => $language === 'ar',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    });
}

$deletedStale = DB::table('hadith_hadiths')
    ->where('source_file', 'like', 'shamail_muhammadiyah/%')
    ->whereNotIn('source_file', $sourceFiles)
    ->delete();

echo "shamail: updated {$updated} DB row(s), created {$created}, deleted_stale {$deletedStale}";
if ($invalid !== []) {
    echo ', invalid '.count($invalid);
}
if ($missingCanonical !== []) {
    echo ', missing_canonical '.count($missingCanonical);
}
echo "\n";

foreach (array_slice($invalid, 0, 40) as $entry) {
    echo "INVALID {$entry}\n";
}
foreach (array_slice($missingCanonical, 0, 40) as $entry) {
    echo "MISSING_CANONICAL {$entry}\n";
}

exit($invalid === [] && $missingCanonical === [] ? 0 : 1);
