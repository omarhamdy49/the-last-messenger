<?php

declare(strict_types=1);

/**
 * Fix Al-Adab Al-Mufrad production JSON from Sunnah.com API data.
 *
 * The local source directory is "aladab_almufrad"; Sunnah.com exposes the
 * collection through the API slug "adab". The source file order matches the
 * Sunnah API order, while many existing collection rows contain stale
 * cross-reference metadata.
 *
 * Usage:
 *   php scripts/fix_aladab_from_sunnah_api.php
 *   php scripts/fix_aladab_from_sunnah_api.php --apply
 */

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$apply = in_array('--apply', array_slice($argv, 1), true);
$verbose = in_array('--verbose', array_slice($argv, 1), true);
$sourceRoot = dirname(__DIR__, 2).'/prophet-data-generator/hadith/production/aladab_almufrad';
$delayMs = 900;
$limit = 100;

foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--source=')) {
        $sourceRoot = substr($arg, strlen('--source='));
    }
    if (str_starts_with($arg, '--delay-ms=')) {
        $delayMs = max(0, (int) substr($arg, strlen('--delay-ms=')));
    }
    if (str_starts_with($arg, '--limit=')) {
        $limit = min(100, max(1, (int) substr($arg, strlen('--limit='))));
    }
}

if (! is_dir($sourceRoot)) {
    fwrite(STDERR, "Collection directory not found: {$sourceRoot}\n");
    exit(1);
}

$apiKey = (string) config('sunnah.api.key');
if ($apiKey === '') {
    fwrite(STDERR, "SUNNAH_API_KEY is not configured.\n");
    exit(1);
}

$client = new Client([
    'base_uri' => rtrim((string) config('sunnah.api.base_url'), '/').'/',
    'timeout' => 60,
    'headers' => [
        'Accept' => 'application/json',
        'X-API-Key' => $apiKey,
        'Referer' => 'https://sunnah.com/',
        'User-Agent' => 'TheLastMessengerHadithFixer/1.0',
    ],
]);
$webClient = new Client([
    'base_uri' => 'https://sunnah.com/',
    'timeout' => 60,
    'headers' => [
        'Accept' => 'text/html',
        'User-Agent' => 'TheLastMessengerHadithFixer/1.0',
    ],
]);

function aladabText(?string $html): string
{
    $text = html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = str_replace(["\xc2\xa0", '“', '”', '‘', '’', '`'], [' ', '"', '"', "'", "'", "'"], $text);

    return trim((string) preg_replace('/\s+/u', ' ', $text));
}

function stripArabicMarksAladab(string $text): string
{
    $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}\x{06D6}-\x{06ED}]/u', '', $text);

    return trim((string) preg_replace('/[ \t]+/u', ' ', (string) $text));
}

function looseAladabText(?string $html): string
{
    $text = mb_strtolower(aladabText($html), 'UTF-8');
    $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}\x{06D6}-\x{06ED}]/u', '', (string) $text);

    return (string) preg_replace('/[^\p{L}\p{N}]+/u', '', (string) $text);
}

function aladabRefKey(string $bookNumber, string $hadithNumber): string
{
    $hadith = preg_replace('/\s+/u', '', mb_strtolower(trim($hadithNumber), 'UTF-8'));

    return trim($bookNumber).'|'.$hadith;
}

function getJsonWithRetryAladab(Client $client, string $uri, array $query = [], int $attempts = 6): object
{
    $last = null;
    for ($attempt = 1; $attempt <= $attempts; $attempt++) {
        try {
            $response = $client->get($uri, $query === [] ? [] : ['query' => $query]);

            return json_decode((string) $response->getBody());
        } catch (RequestException $e) {
            $last = $e;
            $status = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            if (! in_array($status, [429, 500, 502, 503, 504], true) || $attempt === $attempts) {
                throw $e;
            }
            sleep($status === 429 ? 20 * $attempt : 5 * $attempt);
        }
    }

    throw $last;
}

function aladabLangPayload(object $apiHadith, string $lang): array
{
    foreach (($apiHadith->hadith ?? []) as $translation) {
        if (($translation->lang ?? null) !== $lang) {
            continue;
        }

        return [
            'text' => aladabText($translation->body ?? ''),
            'urn' => isset($translation->urn) ? (int) $translation->urn : null,
            'chapter_number' => isset($translation->chapterNumber) ? (string) $translation->chapterNumber : null,
            'chapter_title' => aladabText($translation->chapterTitle ?? ''),
            'grade' => (string) (($translation->grades[0]->grade ?? '') ?: ''),
        ];
    }

    return [
        'text' => '',
        'urn' => null,
        'chapter_number' => null,
        'chapter_title' => '',
        'grade' => '',
    ];
}

