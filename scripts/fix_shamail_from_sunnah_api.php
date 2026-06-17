<?php

declare(strict_types=1);

/**
 * Fix Ash-Shama'il Al-Muhammadiyah production JSON from Sunnah.com API data.
 *
 * The local source directory is named "shamail_muhammadiyah", but Sunnah.com exposes this
 * collection through the API slug "shamail". Sunnah currently has 402
 * available Ash-Shama'il Al-Muhammadiyah rows. The API books endpoint returns
 * book -8 out of hadith-number order, so fetched rows are sorted by hadith
 * number before mapping to source files.
 *
 * Usage:
 *   php scripts/fix_shamail_from_sunnah_api.php
 *   php scripts/fix_shamail_from_sunnah_api.php --apply
 */

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$apply = in_array('--apply', array_slice($argv, 1), true);
$verbose = in_array('--verbose', array_slice($argv, 1), true);
$sourceRoot = dirname(__DIR__, 2).'/prophet-data-generator/hadith/production/shamail_muhammadiyah';
$delayMs = 600;
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

function shamailText(?string $html): string
{
    $text = html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = str_replace(["\xc2\xa0", '“', '”', '‘', '’', '`'], [' ', '"', '"', "'", "'", "'"], $text);

    return trim((string) preg_replace('/\s+/u', ' ', $text));
}

function stripArabicMarksShamail(string $text): string
{
    $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}\x{06D6}-\x{06ED}]/u', '', $text);

    return trim((string) preg_replace('/[ \t]+/u', ' ', (string) $text));
}

function looseShamailText(?string $html): string
{
    $text = mb_strtolower(shamailText($html), 'UTF-8');
    $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}\x{06D6}-\x{06ED}]/u', '', (string) $text);

    return (string) preg_replace('/[^\p{L}\p{N}]+/u', '', (string) $text);
}

function shamailRefKey(string $bookNumber, string $hadithNumber): string
{
    $hadith = preg_replace('/\s+/u', '', mb_strtolower(trim($hadithNumber), 'UTF-8'));

    return trim($bookNumber).'|'.$hadith;
}

function getJsonWithRetryShamail(Client $client, string $uri, array $query = [], int $attempts = 6): object
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

function shamailLangPayload(object $apiHadith, string $lang): array
{
    foreach (($apiHadith->hadith ?? []) as $translation) {
        if (($translation->lang ?? null) !== $lang) {
            continue;
        }

        return [
            'text' => shamailText($translation->body ?? ''),
            'urn' => isset($translation->urn) ? (int) $translation->urn : null,
            'grade' => (string) (($translation->grades[0]->grade ?? '') ?: ''),
        ];
    }

    return [
        'text' => '',
        'urn' => null,
        'grade' => '',
    ];
}

function shamailBookName(object $book, string $lang): string
{
    foreach (($book->book ?? []) as $translation) {
        if (($translation->lang ?? null) === $lang) {
            return shamailText($translation->name ?? '');
        }
    }

    return '';
}

function shamailApiRow(object $apiHadith, object $book): array
{
    $en = shamailLangPayload($apiHadith, 'en');
    $ar = shamailLangPayload($apiHadith, 'ar');

    return [
        'hadith_number' => (string) $apiHadith->hadithNumber,
        'book_number' => (string) $apiHadith->bookNumber,
        'book_name_en' => shamailBookName($book, 'en'),
        'book_name_ar' => shamailBookName($book, 'ar'),
        'source_url' => 'https://sunnah.com/shamail:'.rawurlencode((string) $apiHadith->hadithNumber),
        'en' => $en['text'],
        'ar' => $ar['text'],
        'urn_en' => $en['urn'],
        'urn_ar' => $ar['urn'],
        'grade_en' => $en['grade'],
        'grade_ar' => $ar['grade'],
    ];
}

function shamailSourceSequence(string $file): int
{
    if (preg_match('/hadith_(\d+)_final\.json$/', basename($file), $match)) {
        return (int) $match[1];
    }

    return PHP_INT_MAX;
}

function shamailSourceFiles(string $sourceRoot): array
{
    $files = glob(rtrim($sourceRoot, '/').'/hadith_*_final.json') ?: [];
    usort($files, static fn (string $a, string $b): int => shamailSourceSequence($a) <=> shamailSourceSequence($b));

    return $files;
}

