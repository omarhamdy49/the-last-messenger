<?php

declare(strict_types=1);

/**
 * Fix Riyad as-Salihin production JSON from Sunnah.com API data.
 *
 * Local Riyad files are not in Sunnah book order: hadiths 680+ appear first,
 * while introduction hadiths 1-679 are stored later. Match by canonical Sunnah
 * hadith number, not by filename sequence.
 *
 * Usage:
 *   php scripts/fix_riyad_from_sunnah_api.php
 *   php scripts/fix_riyad_from_sunnah_api.php --apply
 */

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$apply = in_array('--apply', array_slice($argv, 1), true);
$verbose = in_array('--verbose', array_slice($argv, 1), true);
$sourceRoot = dirname(__DIR__, 2).'/prophet-data-generator/hadith/production/riyad_assalihin';
$delayMs = 500;
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

function riyadText(?string $html): string
{
    $text = html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = str_replace(["\xc2\xa0", '“', '”', '‘', '’', '`'], [' ', '"', '"', "'", "'", "'"], $text);

    return trim((string) preg_replace('/\s+/u', ' ', $text));
}

function stripArabicMarksRiyad(string $text): string
{
    $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}\x{06D6}-\x{06ED}]/u', '', $text);

    return trim((string) preg_replace('/[ \t]+/u', ' ', (string) $text));
}

function looseRiyadText(?string $html): string
{
    $text = mb_strtolower(riyadText($html), 'UTF-8');
    $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}\x{06D6}-\x{06ED}]/u', '', (string) $text);

    return (string) preg_replace('/[^\p{L}\p{N}]+/u', '', (string) $text);
}

function getJsonWithRetryRiyad(Client $client, string $uri, array $query = [], int $attempts = 6): object
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

function riyadLangPayload(object $apiHadith, string $lang): array
{
    foreach (($apiHadith->hadith ?? []) as $translation) {
        if (($translation->lang ?? null) !== $lang) {
            continue;
        }

        return [
            'text' => riyadText($translation->body ?? ''),
            'urn' => isset($translation->urn) ? (int) $translation->urn : null,
            'chapter_number' => isset($translation->chapterNumber) ? (string) $translation->chapterNumber : null,
            'chapter_title' => riyadText($translation->chapterTitle ?? ''),
            'grade' => (string) (($translation->grades[0]->grade ?? '') ?: ''),
        ];
    }

    return ['text' => '', 'urn' => null, 'chapter_number' => null, 'chapter_title' => '', 'grade' => ''];
}

function riyadBookName(object $book, string $lang): string
{
    foreach (($book->book ?? []) as $translation) {
        if (($translation->lang ?? null) === $lang) {
            return riyadText($translation->name ?? '');
        }
    }

    return '';
}

function riyadApiRow(object $apiHadith, object $book): array
{
    $en = riyadLangPayload($apiHadith, 'en');
    $ar = riyadLangPayload($apiHadith, 'ar');
    $chapterNumber = $ar['chapter_number'] ?: $en['chapter_number'] ?: (isset($apiHadith->chapterId) ? (string) $apiHadith->chapterId : null);

    return [
        'hadith_number' => (string) $apiHadith->hadithNumber,
        'book_number' => (string) $apiHadith->bookNumber,
        'book_name_en' => riyadBookName($book, 'en'),
        'book_name_ar' => riyadBookName($book, 'ar'),
        'chapter_number' => $chapterNumber,
        'chapter_name_en' => $en['chapter_title'],
        'chapter_name_ar' => $ar['chapter_title'],
        'source_url' => 'https://sunnah.com/riyadussalihin:'.rawurlencode((string) $apiHadith->hadithNumber),
        'en' => $en['text'],
        'ar' => $ar['text'],
        'urn_en' => $en['urn'],
        'urn_ar' => $ar['urn'],
        'grade_en' => $en['grade'],
        'grade_ar' => $ar['grade'],
    ];
}

function riyadSourceFiles(string $sourceRoot): array
{
    $files = glob(rtrim($sourceRoot, '/').'/hadith_*_final.json') ?: [];
    sort($files, SORT_NATURAL);

    return $files;
}

function riyadSourceHadithNumber(array $json): string
{
    $idInBook = $json['metadata']['id_in_book'] ?? null;
    if (is_numeric($idInBook) && (int) $idInBook >= 1 && (int) $idInBook <= 1896) {
        // The generator stored Riyad in source order 680..1896, then 1..679.
        return (string) ((((int) $idInBook + 678) % 1896) + 1);
    }

    if (preg_match('/riyadussalihin:(\d+)/', (string) ($json['collections'][0]['source_url'] ?? ''), $match)) {
        return $match[1];
    }

    return (string) ($json['collections'][0]['hadith_number'] ?? $json['metadata']['sunnah_reference']['hadith_number'] ?? '');
}