function aladabBookName(object $book, string $lang): string
{
    foreach (($book->book ?? []) as $translation) {
        if (($translation->lang ?? null) === $lang) {
            return aladabText($translation->name ?? '');
        }
    }

    return '';
}

function aladabBookObject(string $bookNumber, string $nameEn, string $nameAr, ?int $start = null, ?int $end = null): object
{
    $book = (object) [
        'bookNumber' => $bookNumber,
        'book' => [
            (object) ['lang' => 'en', 'name' => $nameEn],
            (object) ['lang' => 'ar', 'name' => $nameAr],
        ],
    ];
    if ($start !== null) {
        $book->hadithStartNumber = $start;
    }
    if ($end !== null) {
        $book->hadithEndNumber = $end;
    }

    return $book;
}

function fetchAladabWebBooks(Client $webClient): array
{
    $response = $webClient->get('adab');
    $html = (string) $response->getBody();
    $books = [];

    if (preg_match_all('/<div class="book_title title" id="obi(\d+)">.*?<div class="english english_book_name">(.*?)<\/div><div class="arabic arabic_book_name">(.*?)<\/div>/su', $html, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $bookNumber = (string) $match[1];
            $books[$bookNumber] = aladabBookObject(
                $bookNumber,
                aladabText($match[2]),
                aladabText($match[3]),
            );
        }
    }

    $webOnlyRanges = [
        '14' => [260, 308],
        '27' => [475, 482],
        '50' => [1205, 1233],
        '51' => [1234, 1237],
        '53' => [1244, 1258],
        '55' => [1282, 1299],
        '56' => [1300, 1316],
    ];
    foreach ($webOnlyRanges as $bookNumber => [$start, $end]) {
        if (isset($books[$bookNumber])) {
            $books[$bookNumber]->hadithStartNumber = $start;
            $books[$bookNumber]->hadithEndNumber = $end;
        }
    }

    return $books;
}

function aladabApiRow(object $apiHadith, object $book): array
{
    $en = aladabLangPayload($apiHadith, 'en');
    $ar = aladabLangPayload($apiHadith, 'ar');
    $chapterNumber = $ar['chapter_number'] ?: $en['chapter_number'] ?: (isset($apiHadith->chapterId) ? (string) $apiHadith->chapterId : null);

    return [
        'hadith_number' => (string) $apiHadith->hadithNumber,
        'book_number' => (string) $apiHadith->bookNumber,
        'book_name_en' => aladabBookName($book, 'en'),
        'book_name_ar' => aladabBookName($book, 'ar'),
        'chapter_number' => $chapterNumber,
        'chapter_name_en' => $en['chapter_title'],
        'chapter_name_ar' => $ar['chapter_title'],
        'source_url' => 'https://sunnah.com/adab:'.rawurlencode((string) $apiHadith->hadithNumber),
        'en' => $en['text'],
        'ar' => $ar['text'],
        'urn_en' => $en['urn'],
        'urn_ar' => $ar['urn'],
        'grade_en' => $en['grade'],
        'grade_ar' => $ar['grade'],
    ];
}

function aladabBookForHadithNumber(array $books, string $hadithNumber): object
{
    $numeric = (int) preg_replace('/\D+/', '', $hadithNumber);
    foreach ($books as $book) {
        $start = isset($book->hadithStartNumber) ? (int) $book->hadithStartNumber : null;
        $end = isset($book->hadithEndNumber) ? (int) $book->hadithEndNumber : null;
        if ($start !== null && $end !== null && $numeric >= $start && $numeric <= $end) {
            return $book;
        }
    }

    return aladabBookObject('', '', '');
}

function aladabSourceFallbackRow(array $json, string $hadithNumber, object $book): array
{
    $collection = $json['collections'][0] ?? [];
    $reference = $json['metadata']['sunnah_reference'] ?? [];
    $chapterNumber = (string) ($collection['chapter_number'] ?? $json['chapter_info'] ?? '');

    return [
        'hadith_number' => $hadithNumber,
        'book_number' => (string) ($book->bookNumber ?? ($collection['book_number'] ?? '')),
        'book_name_en' => aladabBookName($book, 'en') ?: aladabText((string) ($collection['book_name_en'] ?? '')),
        'book_name_ar' => aladabBookName($book, 'ar') ?: aladabText((string) ($collection['book_name_ar'] ?? '')),
        'chapter_number' => $chapterNumber !== '' ? $chapterNumber : null,
        'chapter_name_en' => aladabText((string) ($collection['chapter_name_en'] ?? '')),
        'chapter_name_ar' => aladabText((string) ($collection['chapter_name_ar'] ?? '')),
        'source_url' => 'https://sunnah.com/adab:'.rawurlencode($hadithNumber),
        'en' => aladabText((string) ($json['hadith_text']['en'] ?? '')),
        'ar' => aladabText((string) ($json['hadith_text']['ar'] ?? '')),
        'urn_en' => isset($reference['urn_en']) ? (int) $reference['urn_en'] : null,
        'urn_ar' => isset($reference['urn']) ? (int) $reference['urn'] : null,
        'grade_en' => (string) ($json['grading']['status'] ?? ''),
        'grade_ar' => '',
    ];
}

