<?php

declare(strict_types=1);

/**
 * Fix Sahih al-Bukhari production JSON from Sunnah.com API data.
 *
 * Bukhari source filenames are local sequence IDs (1..7277), while Sunnah.com
 * hadith references run up to 7563 with gaps. This script maps source files to
 * API rows by canonical collection order, then rewrites text and metadata.
 *
 * Usage:
 *   php scripts/fix_bukhari_from_sunnah_api.php
 *   php scripts/fix_bukhari_from_sunnah_api.php --apply
 */

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$apply = in_array('--apply', array_slice($argv, 1), true);
$verbose = in_array('--verbose', array_slice($argv, 1), true);
$sourceRoot = dirname(__DIR__, 2).'/prophet-data-generator/hadith/production/bukhari';
$delayMs = 350;
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

function apiText(?string $html): string
{
    $text = html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = str_replace(["\xc2\xa0", '“', '”', '‘', '’', '`'], [' ', '"', '"', "'", "'", "'"], $text);

    return trim((string) preg_replace('/\s+/u', ' ', $text));
}

function stripArabicMarksBukhari(string $text): string
{
    $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}\x{06D6}-\x{06ED}]/u', '', $text);

    return trim((string) preg_replace('/[ \t]+/u', ' ', (string) $text));
}

function looseBukhariText(?string $html): string
{
    $text = mb_strtolower(apiText($html), 'UTF-8');
    $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}\x{06D6}-\x{06ED}]/u', '', (string) $text);

    return (string) preg_replace('/[^\p{L}\p{N}]+/u', '', (string) $text);
}

function getJsonWithRetryBukhari(Client $client, string $uri, array $query = [], int $attempts = 5): object
{
    $last = null;
    for ($attempt = 1; $attempt <= $attempts; $attempt++) {
        try {
            $response = $client->get($uri, $query === [] ? [] : ['query' => $query]);

            return json_decode((string) $response->getBody());
        } catch (RequestException $e) {
            $last = $e;
            $status = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            if ($status !== 429 || $attempt === $attempts) {
                throw $e;
            }
            sleep(15 * $attempt);
        }
    }

    throw $last;
}

function getOptionalJsonWithRetryBukhari(Client $client, string $uri, array $query = [], int $attempts = 4): ?object
{
    $last = null;
    for ($attempt = 1; $attempt <= $attempts; $attempt++) {
        try {
            $response = $client->get($uri, $query === [] ? [] : ['query' => $query]);

            return json_decode((string) $response->getBody());
        } catch (RequestException $e) {
            $last = $e;
            $status = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            if ($status === 404) {
                return null;
            }
            if ($status !== 429 || $attempt === $attempts) {
                throw $e;
            }
            sleep(15 * $attempt);
        }
    }

    throw $last;
}

function getHtmlWithRetryBukhari(Client $client, string $uri, int $attempts = 4): string
{
    $last = null;
    for ($attempt = 1; $attempt <= $attempts; $attempt++) {
        try {
            return (string) $client->get($uri)->getBody();
        } catch (RequestException $e) {
            $last = $e;
            $status = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            if ($status !== 429 || $attempt === $attempts) {
                throw $e;
            }
            sleep(10 * $attempt);
        }
    }

    throw $last;
}

