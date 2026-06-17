<?php

declare(strict_types=1);

/**
 * Fix Muwatta Malik production JSON from Sunnah.com web pages.
 *
 * Malik is an API exception: collection metadata exists, but the API book route
 * returns empty data and individual hadith route returns 404. The web pages use
 * /malik/{book}/{book-local-hadith}, while local source files use the Arabic
 * reference/global hadith number. This script builds that map from the web UI.
 *
 * Usage:
 *   php scripts/fix_malik_from_sunnah_web.php
 *   php scripts/fix_malik_from_sunnah_web.php --apply
 */

use GuzzleHttp\Client;

require __DIR__.'/../vendor/autoload.php';

$apply = in_array('--apply', array_slice($argv, 1), true);
$verbose = in_array('--verbose', array_slice($argv, 1), true);
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

$client = new Client([
    'base_uri' => 'https://sunnah.com/',
    'timeout' => 60,
    'headers' => [
        'Accept' => 'text/html',
        'User-Agent' => 'TheLastMessengerHadithFixer/1.0',
    ],
]);

function pageText(string $html): string
{
    $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = str_replace(["\xc2\xa0", '“', '”', '‘', '’', '`'], [' ', '"', '"', "'", "'", "'"], $text);

    return trim((string) preg_replace('/\s+/u', ' ', $text));
}

function stripArabicMarks(string $text): string
{
    $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}\x{06D6}-\x{06ED}]/u', '', $text);

    return trim((string) preg_replace('/[ \t]+/u', ' ', (string) $text));
}

function looseText(string $text): string
{
    $text = mb_strtolower(pageText($text), 'UTF-8');
    $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}\x{06D6}-\x{06ED}]/u', '', (string) $text);

    return (string) preg_replace('/[^\p{L}\p{N}]+/u', '', (string) $text);
}

function getHtml(Client $client, string $path): string
{
    $last = null;
    for ($attempt = 1; $attempt <= 4; $attempt++) {
        try {
            return (string) $client->get($path)->getBody();
        } catch (Throwable $e) {
            $last = $e;
            sleep(5 * $attempt);
        }
    }

    throw $last;
}

function extractBooks(string $html): array
{
    preg_match_all(
        '/<a href="\/malik\/(\d+)">\s*<div class="book_number title_number">\d+<\/div><div class="english english_book_name">(.*?)<\/div><div class="arabic arabic_book_name">(.*?)<\/div>/s',
        $html,
        $matches,
        PREG_SET_ORDER
    );

    $books = [];
    foreach ($matches as $match) {
        $books[(int) $match[1]] = [
            'book_number' => (int) $match[1],
            'book_name_en' => pageText($match[2]),
            'book_name_ar' => pageText($match[3]),
        ];
    }

    return $books;
}

function extractReference(string $html, string $label): ?array
{
    $text = pageText($html);
    $quoted = preg_quote($label, '/');
    if (! preg_match('/'.$quoted.'\s*:\s*Book\s+(\d+),\s*Hadith\s+(\d+)/u', $text, $match)) {
        return null;
    }

    return [(int) $match[1], (int) $match[2]];
}

function extractHadithsFromBook(string $html, array $book, int &$nextGlobal): array
{
    $parts = preg_split('/<div class="actualHadithContainer\b/', $html) ?: [];
    array_shift($parts);

    $rows = [];
    foreach ($parts as $part) {
        $english = '';
        if (preg_match('/<div class="english_hadith_full">(.*?)<div class=clear><\/div><\/div><\/div><div class="arabic_hadith_full/s', $part, $match)) {
            $english = pageText($match[1]);
        }

        $arabic = '';
        if (preg_match('/<div class="arabic_hadith_full arabic">(.*?)<\/div>\s*<\/div>\s*<!-- End hadith -->/s', $part, $match)) {
            $arabic = pageText($match[1]);
        }

        $sunnahRef = extractReference($part, 'Sunnah.com reference');
        $sharedRef = extractReference($part, 'Arabic/English book reference');
        $englishRef = extractReference($part, 'USC-MSA web (English) reference');
        $arabicRef = extractReference($part, 'Arabic reference');

        $bookLocalHadith = $sunnahRef[1] ?? $sharedRef[1] ?? $englishRef[1] ?? null;
        if ($bookLocalHadith === null || ($english === '' && $arabic === '')) {
            continue;
        }

        $globalHadith = $nextGlobal++;

        $rows[$globalHadith] = [
            'hadith_number' => (string) $globalHadith,
            'book_number' => $book['book_number'],
            'book_name_en' => $book['book_name_en'],
            'book_name_ar' => $book['book_name_ar'],
            'book_local_hadith' => $bookLocalHadith,
            'source_url' => "https://sunnah.com/malik/{$book['book_number']}/{$bookLocalHadith}",
            'en' => $english,
            'ar' => $arabic,
        ];
    }

    return $rows;
}