function aladabSourceSequence(string $file): int
{
    if (preg_match('/hadith_(\d+)_final\.json$/', basename($file), $match)) {
        return (int) $match[1];
    }

    return PHP_INT_MAX;
}

function aladabSourceFiles(string $sourceRoot): array
{
    $files = glob(rtrim($sourceRoot, '/').'/hadith_*_final.json') ?: [];
    usort($files, static fn (string $a, string $b): int => aladabSourceSequence($a) <=> aladabSourceSequence($b));

    return $files;
}

function aladabExpectedHadithNumbers(array $files): array
{
    $variantNumbers = [];
    foreach ($files as $file) {
        $json = json_decode((string) file_get_contents($file), true);
        if (! is_array($json)) {
            continue;
        }

        $collection = $json['collections'][0] ?? [];
        $source = (string) ($collection['source_url'] ?? $json['metadata']['sunnah_reference']['url'] ?? '');
        $hadithNumber = (string) ($collection['hadith_number'] ?? $json['metadata']['sunnah_reference']['hadith_number'] ?? '');
        if (preg_match('~/adab:([0-9]+[a-z])$~i', $source, $match) || preg_match('/^[0-9]+[a-z]$/i', $hadithNumber)) {
            $variant = $match[1] ?? $hadithNumber;
            if (preg_match('/^(\d+)[a-z]$/i', $variant, $baseMatch)) {
                $variantNumbers[(int) $baseMatch[1]][] = $variant;
            }
        }
    }

    foreach ($variantNumbers as &$variants) {
        $variants = array_values(array_unique($variants));
        sort($variants, SORT_NATURAL);
    }
    unset($variants);

    $numbers = [];
    for ($number = 1; $number <= 1322; $number++) {
        $numbers[] = (string) $number;
        foreach (($variantNumbers[$number] ?? []) as $variant) {
            $numbers[] = $variant;
        }
    }

    return array_slice($numbers, 0, count($files));
}

$books = [];
$page = 1;
do {
    $response = getJsonWithRetryAladab($client, 'collections/adab/books', ['limit' => 100, 'page' => $page]);
    foreach (($response->data ?? []) as $book) {
        $books[(string) $book->bookNumber] = $book;
    }
    $page = isset($response->next) && $response->next !== null ? (int) $response->next : 0;
    if ($page > 0) {
        usleep($delayMs * 1000);
    }
} while ($page > 0);

$apiBookRouteBooks = $books;
$webBooks = fetchAladabWebBooks($webClient);
foreach ($webBooks as $bookNumber => $book) {
    $books[$bookNumber] ??= $book;
}

$apiRowsByHadithNumber = [];
$apiRowsFromBookRoutes = 0;
foreach ($apiBookRouteBooks as $bookNumber => $book) {
    $bookRows = 0;
    $hadithPage = 1;
    do {
        $response = getJsonWithRetryAladab($client, "collections/adab/books/{$bookNumber}/hadiths", [
            'limit' => $limit,
            'page' => $hadithPage,
        ]);

        foreach (($response->data ?? []) as $apiHadith) {
            $row = aladabApiRow($apiHadith, $book);
            $apiRowsByHadithNumber[$row['hadith_number']] = $row;
            $apiRowsFromBookRoutes++;
            $bookRows++;
        }

        $hadithPage = isset($response->next) && $response->next !== null ? (int) $response->next : 0;
        if ($hadithPage > 0) {
            usleep($delayMs * 1000);
        }
    } while ($hadithPage > 0);

    if ($verbose) {
        echo "book {$bookNumber}: {$bookRows} rows\n";
    }

    usleep($delayMs * 1000);
}

$files = aladabSourceFiles($sourceRoot);
$expectedHadithNumbers = aladabExpectedHadithNumbers($files);
$individualFallbacks = 0;
$sourceFallbacks = 0;
$individualFetchFailures = [];

