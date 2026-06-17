<?php

declare(strict_types=1);

/**
 * Repair Sahih Muslim DB rows from corrected production JSON.
 *
 * Updates by source_file and creates missing rows if needed. This avoids the
 * historical book/reference collision behavior that made Muslim rows drift into
 * wrong book slots.
 *
 * Usage:
 *   php scripts/repair_muslim_db_from_source.php
 */

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$sourceRoot = dirname(__DIR__, 2).'/prophet-data-generator/hadith/production/muslim';
foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--source=')) {
        $sourceRoot = substr($arg, strlen('--source='));
    }
}

if (! is_dir($sourceRoot)) {
    fwrite(STDERR, "Collection directory not found: {$sourceRoot}\n");
    exit(1);
}

function cleanMuslimDbText(?string $text): string
{
    return trim((string) preg_replace('/\s+/u', ' ', (string) $text));
}

function textHashFromMuslimJson(array $texts): string
{
    return hash('sha256', trim((string) ($texts['ar'] ?? $texts['en'] ?? '')));
}

function muslimRepairSourceSequence(string $file): int
{
    if (preg_match('/hadith_(\d+)_final\.json$/', basename($file), $match)) {
        return (int) $match[1];
    }

    return PHP_INT_MAX;
}

function muslimRepairSourceFiles(string $sourceRoot): array
{
    $files = glob(rtrim($sourceRoot, '/').'/hadith_*_final.json') ?: [];
    usort($files, static fn (string $a, string $b): int => muslimRepairSourceSequence($a) <=> muslimRepairSourceSequence($b));

    return $files;
}

DB::table('hadith_collections')->updateOrInsert(
    ['name_en' => 'Sahih Muslim'],
    [
        'name_ar' => 'صحيح مسلم',
        'slug_ar' => Str::slug('صحيح مسلم', '-', 'ar'),
        'slug_en' => Str::slug('Sahih Muslim'),
        'canonical_code' => 'muslim',
        'source_slug' => 'muslim',
        'updated_at' => now(),
        'created_at' => now(),
    ]
);

$collection = DB::table('hadith_collections')->where('name_en', 'Sahih Muslim')->first();
if ($collection === null) {
    fwrite(STDERR, "Could not resolve Sahih Muslim collection.\n");
    exit(1);
}

$files = muslimRepairSourceFiles($sourceRoot);
$sourceFiles = array_map(static fn (string $file): string => 'muslim/'.basename($file), $files);

$updated = 0;
$created = 0;
$conflictsMoved = 0;
$invalid = [];
$missingCanonical = [];

foreach ($files as $file) {
    $json = json_decode((string) file_get_contents($file), true);
    if (! is_array($json)) {
        $invalid[] = basename($file);

        continue;
    }

    $sourceFile = 'muslim/'.basename($file);
    $collectionRow = $json['collections'][0] ?? [];
    $hadithNumber = (string) ($collectionRow['hadith_number'] ?? '');
    $bookNumber = (string) ($collectionRow['book_number'] ?? '');
    if ($hadithNumber === '' || $bookNumber === '') {
        $missingCanonical[] = $sourceFile;

        continue;
    }

    $bookNameEn = cleanMuslimDbText((string) ($collectionRow['book_name_en'] ?? ''));
    $bookNameAr = cleanMuslimDbText((string) ($collectionRow['book_name_ar'] ?? ''));

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

    $chapterId = null;
    $chapterNumber = $collectionRow['chapter_number'] ?? $json['chapter_info'] ?? null;
    if ($chapterNumber !== null && $chapterNumber !== '') {
        $chapterName = json_encode([
            'ar' => cleanMuslimDbText((string) ($collectionRow['chapter_name_ar'] ?? '')),
            'en' => cleanMuslimDbText((string) ($collectionRow['chapter_name_en'] ?? '')),
        ], JSON_UNESCAPED_UNICODE);

        DB::table('hadith_chapters')->updateOrInsert(
            [
                'book_id' => $book->id,
                'chapter_number' => (string) $chapterNumber,
            ],
            [
                'name' => $chapterName,
                'description' => null,
                'display_order' => is_numeric($chapterNumber) ? (int) floor((float) $chapterNumber) : 0,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $chapter = DB::table('hadith_chapters')
            ->where('book_id', $book->id)
            ->where('chapter_number', (string) $chapterNumber)
            ->first();
        $chapterId = $chapter?->id;
    }

    $row = DB::table('hadith_hadiths')
        ->where('source_file', $sourceFile)
        ->orderBy('id')
        ->first();

    $conflict = DB::table('hadith_hadiths')
        ->where('book_id', $book->id)
        ->where('hadith_number', $hadithNumber)
        ->when($row !== null, fn ($query) => $query->where('id', '<>', $row->id))
        ->orderBy('id')
        ->first();

    if ($conflict !== null) {
        DB::table('hadith_hadiths')
            ->where('id', $conflict->id)
            ->update([
                'hadith_number' => "{$hadithNumber} stale {$conflict->id}",
                'updated_at' => now(),
            ]);
        $conflictsMoved++;
    }

    $texts = $json['hadith_text'] ?? [];
    $reference = $json['metadata']['sunnah_reference'] ?? [];
    $payload = [
        'book_id' => $book->id,
        'chapter_id' => $chapterId,
        'local_source_id' => (string) ($json['metadata']['id'] ?? muslimRepairSourceSequence($file)),
        'source_file' => $sourceFile,
        'source_url' => $collectionRow['source_url'] ?? $reference['url'] ?? null,
        'sunnah_urn_ar' => $reference['urn'] ?? null,
        'sunnah_urn_en' => $reference['urn_en'] ?? null,
        'hadith_number' => $hadithNumber,
        'verification_status' => 'source_verified',
        'source_verification_method' => 'source_verified',
        'source_verified_at' => now(),
        'uncertainty_notes' => null,
        'grading_status' => $json['grading']['status'] ?? null,
        'grading_agreement' => $json['grading']['agreement'] ?? null,
        'text_hash' => textHashFromMuslimJson($texts),
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
                    'text' => cleanMuslimDbText((string) $texts[$language]),
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
    ->where('source_file', 'like', 'muslim/%')
    ->whereNotIn('source_file', $sourceFiles)
    ->delete();

echo "muslim: updated {$updated} DB row(s), created {$created}, moved_conflicts {$conflictsMoved}, deleted_stale {$deletedStale}";
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
