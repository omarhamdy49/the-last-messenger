<?php

declare(strict_types=1);

/**
 * Repair existing Malik DB rows from corrected production JSON.
 *
 * This updates by source_file/hadith_number because a normal import would create
 * duplicates when corrected book numbers resolve to different book IDs.
 *
 * Usage:
 *   php scripts/repair_malik_db_from_source.php
 */

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$sourceRoot = dirname(__DIR__, 2).'/prophet-data-generator/hadith/production/malik';
foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--source=')) {
        $sourceRoot = substr($arg, strlen('--source='));
    }
}

if (! is_dir($sourceRoot)) {
    fwrite(STDERR, "Collection directory not found: {$sourceRoot}\n");
    exit(1);
}

function cleanDbText(?string $text): string
{
    return trim((string) preg_replace('/\s+/u', ' ', (string) $text));
}

function textHashFromJson(array $texts): string
{
    return hash('sha256', trim((string) ($texts['ar'] ?? $texts['en'] ?? '')));
}

$collectionId = DB::table('hadith_collections')->updateOrInsert(
    ['name_en' => 'Muwatta Malik'],
    [
        'name_ar' => 'موطأ مالك',
        'slug_ar' => Str::slug('موطأ مالك', '-', 'ar'),
        'slug_en' => Str::slug('Muwatta Malik'),
        'canonical_code' => 'malik',
        'source_slug' => 'malik',
        'updated_at' => now(),
    ]
);

$collection = DB::table('hadith_collections')->where('name_en', 'Muwatta Malik')->first();
if ($collection === null) {
    fwrite(STDERR, "Could not resolve Muwatta Malik collection.\n");
    exit(1);
}

$files = glob(rtrim($sourceRoot, '/').'/hadith_*_final.json') ?: [];
sort($files);

$updated = 0;
$skippedUnmapped = 0;
$missingRows = [];

foreach ($files as $file) {
    $json = json_decode((string) file_get_contents($file), true);
    if (! is_array($json)) {
        continue;
    }

    $sourceFile = 'malik/'.basename($file);
    $collectionRow = $json['collections'][0] ?? [];
    $hadithNumber = (string) ($json['metadata']['id_in_book'] ?? $collectionRow['hadith_number'] ?? '');
    if ($hadithNumber === '' || (int) $hadithNumber > 1973) {
        $skippedUnmapped++;

        continue;
    }

    $bookNumber = (string) ($collectionRow['book_number'] ?? '');
    $bookNameEn = cleanDbText((string) ($collectionRow['book_name_en'] ?? ''));
    $bookNameAr = cleanDbText((string) ($collectionRow['book_name_ar'] ?? ''));

    $bookPayload = [
        'collection_id' => $collection->id,
        'title_ar' => $bookNameAr,
        'title_en' => $bookNameEn,
        'book_number' => $bookNumber,
        'slug_ar' => Str::slug($bookNameAr, '-', 'ar'),
        'slug_en' => Str::slug($bookNameEn),
        'updated_at' => now(),
    ];

    DB::table('hadith_books')->updateOrInsert(
        [
            'collection_id' => $collection->id,
            'title_en' => $bookNameEn,
        ],
        $bookPayload + ['created_at' => now()]
    );
    $book = DB::table('hadith_books')
        ->where('collection_id', $collection->id)
        ->where('title_en', $bookNameEn)
        ->first();

    if ($book === null) {
        $missingRows[] = "{$sourceFile} #{$hadithNumber} (book)";

        continue;
    }

    $chapterId = null;
    $chapterNumber = $json['chapter_info'] ?? $json['metadata']['chapter_id'] ?? null;
    if (! empty($chapterNumber)) {
        DB::table('hadith_chapters')->updateOrInsert(
            [
                'book_id' => $book->id,
                'chapter_number' => (string) $chapterNumber,
            ],
            [
                'name' => json_encode(['ar' => '', 'en' => ''], JSON_UNESCAPED_UNICODE),
                'description' => null,
                'display_order' => is_numeric($chapterNumber) ? (int) $chapterNumber : 0,
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
        ->where('hadith_number', $hadithNumber)
        ->orderBy('id')
        ->first();

    $row ??= DB::table('hadith_hadiths')
        ->where('source_file', $sourceFile)
        ->orderBy('id')
        ->first();

    if ($row === null) {
        $missingRows[] = "{$sourceFile} #{$hadithNumber}";

        continue;
    }

    $conflict = DB::table('hadith_hadiths')
        ->where('book_id', $book->id)
        ->where('hadith_number', $hadithNumber)
        ->where('id', '<>', $row->id)
        ->first();
    if ($conflict !== null) {
        DB::table('hadith_hadiths')
            ->where('id', $conflict->id)
            ->update([
                'hadith_number' => "{$hadithNumber} stale {$conflict->id}",
                'updated_at' => now(),
            ]);
    }

    $texts = $json['hadith_text'] ?? [];
    DB::transaction(function () use ($row, $book, $chapterId, $hadithNumber, $texts, $collectionRow): void {
        DB::table('hadith_hadiths')
            ->where('id', $row->id)
            ->update([
                'book_id' => $book->id,
                'chapter_id' => $chapterId,
                'hadith_number' => $hadithNumber,
                'source_url' => $collectionRow['source_url'] ?? null,
                'verification_status' => 'source_verified',
                'source_verification_method' => 'sunnah_web',
                'source_verified_at' => now(),
                'uncertainty_notes' => null,
                'text_hash' => textHashFromJson($texts),
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
                    'text' => cleanDbText((string) $texts[$language]),
                    'source_url' => $collectionRow['source_url'] ?? null,
                    'verified' => true,
                    'is_primary' => $language === 'ar',
                    'updated_at' => now(),
                ]
            );
        }
    });

    $updated++;
}

echo "malik: repaired {$updated} DB row(s), skipped_unmapped {$skippedUnmapped}";
if ($missingRows !== []) {
    echo ', missing '.count($missingRows);
}
echo "\n";

foreach (array_slice($missingRows, 0, 80) as $entry) {
    echo "MISSING {$entry}\n";
}

exit($missingRows === [] ? 0 : 1);