foreach ($expectedHadithNumbers as $sourceIndex => $hadithNumber) {
    if (isset($apiRowsByHadithNumber[$hadithNumber])) {
        continue;
    }

    try {
        $apiHadith = getJsonWithRetryAladab($client, 'collections/adab/hadiths/'.rawurlencode($hadithNumber));
    } catch (RequestException $e) {
        $sourceJson = json_decode((string) file_get_contents($files[$sourceIndex]), true);
        if (is_array($sourceJson)) {
            $book = aladabBookForHadithNumber($books, $hadithNumber);
            $apiRowsByHadithNumber[$hadithNumber] = aladabSourceFallbackRow($sourceJson, $hadithNumber, $book);
            $sourceFallbacks++;
        } else {
            $individualFetchFailures[] = $hadithNumber;
        }

        continue;
    }

    $book = $books[(string) ($apiHadith->bookNumber ?? '')] ?? aladabBookObject((string) ($apiHadith->bookNumber ?? ''), '', '');
    $apiRowsByHadithNumber[$hadithNumber] = aladabApiRow($apiHadith, $book);
    $individualFallbacks++;
    usleep($delayMs * 1000);
}

$apiRowsOrdered = [];
foreach ($expectedHadithNumbers as $hadithNumber) {
    if (isset($apiRowsByHadithNumber[$hadithNumber])) {
        $apiRowsOrdered[] = $apiRowsByHadithNumber[$hadithNumber];
    }
}

$updated = 0;
$textDiffs = 0;
$metadataDiffs = 0;
$sequenceRemaps = 0;
$lowTextMatches = [];
$invalid = [];
$missingApi = [];
$seenApiKeys = [];

foreach ($files as $sourceIndex => $file) {
    $json = json_decode((string) file_get_contents($file), true);
    if (! is_array($json)) {
        $invalid[] = basename($file);

        continue;
    }

    $api = $apiRowsOrdered[$sourceIndex] ?? null;
    if ($api === null) {
        $missingApi[] = basename($file);

        continue;
    }

    $oldCollection = $json['collections'][0] ?? [];
    $oldBook = (string) ($oldCollection['book_number'] ?? $json['metadata']['sunnah_reference']['book_number'] ?? '');
    $oldHadith = (string) ($oldCollection['hadith_number'] ?? $json['metadata']['sunnah_reference']['hadith_number'] ?? '');
    $oldKey = aladabRefKey($oldBook, $oldHadith);
    $apiKey = aladabRefKey($api['book_number'], $api['hadith_number']);
    $seenApiKeys[$apiKey] = true;

    if ($oldKey !== '|' && $oldKey !== $apiKey) {
        $sequenceRemaps++;
        if ($verbose) {
            echo 'sequence remap '.basename($file)." {$oldKey} -> {$apiKey}\n";
        }
    }

    $oldTexts = $json['hadith_text'] ?? [];
    $sourceLoose = looseAladabText((string) ($oldTexts['ar'] ?? '')).' '.looseAladabText((string) ($oldTexts['en'] ?? ''));
    $apiLoose = looseAladabText($api['ar']).' '.looseAladabText($api['en']);
    $score = 0.0;
    if ($sourceLoose !== '' && $apiLoose !== '') {
        similar_text(mb_substr($sourceLoose, 0, 3000), mb_substr($apiLoose, 0, 3000), $score);
    }
    if ($score > 0 && $score < 80.0) {
        $lowTextMatches[] = basename($file).":{$api['book_number']}:{$api['hadith_number']}:".round($score, 2);
    }

    $textChanged = ($api['en'] !== '' && looseAladabText((string) ($oldTexts['en'] ?? '')) !== looseAladabText($api['en']))
        || ($api['ar'] !== '' && looseAladabText((string) ($oldTexts['ar'] ?? '')) !== looseAladabText($api['ar']));

    $metadataChanged = (string) ($oldCollection['hadith_number'] ?? '') !== $api['hadith_number']
        || (string) ($oldCollection['book_number'] ?? '') !== $api['book_number']
        || aladabText((string) ($oldCollection['book_name_en'] ?? '')) !== $api['book_name_en']
        || aladabText((string) ($oldCollection['book_name_ar'] ?? '')) !== $api['book_name_ar']
        || (string) ($oldCollection['chapter_number'] ?? '') !== (string) ($api['chapter_number'] ?? '')
        || aladabText((string) ($oldCollection['chapter_name_en'] ?? '')) !== $api['chapter_name_en']
        || aladabText((string) ($oldCollection['chapter_name_ar'] ?? '')) !== $api['chapter_name_ar']
        || (string) ($oldCollection['source_url'] ?? '') !== $api['source_url']
        || count($json['collections'] ?? []) !== 1;

    if ($textChanged) {
        $textDiffs++;
    }
    if ($metadataChanged) {
        $metadataDiffs++;
    }

    if (! $apply || (! $textChanged && ! $metadataChanged)) {
        continue;
    }

    $json['hadith_text']['en'] = $api['en'];
    $json['hadith_text']['ar'] = $api['ar'];
    $json['hadith_text']['ar_notashkeel'] = stripArabicMarksAladab($api['ar']);
    $json['verification_status'] = 'source_verified';
    $json['uncertainty_notes'] = null;
    $json['source_verification']['sunnah'] = [
        'status' => 'source_verified',
        'url' => $api['source_url'],
    ];

    $json['collections'] = [[
        'name_en' => 'Al-Adab Al-Mufrad',
        'name_ar' => 'الأدب المفرد',
        'hadith_number' => $api['hadith_number'],
        'book_number' => $api['book_number'],
        'book_name_en' => $api['book_name_en'],
        'book_name_ar' => $api['book_name_ar'],
        'chapter_number' => $api['chapter_number'],
        'chapter_name_en' => $api['chapter_name_en'],
        'chapter_name_ar' => $api['chapter_name_ar'],
        'verified' => true,
        'source_url' => $api['source_url'],
        'source_verified' => true,
    ]];

    $json['metadata']['book_id'] = $api['book_number'];
    $json['metadata']['chapter_id'] = $api['chapter_number'];
    $json['metadata']['sunnah_reference'] = [
        'collection' => 'adab',
        'book_number' => $api['book_number'],
        'hadith_number' => $api['hadith_number'],
        'url' => $api['source_url'],
        'urn' => $api['urn_ar'],
        'urn_en' => $api['urn_en'],
    ];
    $json['chapter_info'] = $api['chapter_number'];

    if (! isset($json['grading']) || ! is_array($json['grading'])) {
        $json['grading'] = [];
    }
    if (($api['grade_en'] ?? '') !== '') {
        $json['grading']['status'] = $api['grade_en'];
    }
    $json['grading']['verified'] = true;

    file_put_contents(
        $file,
        json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n"
    );

    $updated++;
}

