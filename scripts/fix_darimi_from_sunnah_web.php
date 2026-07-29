<?php

declare(strict_types=1);

/**
 * Fix Sunan ad-Darimi production JSON from Sunnah.com web pages.
 *
 * Darimi is an API exception: collection metadata exists, but total available
 * is 0, book routes are empty, and individual API hadith routes return 404.
 * The web pages expose Arabic-only hadith text:
 *   - /darimi/introduction => book 0, hadiths 1-649
 *   - /darimi/1..23       => books 1-23, hadiths 650-3406
 *
 * Usage:
 *   php scripts/fix_darimi_from_sunnah_web.php
 *   php scripts/fix_darimi_from_sunnah_web.php --apply
 */

use GuzzleHttp\Client;

require __DIR__.'/../vendor/autoload.php';

$apply = in_array('--apply', array_slice($argv, 1), true);
$verbose = in_array('--verbose', array_slice($argv, 1), true);
$sourceRoot = dirname(__DIR__, 2).'/prophet-data-generator/hadith/production/darimi';
$delayMs = 500;

foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--source=')) {
        $sourceRoot = substr($arg, strlen('--source='));
    }
    if (str_starts_with($arg, '--delay-ms=')) {
        $delayMs = max(0, (int) substr($arg, strlen('--delay-ms=')));
    }
}

if (! is_dir($sourceRoot)) {
    fwrite(STDERR, "Collection directory not found: {$sourceRoot}\n");
    exit(1);
}

$client = new Client([
    'base_uri' => 'https://sunnah.com/',
    'timeout' => 90,
    'headers' => [
        'Accept' => 'text/html',
        'User-Agent' => 'Mozilla/5.0 TheLastMessengerHadithFixer/1.0',
    ],
]);

function darimiText(string $html): string
{
    $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = str_replace(["\xc2\xa0", '“', '”', '‘', '’', '`'], [' ', '"', '"', "'", "'", "'"], $text);

    return trim((string) preg_replace('/\s+/u', ' ', $text));
}

function stripArabicMarksDarimi(string $text): string
{
    $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}\x{06D6}-\x{06ED}]/u', '', $text);

    return trim((string) preg_replace('/[ \t]+/u', ' ', (string) $text));
}

function looseDarimiText(string $text): string
{
    $text = mb_strtolower(darimiText($text), 'UTF-8');
    $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}\x{06D6}-\x{06ED}]/u', '', (string) $text);

    return (string) preg_replace('/[^\p{L}\p{N}]+/u', '', (string) $text);
}

function getDarimiHtml(Client $client, string $path): string
{
    $last = null;
    for ($attempt = 1; $attempt <= 5; $attempt++) {
        try {
            $response = $client->get(ltrim($path, '/'));

            return (string) $response->getBody();
        } catch (Throwable $e) {
            $last = $e;
            sleep(8 * $attempt);
        }
    }

    throw $last;
}

function extractDarimiBooks(string $html): array
{
    preg_match_all(
        '/<a href="\/darimi\/(\d+)">\s*<div class="book_number title_number">.*?<div class="english english_book_name">(.*?)<\/div><div class="arabic arabic_book_name">(.*?)<\/div>/s',
        $html,
        $matches,
        PREG_SET_ORDER
    );

    $books = [
        0 => [
            'book_number' => 0,
            'book_name_en' => '',
            'book_name_ar' => 'سنن الدارمي',
            'path' => '/darimi/introduction',
        ],
    ];

    foreach ($matches as $match) {
        $bookNumber = (int) $match[1];
        $books[$bookNumber] = [
            'book_number' => $bookNumber,
            'book_name_en' => darimiText($match[2]),
            'book_name_ar' => darimiText($match[3]),
            'path' => "/darimi/{$bookNumber}",
        ];
    }
    ksort($books);

    return $books;
}