function riyadCurrentCandidateNumbers(array $json): array
{
    $numbers = [];
    foreach ([
        $json['collections'][0]['source_url'] ?? '',
        $json['metadata']['sunnah_reference']['url'] ?? '',
    ] as $url) {
        if (preg_match('/riyadussalihin:(\d+)/', (string) $url, $match)) {
            $numbers[] = $match[1];
        }
    }

    foreach ([
        $json['collections'][0]['hadith_number'] ?? '',
        $json['metadata']['sunnah_reference']['hadith_number'] ?? '',
    ] as $number) {
        if (preg_match('/^\d+$/', (string) $number)) {
            $numbers[] = (string) $number;
        }
    }

    return array_values(array_unique($numbers));
}

function riyadCombinedLoose(array $texts): string
{
    return looseRiyadText((string) ($texts['ar'] ?? '')).' '.looseRiyadText((string) ($texts['en'] ?? ''));
}

function riyadTextScore(string $sourceLoose, string $apiLoose): float
{
    if ($sourceLoose === '' || $apiLoose === '') {
        return 0.0;
    }

    $score = 0.0;
    similar_text(mb_substr($sourceLoose, 0, 3000), mb_substr($apiLoose, 0, 3000), $score);

    return $score;
}

$books = [];
$page = 1;
do {
    $response = getJsonWithRetryRiyad($client, 'collections/riyadussalihin/books', ['limit' => 100, 'page' => $page]);
    foreach (($response->data ?? []) as $book) {
        $books[(string) $book->bookNumber] = $book;
    }
    $page = isset($response->next) && $response->next !== null ? (int) $response->next : 0;
    if ($page > 0) {
        usleep($delayMs * 1000);
    }
} while ($page > 0);