function langPayloadBukhari(object $apiHadith, string $lang): array
{
    foreach (($apiHadith->hadith ?? []) as $translation) {
        if (($translation->lang ?? null) !== $lang) {
            continue;
        }

        return [
            'text' => apiText($translation->body ?? ''),
            'urn' => isset($translation->urn) ? (int) $translation->urn : null,
            'chapter_number' => isset($translation->chapterNumber) ? (string) $translation->chapterNumber : null,
            'chapter_title' => apiText($translation->chapterTitle ?? ''),
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

function bookNameBukhari(object $book, string $lang): string
{
    foreach (($book->book ?? []) as $translation) {
        if (($translation->lang ?? null) === $lang) {
            return apiText($translation->name ?? '');
        }
    }

    return '';
}

function apiRowFromBukhariHadith(object $apiHadith, object $book): array
{
    $en = langPayloadBukhari($apiHadith, 'en');
    $ar = langPayloadBukhari($apiHadith, 'ar');
    $chapterNumber = $ar['chapter_number'] ?: $en['chapter_number'] ?: (isset($apiHadith->chapterId) ? (string) $apiHadith->chapterId : null);

    return [
        'hadith_number' => (string) $apiHadith->hadithNumber,
        'book_number' => (string) $apiHadith->bookNumber,
        'book_name_en' => bookNameBukhari($book, 'en'),
        'book_name_ar' => bookNameBukhari($book, 'ar'),
        'chapter_number' => $chapterNumber,
        'chapter_name_en' => $en['chapter_title'],
        'chapter_name_ar' => $ar['chapter_title'],
        'source_url' => 'https://sunnah.com/bukhari:'.$apiHadith->hadithNumber,
        'en' => $en['text'],
        'ar' => $ar['text'],
        'urn_en' => $en['urn'],
        'urn_ar' => $ar['urn'],
        'grade_en' => $en['grade'],
        'grade_ar' => $ar['grade'],
    ];
}

function webRowsForBukhariBook(Client $webClient, object $book): array
{
    $html = getHtmlWithRetryBukhari($webClient, '/bukhari/'.rawurlencode((string) $book->bookNumber));

    preg_match_all('/<a name=C([^"]+)><\/a>/s', $html, $anchorMatches, PREG_OFFSET_CAPTURE);
    $anchors = [];
    foreach ($anchorMatches[1] ?? [] as $anchor) {
        $anchors[] = ['chapter' => (string) $anchor[0], 'offset' => (int) $anchor[1]];
    }

    preg_match_all('/<div class="actualHadithContainer\b.*?<!-- end actual hadith container -->/s', $html, $containerMatches, PREG_OFFSET_CAPTURE);

    $rows = [];
    foreach ($containerMatches[0] ?? [] as $container) {
        $part = (string) $container[0];
        $offset = (int) $container[1];
        $chapterNumber = null;
        foreach ($anchors as $anchor) {
            if ($anchor['offset'] > $offset) {
                break;
            }
            $chapterNumber = preg_replace('/\.00$/', '', $anchor['chapter']);
        }

        if (! preg_match('/Sahih al-Bukhari\s+(\d+)/u', apiText($part), $referenceMatch)) {
            continue;
        }
        $hadithNumber = (string) $referenceMatch[1];

        $english = '';
        if (preg_match('/<div class="english_hadith_full">(.*?)<div class=clear><\/div><\/div><\/div><div class="arabic_hadith_full/s', $part, $match)) {
            $english = apiText($match[1]);
        }

        $arabic = '';
        if (preg_match('/<div class="arabic_hadith_full arabic">(.*?)(?:<table class=hadith_reference|<!-- end actual hadith container -->)/s', $part, $match)) {
            $arabic = apiText($match[1]);
        }

        if ($english === '' && $arabic === '') {
            continue;
        }

        $rows[$hadithNumber] = [
            'hadith_number' => $hadithNumber,
            'book_number' => (string) $book->bookNumber,
            'book_name_en' => bookNameBukhari($book, 'en'),
            'book_name_ar' => bookNameBukhari($book, 'ar'),
            'chapter_number' => $chapterNumber,
            'chapter_name_en' => '',
            'chapter_name_ar' => '',
            'source_url' => 'https://sunnah.com/bukhari:'.$hadithNumber,
            'en' => $english,
            'ar' => $arabic,
            'urn_en' => null,
            'urn_ar' => null,
            'grade_en' => 'Sahih',
            'grade_ar' => 'صحيح',
        ];
    }

    return $rows;
}

function sourceSequenceNumberBukhari(string $file): int
{
    if (preg_match('/hadith_(\d+)_final\.json$/', basename($file), $match)) {
        return (int) $match[1];
    }

    return PHP_INT_MAX;
}

function sourceFilesBukhari(string $sourceRoot): array
{
    $files = glob(rtrim($sourceRoot, '/').'/hadith_*_final.json') ?: [];
    usort($files, static fn (string $a, string $b): int => sourceSequenceNumberBukhari($a) <=> sourceSequenceNumberBukhari($b));

    return $files;
}

$books = [];
$page = 1;
do {
    $response = getJsonWithRetryBukhari($client, 'collections/bukhari/books', ['limit' => 100, 'page' => $page]);
    foreach (($response->data ?? []) as $book) {
        $books[(int) $book->bookNumber] = $book;
    }
    $page = isset($response->next) && $response->next !== null ? (int) $response->next : 0;
    if ($page > 0) {
        usleep($delayMs * 1000);
    }
} while ($page > 0);

ksort($books);

$apiRows = [];
$individualFallbacks = 0;
$webFallbacks = 0;
$bookEndpointGaps = [];
foreach ($books as $bookNumber => $book) {
    $hadithPage = 1;
    $bookRows = 0;
    $seenInBook = [];
    $bookApiRows = [];
    $bookSequence = 0;
    do {
        $response = getJsonWithRetryBukhari($client, "collections/bukhari/books/{$bookNumber}/hadiths", [
            'limit' => $limit,
            'page' => $hadithPage,
        ]);

        foreach (($response->data ?? []) as $apiHadith) {
            $seenInBook[(string) $apiHadith->hadithNumber] = true;
            $row = apiRowFromBukhariHadith($apiHadith, $book);
            $row['_sort'] = is_numeric($row['hadith_number']) ? (int) $row['hadith_number'] : PHP_INT_MAX;
            $row['_seq'] = $bookSequence++;
            $bookApiRows[] = $row;
            $bookRows++;
        }

        $hadithPage = isset($response->next) && $response->next !== null ? (int) $response->next : 0;
        if ($hadithPage > 0) {
            usleep($delayMs * 1000);
        }
    } while ($hadithPage > 0);

    $expectedBookRows = (int) ($book->numberOfHadith ?? 0);
    if ($expectedBookRows > 0 && $bookRows < $expectedBookRows) {
        $filled = 0;
        $start = (int) ($book->hadithStartNumber ?? 0);
        $end = (int) ($book->hadithEndNumber ?? 0);

        for ($hadithNumber = $start; $hadithNumber <= $end; $hadithNumber++) {
            if (isset($seenInBook[(string) $hadithNumber])) {
                continue;
            }

            $individual = getOptionalJsonWithRetryBukhari($client, "collections/bukhari/hadiths/{$hadithNumber}");
            usleep($delayMs * 1000);
            if ($individual === null || (string) ($individual->bookNumber ?? '') !== (string) $book->bookNumber) {
                continue;
            }

            $seenInBook[(string) $individual->hadithNumber] = true;
            $row = apiRowFromBukhariHadith($individual, $book);
            $row['_sort'] = is_numeric($row['hadith_number']) ? (int) $row['hadith_number'] : PHP_INT_MAX;
            $row['_seq'] = $bookSequence++;
            $bookApiRows[] = $row;
            $bookRows++;
            $filled++;
            $individualFallbacks++;

            if ($bookRows >= $expectedBookRows) {
                break;
            }
        }

        if ($bookRows < $expectedBookRows || $filled > 0) {
            $bookEndpointGaps[] = "{$bookNumber}:{$bookRows}/{$expectedBookRows} (+{$filled})";
        }
    }

    if ($expectedBookRows > 0 && $bookRows < $expectedBookRows) {
        $filled = 0;
        $webRows = webRowsForBukhariBook($webClient, $book);
        foreach ($webRows as $hadithNumber => $webRow) {
            if (isset($seenInBook[(string) $hadithNumber])) {
                continue;
            }

            $webRow['_sort'] = is_numeric($webRow['hadith_number']) ? (int) $webRow['hadith_number'] : PHP_INT_MAX;
            $webRow['_seq'] = $bookSequence++;
            $bookApiRows[] = $webRow;
            $seenInBook[(string) $hadithNumber] = true;
            $bookRows++;
            $filled++;
            $webFallbacks++;

            if ($bookRows >= $expectedBookRows) {
                break;
            }
        }

        if ($bookRows < $expectedBookRows || $filled > 0) {
            $bookEndpointGaps[] = "{$bookNumber}:{$bookRows}/{$expectedBookRows} (web +{$filled})";
        }
    }

    if ($verbose) {
        echo "book {$bookNumber}: {$bookRows} rows\n";
    }

    usort($bookApiRows, static function (array $left, array $right): int {
        return ($left['_sort'] <=> $right['_sort']) ?: ($left['_seq'] <=> $right['_seq']);
    });
    foreach ($bookApiRows as $row) {
        unset($row['_sort'], $row['_seq']);
        $apiRows[] = $row;
    }

    usleep($delayMs * 1000);
}

$files = sourceFilesBukhari($sourceRoot);
$updated = 0;
$textDiffs = 0;
$metadataDiffs = 0;
$missingApi = max(0, count($files) - count($apiRows));
$missingSource = max(0, count($apiRows) - count($files));

foreach ($files as $index => $file) {
    $api = $apiRows[$index] ?? null;
    if ($api === null) {
        continue;
    }

    $json = json_decode((string) file_get_contents($file), true);
    if (! is_array($json)) {
        continue;
    }

    $oldTexts = $json['hadith_text'] ?? [];
    $textChanged = ($api['en'] !== '' && looseBukhariText((string) ($oldTexts['en'] ?? '')) !== looseBukhariText($api['en']))
        || ($api['ar'] !== '' && looseBukhariText((string) ($oldTexts['ar'] ?? '')) !== looseBukhariText($api['ar']));

    $oldCollection = $json['collections'][0] ?? [];
    $metadataChanged = (string) ($oldCollection['hadith_number'] ?? '') !== $api['hadith_number']
        || (string) ($oldCollection['book_number'] ?? '') !== $api['book_number']
        || apiText((string) ($oldCollection['book_name_en'] ?? '')) !== $api['book_name_en']
        || apiText((string) ($oldCollection['book_name_ar'] ?? '')) !== $api['book_name_ar']
        || (string) ($oldCollection['chapter_number'] ?? '') !== (string) ($api['chapter_number'] ?? '')
        || apiText((string) ($oldCollection['chapter_name_en'] ?? '')) !== $api['chapter_name_en']
        || apiText((string) ($oldCollection['chapter_name_ar'] ?? '')) !== $api['chapter_name_ar']
        || (string) ($oldCollection['source_url'] ?? '') !== $api['source_url'];

    if ($textChanged) {
        $textDiffs++;
    }
    if ($metadataChanged) {
        $metadataDiffs++;
    }

    if (! $apply || (! $textChanged && ! $metadataChanged)) {
        continue;
    }

    $sourceSequence = sourceSequenceNumberBukhari($file);
    $json['hadith_text']['en'] = $api['en'];
    $json['hadith_text']['ar'] = $api['ar'];
    $json['hadith_text']['ar_notashkeel'] = stripArabicMarksBukhari($api['ar']);
    $json['verification_status'] = 'source_verified';
    $json['uncertainty_notes'] = null;
    $json['source_verification']['sunnah'] = [
        'status' => 'source_verified',
        'url' => $api['source_url'],
    ];

    $json['collections'] = [[
        'name_en' => 'Sahih al-Bukhari',
        'name_ar' => 'صحيح البخاري',
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

    $json['metadata']['id'] = $json['metadata']['id'] ?? $sourceSequence;
    $json['metadata']['id_in_book'] = $json['metadata']['id_in_book'] ?? $sourceSequence;
    $json['metadata']['book_id'] = $api['book_number'];
    $json['metadata']['chapter_id'] = $api['chapter_number'];
    $json['metadata']['sunnah_reference'] = [
        'collection' => 'bukhari',
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
        $json['grading']['status'] = strtolower($api['grade_en']) === 'sahih' ? 'sahih' : $api['grade_en'];
    }
    $json['grading']['verified'] = true;

    file_put_contents(
        $file,
        json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n"
    );

    $updated++;
}

echo 'bukhari api_books='.count($books)
    .' api_hadiths='.count($apiRows)
    .' source_hadiths='.count($files)
    ." text_diffs={$textDiffs}"
    ." metadata_diffs={$metadataDiffs}"
    ." updated={$updated}"
    ." individual_fallbacks={$individualFallbacks}"
    ." web_fallbacks={$webFallbacks}"
    ." missing_api={$missingApi}"
    ." missing_source={$missingSource}"
    ."\n";

if ($bookEndpointGaps !== []) {
    echo 'Book endpoint gaps: '.implode(', ', array_slice($bookEndpointGaps, 0, 40)).(count($bookEndpointGaps) > 40 ? ' ...' : '')."\n";
}

exit($missingApi === 0 && $missingSource === 0 ? 0 : 1);