function extractDarimiRowsFromPage(string $html, array $book): array
{
    $parts = preg_split('/<div class="actualHadithContainer\b/', $html) ?: [];
    array_shift($parts);

    $rows = [];
    foreach ($parts as $part) {
        $arabic = '';
        if (preg_match('/<div class="arabic_hadith_full arabic">(.*?)<\/div>\s*<\/div>\s*<!-- End hadith -->/s', $part, $match)) {
            $arabic = darimiText($match[1]);
        }
        if ($arabic === '') {
            continue;
        }

        $pageText = darimiText($part);
        if (! preg_match('/Arabic reference\s*:\s*Book\s+(\d+),\s*Hadith\s+(\d+)/u', $pageText, $reference)) {
            continue;
        }

        $bookNumber = (int) $reference[1];
        $hadithNumber = (int) $reference[2];
        $rows[(string) $hadithNumber] = [
            'hadith_number' => (string) $hadithNumber,
            'book_number' => (string) $bookNumber,
            'book_name_en' => (string) ($book['book_name_en'] ?? ''),
            'book_name_ar' => (string) ($book['book_name_ar'] ?? ''),
            'chapter_number' => $bookNumber > 0 ? (string) $bookNumber : null,
            'chapter_name_en' => $bookNumber > 0 ? (string) ($book['book_name_en'] ?? '') : null,
            'chapter_name_ar' => $bookNumber > 0 ? (string) ($book['book_name_ar'] ?? '') : null,
            'source_url' => "https://sunnah.com/darimi:{$hadithNumber}",
            'ar' => $arabic,
        ];
    }

    return $rows;
}

function darimiSourceSequence(string $file): int
{
    if (preg_match('/hadith_(\d+)_final\.json$/', basename($file), $match)) {
        return (int) $match[1];
    }

    return PHP_INT_MAX;
}

function darimiSourceFiles(string $sourceRoot): array
{
    $files = glob(rtrim($sourceRoot, '/').'/hadith_*_final.json') ?: [];
    usort($files, static fn (string $a, string $b): int => darimiSourceSequence($a) <=> darimiSourceSequence($b));

    return $files;
}

$indexHtml = getDarimiHtml($client, '/darimi');
$books = extractDarimiBooks($indexHtml);

$webRows = [];
foreach ($books as $bookNumber => $book) {
    $html = getDarimiHtml($client, $book['path']);
    $bookRows = extractDarimiRowsFromPage($html, $book);
    if ($verbose) {
        echo "book {$bookNumber}: ".count($bookRows)." rows\n";
    }
    foreach ($bookRows as $hadithNumber => $row) {
        $webRows[$hadithNumber] = $row;
    }
    usleep($delayMs * 1000);
}
ksort($webRows, SORT_NATURAL);

