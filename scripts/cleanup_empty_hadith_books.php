<?php

declare(strict_types=1);

/**
 * Remove legacy hadith books that have no hadith rows.
 *
 * Usage:
 *   php scripts/cleanup_empty_hadith_books.php
 *   php scripts/cleanup_empty_hadith_books.php --apply
 *
 * Dry-run by default. Deleting a book cascades to its hadith_chapters rows.
 */

use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$apply = in_array('--apply', array_slice($argv, 1), true);

$emptyBooks = DB::table('hadith_books as b')
    ->join('hadith_collections as c', 'c.id', '=', 'b.collection_id')
    ->leftJoin('hadith_hadiths as h', 'h.book_id', '=', 'b.id')
    ->select([
        'b.id',
        'b.title_en',
        'b.title_ar',
        'c.canonical_code',
        'c.source_slug',
    ])
    ->groupBy('b.id', 'b.title_en', 'b.title_ar', 'c.canonical_code', 'c.source_slug')
    ->havingRaw('count(h.id) = 0')
    ->orderBy('c.canonical_code')
    ->orderBy('b.id')
    ->get();

$bookIds = $emptyBooks->pluck('id')->all();
$chapterCount = empty($bookIds)
    ? 0
    : DB::table('hadith_chapters')->whereIn('book_id', $bookIds)->count();

printf(
    "%s empty_books=%d empty_book_chapters=%d\n",
    $apply ? 'apply' : 'dry-run',
    count($bookIds),
    $chapterCount
);

foreach ($emptyBooks as $book) {
    printf(
        "- %s book_id=%d title_en=%s title_ar=%s\n",
        $book->canonical_code ?: $book->source_slug ?: 'unknown',
        $book->id,
        $book->title_en ?? '',
        $book->title_ar ?? ''
    );
}

if (! $apply || empty($bookIds)) {
    exit(0);
}

DB::transaction(static function () use ($bookIds): void {
    DB::table('hadith_books')->whereIn('id', $bookIds)->delete();
});

echo "deleted_empty_books=".count($bookIds)."\n";
