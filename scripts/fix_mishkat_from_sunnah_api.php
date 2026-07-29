<?php

declare(strict_types=1);

/**
 * Sync Mishkat al-Masabih production JSON from Sunnah.com API data.
 *
 * Sunnah currently exposes Mishkat books introduction, 1-24, and 27-29 through
 * the API. Books 25 and 26 return empty book routes and individual hadiths in
 * that range return 404, even though collection metadata reports a higher
 * totalAvailableHadith. This script syncs every API-accessible row and appends
 * missing rows. Sunnah's Mishkat API intentionally uses some combined hadith
 * numbers such as "20, 21"; those combined references are preserved.
 *
 * Usage:
 *   php scripts/fix_mishkat_from_sunnah_api.php
 *   php scripts/fix_mishkat_from_sunnah_api.php --apply
 */

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$apply = in_array('--apply', array_slice($argv, 1), true);
$verbose = in_array('--verbose', array_slice($argv, 1), true);
$sourceRoot = dirname(__DIR__, 2).'/prophet-data-generator/hadith/production/mishkat_almasabih';
$delayMs = 1200;
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

function mishkatText(?string $html): string
{
    $text = html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = str_replace(["\xc2\xa0", '“', '”', '‘', '’', '`'], [' ', '"', '"', "'", "'", "'"], $text);

    return trim((string) preg_replace('/\s+/u', ' ', $text));
}

function stripArabicMarksMishkat(string $text): string
{
    $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}\x{06D6}-\x{06ED}]/u', '', $text);

    return trim((string) preg_replace('/[ \t]+/u', ' ', (string) $text));
}

function looseMishkatText(?string $html): string
{
    $text = mb_strtolower(mishkatText($html), 'UTF-8');
    $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}\x{06D6}-\x{06ED}]/u', '', (string) $text);

    return (string) preg_replace('/[^\p{L}\p{N}]+/u', '', (string) $text);
}

function mishkatKey(string $bookNumber, string $hadithNumber): string
{
    $hadith = preg_replace('/\s+/u', ' ', trim($hadithNumber));

    return trim($bookNumber).'|'.$hadith;
}

function getJsonWithRetryMishkat(Client $client, string $uri, array $query = [], int $attempts = 7): object
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
            sleep($status === 429 ? 25 * $attempt : 5 * $attempt);
        }
    }

    throw $last;
}