$files = darimiSourceFiles($sourceRoot);
$sourceByNumber = [];
$invalid = [];
foreach ($files as $file) {
    $json = json_decode((string) file_get_contents($file), true);
    if (! is_array($json)) {
        $invalid[] = basename($file);

        continue;
    }

    $hadithNumber = (string) (
        $json['collections'][0]['hadith_number']
        ?? $json['metadata']['sunnah_reference']['hadith_number']
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
$lowTextMatches = [];

foreach ($sourceByNumber as $hadithNumber => $file) {
    $web = $webRows[$hadithNumber] ?? null;
    if ($web === null) {
        $missingWeb[] = $hadithNumber;

        continue;
    }

    $json = json_decode((string) file_get_contents($file), true);
    if (! is_array($json)) {
        continue;
    }

    $oldTexts = $json['hadith_text'] ?? [];
    $sourceLoose = looseDarimiText((string) ($oldTexts['ar'] ?? ''));
    $webLoose = looseDarimiText($web['ar']);
    $score = 0.0;
    if ($sourceLoose !== '' && $webLoose !== '') {
        similar_text(mb_substr($sourceLoose, 0, 3000), mb_substr($webLoose, 0, 3000), $score);
    }
    if ($score > 0 && $score < 90.0) {
        $lowTextMatches[] = basename($file).":{$web['book_number']}:{$web['hadith_number']}:".round($score, 2);
    }

    $textChanged = $webLoose !== '' && $sourceLoose !== $webLoose;
    $oldCollection = $json['collections'][0] ?? [];
    $metadataChanged = (string) ($oldCollection['book_number'] ?? '') !== $web['book_number']
        || darimiText((string) ($oldCollection['book_name_en'] ?? '')) !== $web['book_name_en']
        || darimiText((string) ($oldCollection['book_name_ar'] ?? '')) !== $web['book_name_ar']
        || (string) ($oldCollection['chapter_number'] ?? '') !== (string) ($web['chapter_number'] ?? '')
        || darimiText((string) ($oldCollection['chapter_name_en'] ?? '')) !== (string) ($web['chapter_name_en'] ?? '')
        || darimiText((string) ($oldCollection['chapter_name_ar'] ?? '')) !== (string) ($web['chapter_name_ar'] ?? '')
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

    $json['hadith_text']['ar'] = $web['ar'];
    $json['hadith_text']['ar_notashkeel'] = stripArabicMarksDarimi($web['ar']);
    $json['hadith_text']['en'] = (string) ($json['hadith_text']['en'] ?? '');
    $json['verification_status'] = 'source_verified';
    $json['uncertainty_notes'] = null;

    $json['collections'] = [[
        'name_en' => 'Sunan al-Darimi',
        'name_ar' => 'سنن الدارمي',
        'hadith_number' => $web['hadith_number'],
        'book_number' => $web['book_number'],
        'book_name_en' => $web['book_name_en'],
        'book_name_ar' => $web['book_name_ar'],
        'chapter_number' => $web['chapter_number'],
        'chapter_name_en' => $web['chapter_name_en'],
        'chapter_name_ar' => $web['chapter_name_ar'],
        'verified' => true,
        'source_url' => $web['source_url'],
        'source_verified' => true,
    ]];

    $json['metadata']['id_in_book'] = is_numeric($web['hadith_number']) ? (int) $web['hadith_number'] : $web['hadith_number'];
    $json['metadata']['book_id'] = $web['book_number'];
    $json['metadata']['chapter_id'] = $web['chapter_number'];
    $json['metadata']['collection'] = [
        'id' => 9,
        'name_ar' => 'سنن الدارمي',
        'name_en' => 'Sunan al-Darimi',
        'author_ar' => 'الإمام أبو محمد عبد الرحمن بن عبد الله بن الدارمي',
        'author_en' => 'Imam Abu Muhammad Abd al-Rahman ibn Abd Allah ibn al-Darimi',
        'total_hadiths' => 3406,
    ];
    $json['metadata']['sunnah_reference'] = [
        'collection' => 'darimi',
        'book_number' => $web['book_number'],
        'hadith_number' => $web['hadith_number'],
        'url' => $web['source_url'],
        'urn' => $json['metadata']['sunnah_reference']['urn'] ?? null,
    ];
    $json['chapter_info'] = $web['chapter_number'];
    $json['source_verification']['sunnah'] = [
        'status' => 'source_verified',
        'web_only' => true,
        'api_slug' => 'darimi',
        'hadith_number' => $web['hadith_number'],
        'book_number' => $web['book_number'],
        'url' => $web['source_url'],
    ];

    file_put_contents(
        $file,
        json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n"
    );
    $updated++;
}

foreach ($webRows as $hadithNumber => $_row) {
    if (! isset($sourceByNumber[$hadithNumber])) {
        $missingSource[] = $hadithNumber;
    }
}

echo 'darimi web_books='.count($books)
    .' web_hadiths='.count($webRows)
    .' source_hadiths='.count($sourceByNumber)
    ." text_diffs={$textDiffs}"
    ." metadata_diffs={$metadataDiffs}"
    ." updated={$updated}"
    .' missing_web='.count($missingWeb)
    .' missing_source='.count($missingSource)
    .' low_text_matches='.count($lowTextMatches)
    .' invalid='.count($invalid)
    ."\n";

if ($missingWeb !== []) {
    echo 'Missing on web for source hadiths: '.implode(', ', array_slice($missingWeb, 0, 60)).(count($missingWeb) > 60 ? ' ...' : '')."\n";
}
if ($missingSource !== []) {
    echo 'Missing in source for web hadiths: '.implode(', ', array_slice($missingSource, 0, 60)).(count($missingSource) > 60 ? ' ...' : '')."\n";
}
if ($lowTextMatches !== []) {
    echo 'Low text matches: '.implode(', ', array_slice($lowTextMatches, 0, 40)).(count($lowTextMatches) > 40 ? ' ...' : '')."\n";
}
if ($invalid !== []) {
    echo 'Invalid source JSON: '.implode(', ', array_slice($invalid, 0, 40)).(count($invalid) > 40 ? ' ...' : '')."\n";
}

exit($missingWeb === [] && $missingSource === [] && $invalid === [] ? 0 : 1);