$indexHtml = getHtml($client, '/malik');
$books = extractBooks($indexHtml);

$webRows = [];
$nextGlobal = 1;
foreach ($books as $bookNumber => $book) {
    $bookHtml = getHtml($client, "/malik/{$bookNumber}");
    $bookRows = extractHadithsFromBook($bookHtml, $book, $nextGlobal);
    if ($verbose) {
        echo "book {$bookNumber}: ".count($bookRows)." rows\n";
    }
    foreach ($bookRows as $hadithNumber => $row) {
        $webRows[$hadithNumber] = $row;
    }
    usleep(250_000);
}

$files = glob(rtrim($sourceRoot, '/').'/hadith_*_final.json') ?: [];
sort($files);

$sourceByNumber = [];
foreach ($files as $file) {
    $json = json_decode((string) file_get_contents($file), true);
    if (! is_array($json)) {
        continue;
    }

    $hadithNumber = (string) (
        $json['metadata']['id_in_book']
        ?? $json['collections'][0]['hadith_number']
        ?? ''
    );
    if ($hadithNumber !== '') {
        $sourceByNumber[$hadithNumber] = $file;
    }
}

$updated = 0;
$textDiffs = 0;
$metadataDiffs = 0;
$missingWeb = [];
$missingSource = [];

foreach ($sourceByNumber as $hadithNumber => $file) {
    $web = $webRows[(int) $hadithNumber] ?? null;
    if ($web === null) {
        $missingWeb[] = $hadithNumber;

        continue;
    }

    $json = json_decode((string) file_get_contents($file), true);
    if (! is_array($json)) {
        continue;
    }

    $oldTexts = $json['hadith_text'] ?? [];
    $textChanged = ($web['en'] !== '' && looseText((string) ($oldTexts['en'] ?? '')) !== looseText($web['en']))
        || ($web['ar'] !== '' && looseText((string) ($oldTexts['ar'] ?? '')) !== looseText($web['ar']));

    $oldCollection = $json['collections'][0] ?? [];
    $metadataChanged = (string) ($oldCollection['book_number'] ?? '') !== (string) $web['book_number']
        || pageText((string) ($oldCollection['book_name_en'] ?? '')) !== $web['book_name_en']
        || pageText((string) ($oldCollection['book_name_ar'] ?? '')) !== $web['book_name_ar']
        || (string) ($oldCollection['source_url'] ?? '') !== $web['source_url'];

    if ($textChanged) {
        $textDiffs++;
    }
    if ($metadataChanged) {
        $metadataDiffs++;
    }

    if (! $apply || (! $textChanged && ! $metadataChanged)) {
        continue;
    }

    if ($web['en'] !== '') {
        $json['hadith_text']['en'] = $web['en'];
    }
    if ($web['ar'] !== '') {
        $json['hadith_text']['ar'] = $web['ar'];
        $json['hadith_text']['ar_notashkeel'] = stripArabicMarks($web['ar']);
    }
    $json['verification_status'] = 'source_verified';
    $json['uncertainty_notes'] = null;

    $json['collections'] = [[
        'name_en' => 'Muwatta Malik',
        'name_ar' => 'موطأ مالك',
        'hadith_number' => (string) $hadithNumber,
        'book_number' => $web['book_number'],
        'book_name_en' => $web['book_name_en'],
        'book_name_ar' => $web['book_name_ar'],
        'verified' => true,
        'source_url' => $web['source_url'],
        'source_verified' => true,
    ]];

    file_put_contents(
        $file,
        json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n"
    );

    $updated++;
}

foreach ($webRows as $hadithNumber => $_row) {
    if (! isset($sourceByNumber[(string) $hadithNumber])) {
        $missingSource[] = (string) $hadithNumber;
    }
}

echo 'malik web books='.count($books)
    .' web_hadiths='.count($webRows)
    .' source_hadiths='.count($sourceByNumber)
    ." text_diffs={$textDiffs}"
    ." metadata_diffs={$metadataDiffs}"
    ." updated={$updated}"
    .' missing_web='.count($missingWeb)
    .' missing_source='.count($missingSource)
    ."\n";

if ($missingWeb !== []) {
    echo 'Missing on web for source hadiths: '.implode(', ', array_slice($missingWeb, 0, 60)).(count($missingWeb) > 60 ? ' ...' : '')."\n";
}
if ($missingSource !== []) {
    echo 'Missing in source for web hadiths: '.implode(', ', array_slice($missingSource, 0, 60)).(count($missingSource) > 60 ? ' ...' : '')."\n";
}

exit($missingWeb === [] ? 0 : 1);