function mishkatLangPayload(object $apiHadith, string $lang): array
{
    foreach (($apiHadith->hadith ?? []) as $translation) {
        if (($translation->lang ?? null) !== $lang) {
            continue;
        }

        return [
            'text' => mishkatText($translation->body ?? ''),
            'urn' => isset($translation->urn) ? (int) $translation->urn : null,
            'chapter_number' => isset($translation->chapterNumber) ? (string) $translation->chapterNumber : null,
            'chapter_title' => mishkatText($translation->chapterTitle ?? ''),
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

function mishkatBookName(object $book, string $lang): string
{
    foreach (($book->book ?? []) as $translation) {
        if (($translation->lang ?? null) === $lang) {
            return mishkatText($translation->name ?? '');
        }
    }

    return '';
}

function mishkatApiRow(object $apiHadith, object $book): array
{
    $en = mishkatLangPayload($apiHadith, 'en');
    $ar = mishkatLangPayload($apiHadith, 'ar');
    $chapterNumber = $ar['chapter_number'] ?: $en['chapter_number'] ?: (isset($apiHadith->chapterId) ? (string) $apiHadith->chapterId : null);

    return [
        'hadith_number' => (string) $apiHadith->hadithNumber,
        'book_number' => (string) $apiHadith->bookNumber,
        'book_name_en' => mishkatBookName($book, 'en'),
        'book_name_ar' => mishkatBookName($book, 'ar'),
        'chapter_number' => $chapterNumber,
        'chapter_name_en' => $en['chapter_title'],
        'chapter_name_ar' => $ar['chapter_title'],
        'source_url' => 'https://sunnah.com/mishkat:'.rawurlencode((string) $apiHadith->hadithNumber),
        'en' => $en['text'],
        'ar' => $ar['text'],
        'urn_en' => $en['urn'],
        'urn_ar' => $ar['urn'],
        'grade_en' => $en['grade'],
        'grade_ar' => $ar['grade'],
    ];
}

function mishkatSourceSequence(string $file): int
{
    if (preg_match('/hadith_(\d+)_final\.json$/', basename($file), $match)) {
        return (int) $match[1];
    }

    return PHP_INT_MAX;
}

function mishkatSourceFiles(string $sourceRoot): array
{
    $files = glob(rtrim($sourceRoot, '/').'/hadith_*_final.json') ?: [];
    usort($files, static fn (string $a, string $b): int => mishkatSourceSequence($a) <=> mishkatSourceSequence($b));

    return $files;
}

function applyMishkatApiRow(array $json, array $api, int $localId, int $totalAvailable): array
{
    $json['hadith_text']['en'] = $api['en'];
    $json['hadith_text']['ar'] = $api['ar'];
    $json['hadith_text']['ar_notashkeel'] = stripArabicMarksMishkat($api['ar']);
    $json['verification_status'] = 'source_verified';
    $json['uncertainty_notes'] = null;
    $json['source_verification']['sunnah'] = [
        'status' => 'source_verified',
        'api_slug' => 'mishkat',
        'hadith_number' => $api['hadith_number'],
        'book_number' => $api['book_number'],
        'url' => $api['source_url'],
    ];

    $json['collections'] = [[
        'name_en' => 'Mishkat al-Masabih',
        'name_ar' => 'مشكاة المصابيح',
        'hadith_number' => $api['hadith_number'],
        'book_number' => $api['book_number'],
        'book_name_en' => $api['book_name_en'],
        'book_name_ar' => $api['book_name_ar'],
        'chapter_number' => $api['chapter_number'],
        'chapter_name_en' => $api['chapter_name_en'],
        'chapter_name_ar' => $api['chapter_name_ar'],
        'source_url' => $api['source_url'],
        'source_verified' => true,
        'verified' => true,
    ]];

    $json['metadata']['id'] = $localId;
    $json['metadata']['id_in_book'] = is_numeric($api['hadith_number']) ? (int) $api['hadith_number'] : $api['hadith_number'];
    $json['metadata']['chapter_id'] = $api['chapter_number'];
    $json['metadata']['book_id'] = $api['book_number'];
    $json['metadata']['collection'] = [
        'id' => 14,
        'name_ar' => 'مشكاة المصابيح',
        'name_en' => 'Mishkat al-Masabih',
        'author_ar' => 'الإمام الكاتب التبريزي',
        'author_en' => 'Al-Khatib Al-Tabrizi',
        'total_hadiths' => $totalAvailable,
    ];
    $json['metadata']['sunnah_reference'] = [
        'collection' => 'mishkat',
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

    return $json;
}

function writeMishkatJson(string $file, array $json): void
{
    file_put_contents(
        $file,
        json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n"
    );
}

$collectionMeta = getJsonWithRetryMishkat($client, 'collections/mishkat');
$totalAvailable = (int) ($collectionMeta->totalAvailableHadith ?? $collectionMeta->totalHadith ?? 0);

$books = [];
$page = 1;
do {
    $response = getJsonWithRetryMishkat($client, 'collections/mishkat/books', ['limit' => 100, 'page' => $page]);
    foreach (($response->data ?? []) as $book) {
        $books[(string) $book->bookNumber] = $book;
    }
    $page = isset($response->next) && $response->next !== null ? (int) $response->next : 0;
    if ($page > 0) {
        usleep($delayMs * 1000);
    }
} while ($page > 0);

$apiRowsByKey = [];
$apiRowsOrdered = [];
foreach ($books as $bookNumber => $book) {
    $bookRows = 0;
    $hadithPage = 1;
    do {
        $response = getJsonWithRetryMishkat($client, "collections/mishkat/books/{$bookNumber}/hadiths", [
            'limit' => $limit,
            'page' => $hadithPage,
        ]);

        foreach (($response->data ?? []) as $apiHadith) {
            $row = mishkatApiRow($apiHadith, $book);
            $key = mishkatKey($row['book_number'], $row['hadith_number']);
            if ($row['hadith_number'] !== '') {
                $apiRowsByKey[$key] = $row;
                $apiRowsOrdered[$key] = $row;
                $bookRows++;
            }
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

$files = mishkatSourceFiles($sourceRoot);
$assigned = [];
$usedApiKeys = [];
$invalid = [];
$extraSource = [];
$textDiffs = 0;
$metadataDiffs = 0;
$updated = 0;
$lowTextMatches = [];
$maxLocalId = 0;
$template = null;

foreach ($files as $file) {
    $maxLocalId = max($maxLocalId, mishkatSourceSequence($file));
    $json = json_decode((string) file_get_contents($file), true);
    if (! is_array($json)) {
        $invalid[] = basename($file);

        continue;
    }
    $template ??= $json;
    $maxLocalId = max($maxLocalId, (int) ($json['metadata']['id'] ?? 0));

    $collection = $json['collections'][0] ?? [];
    $bookNumber = (string) ($collection['book_number'] ?? '');
    $key = mishkatKey($bookNumber, (string) ($collection['hadith_number'] ?? ''));
    if (! isset($apiRowsByKey[$key])) {
        $extraSource[] = basename($file).':'.$bookNumber.':'.($collection['hadith_number'] ?? '');

        continue;
    }

    $assigned[$file] = $key;
    $usedApiKeys[$key] = true;
}

foreach ($assigned as $file => $apiKey) {
    $json = json_decode((string) file_get_contents($file), true);
    if (! is_array($json)) {
        continue;
    }
    $api = $apiRowsByKey[$apiKey];
    $oldCollection = $json['collections'][0] ?? [];
    $oldTexts = $json['hadith_text'] ?? [];
    $sourceLoose = looseMishkatText((string) ($oldTexts['ar'] ?? '')).' '.looseMishkatText((string) ($oldTexts['en'] ?? ''));
    $apiLoose = looseMishkatText($api['ar']).' '.looseMishkatText($api['en']);
    $score = 0.0;
    if ($sourceLoose !== '' && $apiLoose !== '') {
        similar_text(mb_substr($sourceLoose, 0, 3000), mb_substr($apiLoose, 0, 3000), $score);
    }
    if ($score > 0 && $score < 70.0) {
        $lowTextMatches[] = basename($file).":{$api['book_number']}:{$api['hadith_number']}:".round($score, 2);
    }

    $textChanged = ($api['en'] !== '' && looseMishkatText((string) ($oldTexts['en'] ?? '')) !== looseMishkatText($api['en']))
        || ($api['ar'] !== '' && looseMishkatText((string) ($oldTexts['ar'] ?? '')) !== looseMishkatText($api['ar']));
    $metadataChanged = (string) ($oldCollection['hadith_number'] ?? '') !== $api['hadith_number']
        || (string) ($oldCollection['book_number'] ?? '') !== $api['book_number']
        || mishkatText((string) ($oldCollection['book_name_en'] ?? '')) !== $api['book_name_en']
        || mishkatText((string) ($oldCollection['book_name_ar'] ?? '')) !== $api['book_name_ar']
        || (string) ($oldCollection['chapter_number'] ?? '') !== (string) ($api['chapter_number'] ?? '')
        || mishkatText((string) ($oldCollection['chapter_name_en'] ?? '')) !== $api['chapter_name_en']
        || mishkatText((string) ($oldCollection['chapter_name_ar'] ?? '')) !== $api['chapter_name_ar']
        || (string) ($oldCollection['source_url'] ?? '') !== $api['source_url'];

    if ($textChanged) {
        $textDiffs++;
    }
    if ($metadataChanged) {
        $metadataDiffs++;
    }

    if ($apply && ($textChanged || $metadataChanged)) {
        $localId = (int) ($json['metadata']['id'] ?? mishkatSourceSequence($file));
        writeMishkatJson($file, applyMishkatApiRow($json, $api, $localId, $totalAvailable));
        $updated++;
    }
}

$created = 0;
$missingSource = [];
$template ??= [
    'hadith_text' => [],
    'verification_status' => 'source_verified',
    'uncertainty_notes' => null,
    'collections' => [],
    'sanad' => ['narrators' => []],
    'companion' => null,
    'themes_en' => [],
    'themes_ar' => [],
    'keywords' => [],
    'grading' => [],
    'significance_en' => '',
    'significance_ar' => '',
    'related_verses' => [],
    'scholarly_comments' => [],
    'applications' => [],
    'metadata' => [],
    'chapter_info' => null,
    'source_verification' => [],
];

foreach ($apiRowsOrdered as $apiKey => $api) {
    if (isset($usedApiKeys[$apiKey])) {
        continue;
    }

    $missingSource[] = $apiKey;
    if (! $apply) {
        continue;
    }

    $maxLocalId++;
    $newFile = rtrim($sourceRoot, '/')."/hadith_{$maxLocalId}_final.json";
    while (file_exists($newFile)) {
        $maxLocalId++;
        $newFile = rtrim($sourceRoot, '/')."/hadith_{$maxLocalId}_final.json";
    }

    writeMishkatJson($newFile, applyMishkatApiRow($template, $api, $maxLocalId, $totalAvailable));
    $created++;
}

echo 'mishkat api_books='.count($books)
    .' api_hadiths='.count($apiRowsByKey)
    .' source_files='.count($files)
    ." text_diffs={$textDiffs}"
    ." metadata_diffs={$metadataDiffs}"
    ." updated={$updated}"
    .' missing_source='.count($missingSource)
    ." created={$created}"
    .' extra_source='.count($extraSource)
    .' low_text_matches='.count($lowTextMatches)
    .' invalid='.count($invalid)
    .' sunnah_total_available='.$totalAvailable
    ."\n";

if ($missingSource !== []) {
    echo 'Missing source for API rows: '.implode(', ', array_slice($missingSource, 0, 40)).(count($missingSource) > 40 ? ' ...' : '')."\n";
}
if ($extraSource !== []) {
    echo 'Source rows without API rows: '.implode(', ', array_slice($extraSource, 0, 40)).(count($extraSource) > 40 ? ' ...' : '')."\n";
}
if ($lowTextMatches !== []) {
    echo 'Low text matches: '.implode(', ', array_slice($lowTextMatches, 0, 40)).(count($lowTextMatches) > 40 ? ' ...' : '')."\n";
}
if ($invalid !== []) {
    echo 'Invalid source JSON: '.implode(', ', array_slice($invalid, 0, 40)).(count($invalid) > 40 ? ' ...' : '')."\n";
}

exit($invalid === [] && $extraSource === [] ? 0 : 1);