$apiRowsByNumber = [];
foreach ($books as $bookNumber => $book) {
    $bookRows = 0;
    $hadithPage = 1;
    do {
        $response = getJsonWithRetryRiyad($client, "collections/riyadussalihin/books/{$bookNumber}/hadiths", [
            'limit' => $limit,
            'page' => $hadithPage,
        ]);

        foreach (($response->data ?? []) as $apiHadith) {
            $row = riyadApiRow($apiHadith, $book);
            if ($row['hadith_number'] !== '') {
                $apiRowsByNumber[$row['hadith_number']] = $row;
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

$files = riyadSourceFiles($sourceRoot);
$sourceByNumber = [];
$invalid = [];
$sourceEntries = [];
$apiLooseByNumber = [];
foreach ($apiRowsByNumber as $hadithNumber => $row) {
    $apiLooseByNumber[$hadithNumber] = riyadCombinedLoose(['ar' => $row['ar'], 'en' => $row['en']]);
}

foreach ($files as $file) {
    $json = json_decode((string) file_get_contents($file), true);
    if (! is_array($json)) {
        $invalid[] = basename($file);

        continue;
    }

    $sourceEntries[] = [
        'file' => $file,
        'json' => $json,
        'loose' => riyadCombinedLoose($json['hadith_text'] ?? []),
        'candidates' => riyadCurrentCandidateNumbers($json),
    ];
}

$claims = [];
foreach ($sourceEntries as $entryIndex => $entry) {
    foreach ($entry['candidates'] as $candidate) {
        if (! isset($apiRowsByNumber[$candidate])) {
            continue;
        }
        $claims[$candidate][] = [
            'entry' => $entryIndex,
            'score' => riyadTextScore($entry['loose'], $apiLooseByNumber[$candidate]),
            'derived_match' => riyadSourceHadithNumber($entry['json']) === $candidate,
        ];
    }
}

$assignedEntries = [];
foreach ($claims as $hadithNumber => $claimRows) {
    usort($claimRows, static function (array $a, array $b): int {
        if ($a['derived_match'] !== $b['derived_match']) {
            return $a['derived_match'] ? -1 : 1;
        }

        return $b['score'] <=> $a['score'];
    });
    $best = $claimRows[0];
    if ($best['score'] >= 70.0 && ! isset($assignedEntries[$best['entry']]) && ! isset($sourceByNumber[$hadithNumber])) {
        $sourceByNumber[$hadithNumber] = $sourceEntries[$best['entry']];
        $sourceByNumber[$hadithNumber]['match_score'] = $best['score'];
        $assignedEntries[$best['entry']] = $hadithNumber;
    }
}

foreach ($sourceEntries as $entryIndex => $entry) {
    if (isset($assignedEntries[$entryIndex])) {
        continue;
    }

    $derived = riyadSourceHadithNumber($entry['json']);
    if ($derived !== '' && isset($apiRowsByNumber[$derived]) && ! isset($sourceByNumber[$derived])) {
        $score = riyadTextScore($entry['loose'], $apiLooseByNumber[$derived]);
        $sourceByNumber[$derived] = $entry;
        $sourceByNumber[$derived]['match_score'] = $score;
        $assignedEntries[$entryIndex] = $derived;
    }
}

$updated = 0;
$textDiffs = 0;
$metadataDiffs = 0;
$missingApi = [];
$missingSource = [];
$lowTextMatches = [];

foreach ($sourceByNumber as $hadithNumber => $entry) {
    $file = $entry['file'];
    $api = $apiRowsByNumber[$hadithNumber] ?? null;
    if ($api === null) {
        $missingApi[] = $hadithNumber;

        continue;
    }

    $json = $entry['json'];

    $oldTexts = $json['hadith_text'] ?? [];
    $sourceLoose = $entry['loose'];
    $apiLoose = $apiLooseByNumber[$hadithNumber];
    $score = $entry['match_score'] ?? riyadTextScore($sourceLoose, $apiLoose);
    if ($score > 0 && $score < 80.0) {
        $lowTextMatches[] = basename($file).":{$api['book_number']}:{$api['hadith_number']}:".round($score, 2);
    }

    $textChanged = ($api['en'] !== '' && looseRiyadText((string) ($oldTexts['en'] ?? '')) !== looseRiyadText($api['en']))
        || ($api['ar'] !== '' && looseRiyadText((string) ($oldTexts['ar'] ?? '')) !== looseRiyadText($api['ar']));
    $oldCollection = $json['collections'][0] ?? [];
    $metadataChanged = (string) ($oldCollection['book_number'] ?? '') !== $api['book_number']
        || riyadText((string) ($oldCollection['book_name_en'] ?? '')) !== $api['book_name_en']
        || riyadText((string) ($oldCollection['book_name_ar'] ?? '')) !== $api['book_name_ar']
        || (string) ($oldCollection['chapter_number'] ?? '') !== (string) ($api['chapter_number'] ?? '')
        || riyadText((string) ($oldCollection['chapter_name_en'] ?? '')) !== $api['chapter_name_en']
        || riyadText((string) ($oldCollection['chapter_name_ar'] ?? '')) !== $api['chapter_name_ar']
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

    $json['hadith_text']['en'] = $api['en'];
    $json['hadith_text']['ar'] = $api['ar'];
    $json['hadith_text']['ar_notashkeel'] = stripArabicMarksRiyad($api['ar']);
    $json['verification_status'] = 'source_verified';
    $json['uncertainty_notes'] = null;
    $json['collections'] = [[
        'name_en' => 'Riyad as-Salihin',
        'name_ar' => 'رياض الصالحين',
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
    $json['metadata']['collection'] = [
        'id' => 16,
        'name_ar' => 'رياض الصالحين',
        'name_en' => 'Riyad as-Salihin',
        'author_ar' => 'الإمام النووي',
        'author_en' => 'Imam an-Nawawi',
        'total_hadiths' => 1896,
    ];
    $json['metadata']['sunnah_reference'] = [
        'collection' => 'riyadussalihin',
        'book_number' => $api['book_number'],
        'hadith_number' => $api['hadith_number'],
        'url' => $api['source_url'],
        'urn' => $api['urn_ar'],
        'urn_en' => $api['urn_en'],
    ];
    $json['chapter_info'] = $api['chapter_number'];
    $json['source_verification']['sunnah'] = [
        'status' => 'source_verified',
        'api_slug' => 'riyadussalihin',
        'hadith_number' => $api['hadith_number'],
        'book_number' => $api['book_number'],
        'url' => $api['source_url'],
    ];

    file_put_contents($file, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
    $updated++;
}

foreach ($apiRowsByNumber as $hadithNumber => $_row) {
    if (! isset($sourceByNumber[$hadithNumber])) {
        $missingSource[] = $hadithNumber;
    }
}

echo 'riyad api_books='.count($books)
    .' api_hadiths='.count($apiRowsByNumber)
    .' source_hadiths='.count($sourceByNumber)
    ." text_diffs={$textDiffs}"
    ." metadata_diffs={$metadataDiffs}"
    ." updated={$updated}"
    .' missing_api='.count($missingApi)
    .' missing_source='.count($missingSource)
    .' low_text_matches='.count($lowTextMatches)
    .' invalid='.count($invalid)
    ."\n";

if ($missingApi !== []) {
    echo 'Missing API for source hadiths: '.implode(', ', array_slice($missingApi, 0, 60)).(count($missingApi) > 60 ? ' ...' : '')."\n";
}
if ($missingSource !== []) {
    echo 'Missing source for API hadiths: '.implode(', ', array_slice($missingSource, 0, 60)).(count($missingSource) > 60 ? ' ...' : '')."\n";
}
if ($lowTextMatches !== []) {
    echo 'Low text matches: '.implode(', ', array_slice($lowTextMatches, 0, 40)).(count($lowTextMatches) > 40 ? ' ...' : '')."\n";
}
if ($invalid !== []) {
    echo 'Invalid source JSON: '.implode(', ', array_slice($invalid, 0, 40)).(count($invalid) > 40 ? ' ...' : '')."\n";
}

exit($missingApi === [] && $missingSource === [] && $invalid === [] ? 0 : 1);
