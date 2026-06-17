<?php

declare(strict_types=1);

/**
 * Audit Quran translation resource coverage.
 *
 * Usage:
 *   php scripts/audit_quran_translation_resources.php
 */

use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$expectedVerses = (int) config('islamic-data.expected.verses', 6236);

echo "resource coverage\n";
$rows = DB::table('quran_verse_translations as t')
    ->leftJoin('quran_translation_resources as r', 'r.id', '=', 't.resource_id')
    ->selectRaw('t.lang, t.resource_id, COALESCE(r.slug, "null") as slug, COALESCE(r.is_default, 0) as is_default, COUNT(*) as row_count, COUNT(DISTINCT t.verse_id) as verse_count')
    ->groupBy('t.lang', 't.resource_id', 'r.slug', 'r.is_default')
    ->orderBy('t.lang')
    ->orderByDesc('r.is_default')
    ->orderBy('r.slug')
    ->get();

foreach ($rows as $row) {
    printf(
        "%s\tresource=%s\t%s\tdefault=%d\trows=%d\tverses=%d\t%s\n",
        $row->lang,
        $row->resource_id ?? 'null',
        $row->slug,
        (int) $row->is_default,
        (int) $row->row_count,
        (int) $row->verse_count,
        (int) $row->verse_count === $expectedVerses ? 'OK' : 'PARTIAL',
    );
}

echo "\nintegrity\n";
foreach (['ar', 'en', 'fr', 'tr', 'ur', 'en-tl'] as $lang) {
    $duplicateVerseResource = DB::table('quran_verse_translations')
        ->where('lang', $lang)
        ->select('verse_id', 'resource_id')
        ->groupBy('verse_id', 'resource_id')
        ->havingRaw('COUNT(*) > 1')
        ->count();

    $nullResource = DB::table('quran_verse_translations')
        ->where('lang', $lang)
        ->whereNull('resource_id')
        ->count();

    $defaultResources = DB::table('quran_translation_resources')
        ->where('language', $lang)
        ->where('is_default', true)
        ->count();

    $defaultRows = DB::table('quran_verse_translations as t')
        ->join('quran_translation_resources as r', 'r.id', '=', 't.resource_id')
        ->where('t.lang', $lang)
        ->where('r.is_default', true)
        ->count();

    printf(
        "%s\tduplicate_verse_resource=%d\tnull_resource=%d\tdefault_resources=%d\tdefault_rows=%d\n",
        $lang,
        $duplicateVerseResource,
        $nullResource,
        $defaultResources,
        $defaultRows,
    );
}