$missingSource = count($apiRowsOrdered) > count($files)
    ? array_map(
        static fn (array $row): string => $row['book_number'].':'.$row['hadith_number'],
        array_slice($apiRowsOrdered, count($files)),
    )
    : [];

echo 'aladab_almufrad api_books='.count($books)
    .' api_hadiths='.count($apiRowsOrdered)
    ." book_route_rows={$apiRowsFromBookRoutes}"
    ." individual_fallbacks={$individualFallbacks}"
    ." source_fallbacks={$sourceFallbacks}"
    .' source_hadiths='.count($files)
    ." text_diffs={$textDiffs}"
    ." metadata_diffs={$metadataDiffs}"
    ." updated={$updated}"
    ." sequence_remaps={$sequenceRemaps}"
    .' low_text_matches='.count($lowTextMatches)
    .' invalid='.count($invalid)
    .' missing_api='.count($missingApi)
    .' individual_fetch_failures='.count($individualFetchFailures)
    .' missing_source='.count($missingSource)
    ."\n";

if ($lowTextMatches !== []) {
    echo 'Low text matches: '.implode(', ', array_slice($lowTextMatches, 0, 40)).(count($lowTextMatches) > 40 ? ' ...' : '')."\n";
}
if ($missingApi !== []) {
    echo 'Missing API for source rows: '.implode(', ', array_slice($missingApi, 0, 40)).(count($missingApi) > 40 ? ' ...' : '')."\n";
}
if ($missingSource !== []) {
    echo 'Missing source for API rows: '.implode(', ', array_slice($missingSource, 0, 40)).(count($missingSource) > 40 ? ' ...' : '')."\n";
}
if ($individualFetchFailures !== []) {
    echo 'Individual fetch failures: '.implode(', ', array_slice($individualFetchFailures, 0, 40)).(count($individualFetchFailures) > 40 ? ' ...' : '')."\n";
}
if ($invalid !== []) {
    echo 'Invalid source JSON: '.implode(', ', array_slice($invalid, 0, 40)).(count($invalid) > 40 ? ' ...' : '')."\n";
}

exit($invalid === [] && $missingApi === [] && $missingSource === [] && $individualFetchFailures === [] ? 0 : 1);