$books = [];
$page = 1;
do {
    $response = getJsonWithRetryShamail($client, 'collections/shamail/books', ['limit' => 100, 'page' => $page]);
    foreach (($response->data ?? []) as $book) {
        $books[(string) $book->bookNumber] = $book;
    }
    $page = isset($response->next) && $response->next !== null ? (int) $response->next : 0;
    if ($page > 0) {
        usleep($delayMs * 1000);
    }
} while ($page > 0);

$apiRowsOrdered = [];
$apiRowsByHadithNumber = [];
foreach ($books as $bookNumber => $book) {
    $bookRows = 0;
    $hadithPage = 1;
    do {
        $response = getJsonWithRetryShamail($client, "collections/shamail/books/{$bookNumber}/hadiths", [
            'limit' => $limit,
            'page' => $hadithPage,
        ]);

        foreach (($response->data ?? []) as $apiHadith) {
            $row = shamailApiRow($apiHadith, $book);
            $apiRowsOrdered[] = $row;
            $apiRowsByHadithNumber[$row['hadith_number']] = $row;
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

$allFiles = shamailSourceFiles($sourceRoot);
$staleDuplicateBasenames = [
    'hadith_48785_final.json',
    'hadith_48786_final.json',
];
$staleDuplicateFiles = array_values(array_filter(
    $allFiles,
    static fn (string $file): bool => in_array(basename($file), $staleDuplicateBasenames, true),
));
$files = array_values(array_filter(
    $allFiles,
    static fn (string $file): bool => ! in_array(basename($file), $staleDuplicateBasenames, true),
));
$deletedStaleDuplicates = 0;
if ($apply) {
    foreach ($staleDuplicateFiles as $staleFile) {
        if (is_file($staleFile)) {
            unlink($staleFile);
            $deletedStaleDuplicates++;
        }
    }
}
$individualFallbacks = 0;
foreach ($files as $file) {
    $json = json_decode((string) file_get_contents($file), true);
    if (! is_array($json)) {
        continue;
    }
    $collection = $json['collections'][0] ?? [];
    $hadithNumber = (string) ($collection['hadith_number'] ?? $json['metadata']['sunnah_reference']['hadith_number'] ?? '');
    if ($hadithNumber === '' || isset($apiRowsByHadithNumber[$hadithNumber])) {
        continue;
    }

    try {
        $apiHadith = getJsonWithRetryShamail($client, 'collections/shamail/hadiths/'.rawurlencode($hadithNumber));
    } catch (RequestException) {
        continue;
    }

    $book = $books[(string) ($apiHadith->bookNumber ?? '')] ?? (object) ['book' => []];
    $row = shamailApiRow($apiHadith, $book);
    $apiRowsOrdered[] = $row;
    $apiRowsByHadithNumber[$row['hadith_number']] = $row;
    $individualFallbacks++;
    usleep($delayMs * 1000);
}

usort($apiRowsOrdered, static function (array $left, array $right): int {
    $leftNumber = (int) preg_replace('/\D+/', '', $left['hadith_number']);
    $rightNumber = (int) preg_replace('/\D+/', '', $right['hadith_number']);
    if ($leftNumber !== $rightNumber) {
        return $leftNumber <=> $rightNumber;
    }

    return strnatcmp($left['hadith_number'], $right['hadith_number']);
});

$updated = 0;
$deletedExtra = 0;
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
        if ($apply && is_file($file)) {
            unlink($file);
            $deletedExtra++;
        }

        continue;
    }

    $oldCollection = $json['collections'][0] ?? [];
    $oldBook = (string) ($oldCollection['book_number'] ?? $json['metadata']['sunnah_reference']['book_number'] ?? '');
    $oldHadith = (string) ($oldCollection['hadith_number'] ?? $json['metadata']['sunnah_reference']['hadith_number'] ?? '');
    $oldKey = shamailRefKey($oldBook, $oldHadith);
    $apiKey = shamailRefKey($api['book_number'], $api['hadith_number']);
    $seenApiKeys[$apiKey] = true;

    if ($oldKey !== '|' && $oldKey !== $apiKey) {
        $sequenceRemaps++;
        if ($verbose) {
            echo 'sequence remap '.basename($file)." {$oldKey} -> {$apiKey}\n";
        }
    }

    $oldTexts = $json['hadith_text'] ?? [];
    $sourceLoose = looseShamailText((string) ($oldTexts['ar'] ?? '')).' '.looseShamailText((string) ($oldTexts['en'] ?? ''));
    $apiLoose = looseShamailText($api['ar']).' '.looseShamailText($api['en']);
    $score = 0.0;
    if ($sourceLoose !== '' && $apiLoose !== '') {
        similar_text(mb_substr($sourceLoose, 0, 3000), mb_substr($apiLoose, 0, 3000), $score);
    }
    if ($score > 0 && $score < 80.0) {
        $lowTextMatches[] = basename($file).":{$api['book_number']}:{$api['hadith_number']}:".round($score, 2);
    }

    $textChanged = ($api['en'] !== '' && looseShamailText((string) ($oldTexts['en'] ?? '')) !== looseShamailText($api['en']))
        || ($api['ar'] !== '' && looseShamailText((string) ($oldTexts['ar'] ?? '')) !== looseShamailText($api['ar']));

    $metadataChanged = (string) ($oldCollection['hadith_number'] ?? '') !== $api['hadith_number']
        || (string) ($oldCollection['book_number'] ?? '') !== $api['book_number']
        || shamailText((string) ($oldCollection['book_name_en'] ?? '')) !== $api['book_name_en']
        || shamailText((string) ($oldCollection['book_name_ar'] ?? '')) !== $api['book_name_ar']
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
    $json['hadith_text']['ar_notashkeel'] = stripArabicMarksShamail($api['ar']);
    $json['verification_status'] = 'source_verified';
    $json['uncertainty_notes'] = null;
    $json['source_verification']['sunnah'] = [
        'status' => 'source_verified',
        'url' => $api['source_url'],
    ];

    $json['collections'] = [[
        'name_en' => "Ash-Shama'il Al-Muhammadiyah",
        'name_ar' => 'الشمائل المحمدية',
        'hadith_number' => $api['hadith_number'],
        'book_number' => $api['book_number'],
        'book_name_en' => $api['book_name_en'],
        'book_name_ar' => $api['book_name_ar'],
        'chapter_number' => null,
        'chapter_name_en' => '',
        'chapter_name_ar' => '',
        'verified' => true,
        'source_url' => $api['source_url'],
        'source_verified' => true,
    ]];

    $json['metadata']['book_id'] = $api['book_number'];
    $json['metadata']['chapter_id'] = null;
    $json['metadata']['sunnah_reference'] = [
        'collection' => 'shamail',
        'book_number' => $api['book_number'],
        'hadith_number' => $api['hadith_number'],
        'url' => $api['source_url'],
        'urn' => $api['urn_ar'],
        'urn_en' => $api['urn_en'],
    ];
    $json['chapter_info'] = null;

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

echo 'shamail api_books='.count($books)
    .' api_hadiths='.count($apiRowsOrdered)
    ." individual_fallbacks={$individualFallbacks}"
    .' source_hadiths='.count($files)
    .' ignored_stale_duplicates='.count($staleDuplicateFiles)
    ." text_diffs={$textDiffs}"
    ." metadata_diffs={$metadataDiffs}"
    ." updated={$updated}"
    ." deleted_extra=".($deletedExtra + $deletedStaleDuplicates)
    ." sequence_remaps={$sequenceRemaps}"
    .' low_text_matches='.count($lowTextMatches)
    .' invalid='.count($invalid)
    .' missing_api='.count($missingApi)
    .' missing_source='.count($missingSource)
    ."\n";

if ($lowTextMatches !== []) {
    echo 'Low text matches: '.implode(', ', array_slice($lowTextMatches, 0, 40)).(count($lowTextMatches) > 40 ? ' ...' : '')."\n";
}
if ($missingApi !== []) {
    echo 'Extra source rows without API rows: '.implode(', ', array_slice($missingApi, 0, 40)).(count($missingApi) > 40 ? ' ...' : '')."\n";
}
if ($missingSource !== []) {
    echo 'Missing source for API rows: '.implode(', ', array_slice($missingSource, 0, 40)).(count($missingSource) > 40 ? ' ...' : '')."\n";
}
if ($invalid !== []) {
    echo 'Invalid source JSON: '.implode(', ', array_slice($invalid, 0, 40)).(count($invalid) > 40 ? ' ...' : '')."\n";
}

exit($invalid === [] && $missingSource === [] ? 0 : 1);
