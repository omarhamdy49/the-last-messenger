<?php

declare(strict_types=1);

/**
 * Compare local books, chapters, and per-book hadith counts with Sunnah.com.
 *
 * Read-only. Writes reports to storage/app/hadith_sunnah_parity/.
 *
 * Usage:
 *   php scripts/audit_sunnah_books_chapters.php
 *   php scripts/audit_sunnah_books_chapters.php --collections=bukhari,muslim --max-pages=2
 *   php scripts/audit_sunnah_books_chapters.php --delay-ms=750
 */

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\DB;
use Psr\Http\Message\ResponseInterface;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$options = [
    'collections' => 'bukhari,muslim,abudawud,tirmidhi,nasai,ibnmajah,malik',
    'max-pages' => null,
    'delay-ms' => 350,
    'limit' => 100,
];

foreach (array_slice($argv, 1) as $argument) {
    if (! str_starts_with($argument, '--') || ! str_contains($argument, '=')) {
        continue;
    }

    [$key, $optionValue] = explode('=', substr($argument, 2), 2);
    if (array_key_exists($key, $options)) {
        $options[$key] = $optionValue;
    }
}

$collections = array_values(array_filter(array_map(
    static fn (string $collectionName): string => trim($collectionName),
    explode(',', (string) $options['collections'])
)));
$maxPages = $options['max-pages'] === null ? null : max(1, (int) $options['max-pages']);
$delayMs = max(0, (int) $options['delay-ms']);
$limit = min(100, max(1, (int) $options['limit']));

$apiKey = (string) config('sunnah.api.key');
if ($apiKey === '') {
    fwrite(STDERR, "SUNNAH_API_KEY is not configured.\n");
    exit(1);
}

$client = new Client([
    'base_uri' => rtrim((string) config('sunnah.api.base_url'), '/').'/',
    'timeout' => 45,
    'headers' => [
        'Accept' => 'application/json',
        'X-API-Key' => $apiKey,
        'Referer' => 'https://sunnah.com/',
        'User-Agent' => 'TheLastMessengerHadithAudit/1.0',
    ],
]);

$outDir = storage_path('app/hadith_sunnah_parity');
if (! is_dir($outDir)) {
    mkdir($outDir, 0775, true);
}

function plainText(?string $html): string
{
    $text = html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = str_replace(["\xc2\xa0", '“', '”', '‘', '’', '`'], [' ', '"', '"', "'", "'", "'"], $text);

    return trim((string) preg_replace('/\s+/u', ' ', $text));
}

function looseText(?string $html): string
{
    $text = mb_strtolower(plainText($html), 'UTF-8');
    $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}\x{06D6}-\x{06ED}]/u', '', (string) $text);

    return (string) preg_replace('/[^\p{L}\p{N}]+/u', '', (string) $text);
}

function sourceSlugForApiCode(string $collection): string
{
    $map = (array) config('islamic-data.hadith_collection_map');

    return (string) ($map[$collection] ?? $collection);
}

function sunnahApiCollectionSlug(string $collection): string
{
    return match ($collection) {
        'ahmed' => 'ahmad',
        'aladab_almufrad' => 'adab',
        default => $collection,
    };
}

function throttleRequests(int $delayMs): void
{
    static $lastRequestAt = 0.0;

    $remainingMs = $delayMs - ((microtime(true) - $lastRequestAt) * 1000);
    if ($lastRequestAt > 0 && $remainingMs > 0) {
        usleep((int) ceil($remainingMs) * 1000);
    }

    $lastRequestAt = microtime(true);
}

function getWithRetry(
    Client $client,
    string $uri,
    array $query,
    int $delayMs,
    int $attempts = 4,
): ResponseInterface {
    $lastException = new RuntimeException("Request failed: {$uri}");
    for ($attempt = 1; $attempt <= $attempts; $attempt++) {
        try {
            throttleRequests($delayMs);

            return $client->get($uri, ['query' => $query]);
        } catch (RequestException $exception) {
            $lastException = $exception;
            $status = $exception->hasResponse() ? $exception->getResponse()->getStatusCode() : null;
            if ($status !== 429 || $attempt === $attempts) {
                throw $exception;
            }
            sleep(15 * $attempt);
        }
    }

    throw $lastException;
}

function requestFailure(RequestException|JsonException|UnexpectedValueException $exception): array
{
    return [
        'status' => $exception instanceof RequestException && $exception->hasResponse()
            ? $exception->getResponse()->getStatusCode()
            : null,
        'message' => $exception->getMessage(),
    ];
}

function fetchApiPage(Client $client, string $uri, int $limit, int $page, int $delayMs): object
{
    $response = getWithRetry($client, $uri, ['limit' => $limit, 'page' => $page], $delayMs);
    $payload = json_decode((string) $response->getBody(), false, 512, JSON_THROW_ON_ERROR);
    if (! isset($payload->data) || ! is_array($payload->data)) {
        throw new UnexpectedValueException("Malformed API response for {$uri}: data is not an array");
    }

    return $payload;
}

function fetchPages(Client $client, string $uri, int $limit, ?int $maxPages, int $delayMs): array
{
    $pageScan = ['rows' => [], 'total' => null, 'complete' => true, 'limited' => false, 'error' => null];
    $page = 1;

    do {
        try {
            $payload = fetchApiPage($client, $uri, $limit, $page, $delayMs);
        } catch (RequestException|JsonException|UnexpectedValueException $exception) {
            $pageScan['complete'] = false;
            $pageScan['error'] = requestFailure($exception);
            break;
        }

        $pageScan['rows'] = array_merge($pageScan['rows'], $payload->data);
        $pageScan['total'] ??= isset($payload->total) ? (int) $payload->total : null;
        $next = $payload->next ?? null;
        if ($next !== null && $maxPages !== null && $page >= $maxPages) {
            $pageScan['complete'] = false;
            $pageScan['limited'] = true;
            break;
        }
        $page = $next === null ? 0 : (is_numeric($next) ? (int) $next : $page + 1);
    } while ($page > 0);

    return $pageScan;
}

function localBooks(string $sourceSlug): array
{
    $rows = DB::table('hadith_books as b')
        ->join('hadith_collections as c', 'c.id', '=', 'b.collection_id')
        ->leftJoin('hadith_hadiths as h', 'h.book_id', '=', 'b.id')
        ->where('c.source_slug', $sourceSlug)
        ->select(['b.id', 'b.book_number', 'b.title_ar', 'b.title_en'])
        ->selectRaw('COUNT(h.id) as hadith_count')
        ->groupBy('b.id', 'b.book_number', 'b.title_ar', 'b.title_en')
        ->orderByRaw('CAST(b.book_number AS UNSIGNED), b.book_number')
        ->get();

    return $rows->values()->map(
        static fn (object $row, int $position): array => [
            'position' => $position,
            'id' => (int) $row->id,
            'number' => (string) ($row->book_number ?? ''),
            'names' => ['ar' => (string) ($row->title_ar ?? ''), 'en' => (string) ($row->title_en ?? '')],
            'hadith_count' => (int) $row->hadith_count,
        ]
    )->all();
}

function decodeLocalNames(mixed $names): array
{
    if (is_string($names)) {
        $names = json_decode($names, true) ?: [];
    } elseif (is_object($names)) {
        $names = (array) $names;
    }

    return [
        'ar' => (string) ($names['ar'] ?? ''),
        'en' => (string) ($names['en'] ?? ''),
    ];
}

function localChapters(string $sourceSlug): array
{
    $rows = DB::table('hadith_chapters as ch')
        ->join('hadith_books as b', 'b.id', '=', 'ch.book_id')
        ->join('hadith_collections as c', 'c.id', '=', 'b.collection_id')
        ->where('c.source_slug', $sourceSlug)
        ->select(['ch.id', 'ch.book_id', 'ch.chapter_number', 'ch.name'])
        ->orderBy('ch.book_id')
        ->orderByRaw('CAST(ch.chapter_number AS DECIMAL(20, 6)), ch.chapter_number')
        ->get();

    $byBook = [];
    foreach ($rows as $row) {
        $byBook[(int) $row->book_id][] = [
            'id' => (int) $row->id,
            'number' => (string) ($row->chapter_number ?? ''),
            'names' => decodeLocalNames($row->name),
        ];
    }

    return $byBook;
}

function apiNames(object $record, string $container, string $field): array
{
    $names = ['ar' => '', 'en' => ''];
    foreach (($record->{$container} ?? []) as $translation) {
        $language = (string) ($translation->lang ?? '');
        if (array_key_exists($language, $names)) {
            $names[$language] = (string) ($translation->{$field} ?? '');
        }
    }

    return $names;
}

function apiBooks(array $rows): array
{
    return array_values(array_map(
        static fn (object $row, int $position): array => [
            'position' => $position,
            'number' => (string) ($row->bookNumber ?? ''),
            'names' => apiNames($row, 'book', 'name'),
            'hadith_count' => isset($row->numberOfHadith) ? (int) $row->numberOfHadith : null,
        ],
        $rows,
        array_keys($rows),
    ));
}

function apiChapters(array $rows): array
{
    return array_values(array_map(
        static fn (object $row): array => [
            'number' => (string) ($row->chapterId ?? ''),
            'names' => apiNames($row, 'chapter', 'chapterTitle'),
        ],
        $rows,
    ));
}

function recordSignature(array $record): string
{
    $arabic = looseText($record['names']['ar']);
    $english = looseText($record['names']['en']);

    return $arabic === '' && $english === '' ? '' : "{$arabic}|{$english}";
}

function pairBy(
    array $localRecords,
    array $apiRecords,
    callable $keyFor,
    array &$usedLocal,
    array &$usedApi,
): array {
    $localIndexes = [];
    foreach ($localRecords as $index => $record) {
        if (! isset($usedLocal[$index]) && ($key = $keyFor($record)) !== '') {
            $localIndexes[$key][] = $index;
        }
    }

    $pairs = [];
    foreach ($apiRecords as $index => $record) {
        $key = $keyFor($record);
        if (isset($usedApi[$index]) || $key === '' || empty($localIndexes[$key])) {
            continue;
        }
        $localIndex = array_shift($localIndexes[$key]);
        $usedLocal[$localIndex] = $usedApi[$index] = true;
        $pairs[] = ['local' => $localRecords[$localIndex], 'api' => $record];
    }

    return $pairs;
}

/**
 * Book and chapter numbers are the same identifier in two notations: the API
 * returns them as decimal strings ("1.00", "12.30") while they are stored here
 * without trailing zeros ("1", "12.3"). Comparing the raw strings reports every
 * chapter in the corpus as a mismatch, so they are keyed on their numeric value.
 * Non-numeric numbers (a few collections use lettered sections) fall back to a
 * trimmed string so they still pair with themselves.
 */
function normalizeRecordNumber(string $number): string
{
    $trimmed = trim($number);

    if ($trimmed === '' || ! is_numeric($trimmed)) {
        return $trimmed;
    }

    return rtrim(rtrim(number_format((float) $trimmed, 6, '.', ''), '0'), '.');
}

function pairRecords(array $localRecords, array $apiRecords): array
{
    $usedLocal = $usedApi = [];
    $sameNumber = pairBy(
        $localRecords,
        $apiRecords,
        static fn (array $record): string => normalizeRecordNumber($record['number']),
        $usedLocal,
        $usedApi,
    );
    $numberMismatch = pairBy($localRecords, $apiRecords, 'recordSignature', $usedLocal, $usedApi);

    return [
        'same_number' => $sameNumber,
        'number_mismatch' => $numberMismatch,
        'missing_local' => array_values(array_diff_key($apiRecords, $usedApi)),
        'extra_local' => array_values(array_diff_key($localRecords, $usedLocal)),
    ];
}

function addIssue(
    array &$summary,
    array &$issues,
    string $counter,
    string $type,
    array $identifiers,
): void {
    $summary[$counter]++;
    $issues[] = ['collection' => $summary['collection'], 'type' => $type] + $identifiers;
}

function compareNames(
    array $local,
    array $api,
    string $counterPrefix,
    string $fieldPrefix,
    array $identifiers,
    array &$summary,
    array &$issues,
): void {
    foreach (['ar', 'en'] as $language) {
        if (looseText($local['names'][$language]) === looseText($api['names'][$language])) {
            continue;
        }
        addIssue($summary, $issues, "{$counterPrefix}_{$language}", "{$fieldPrefix}_mismatch", $identifiers + [
            'what_differs' => "{$fieldPrefix}_{$language}",
            'language' => $language,
            'local_value' => plainText($local['names'][$language]),
            'api_value' => plainText($api['names'][$language]),
        ]);
    }
}

function compareHadithCounts(
    array $local,
    array $api,
    array &$summary,
    array &$issues,
): void {
    $identifiers = ['book_number' => $api['number'], 'local_book_id' => $local['id']];
    if ($api['hadith_count'] === null) {
        addIssue($summary, $issues, 'hadith_count_unavailable', 'hadith_count_unavailable', $identifiers + [
            'what_differs' => 'hadith_count',
            'local_value' => $local['hadith_count'],
            'api_value' => null,
        ]);

        return;
    }
    if ($local['hadith_count'] !== $api['hadith_count']) {
        addIssue($summary, $issues, 'hadith_count_mismatches', 'hadith_count_mismatch', $identifiers + [
            'what_differs' => 'hadith_count',
            'local_value' => $local['hadith_count'],
            'api_value' => $api['hadith_count'],
        ]);
    }
}

function compareBookPairs(array $pairs, array &$summary, array &$issues, bool $numberMismatch): void
{
    foreach ($pairs as $pair) {
        $identifiers = ['book_number' => $pair['api']['number'], 'local_book_id' => $pair['local']['id']];
        if ($numberMismatch) {
            addIssue($summary, $issues, 'book_number_mismatches', 'book_number_mismatch', $identifiers + [
                'what_differs' => 'book_number',
                'local_value' => $pair['local']['number'],
                'api_value' => $pair['api']['number'],
            ]);
        }
        compareNames(
            $pair['local'],
            $pair['api'],
            'book_title_mismatches',
            'book_title',
            $identifiers,
            $summary,
            $issues,
        );
        compareHadithCounts($pair['local'], $pair['api'], $summary, $issues);
    }
}

function compareBooks(array $matches, array &$summary, array &$issues): array
{
    compareBookPairs($matches['same_number'], $summary, $issues, false);
    compareBookPairs($matches['number_mismatch'], $summary, $issues, true);

    foreach ($matches['missing_local'] as $api) {
        addIssue($summary, $issues, 'books_missing_local', 'book_missing_locally', [
            'book_number' => $api['number'],
            'what_differs' => 'presence',
            'local_value' => null,
            'api_value' => $api['names'],
        ]);
    }

    return pairedLocalBooks($matches);
}

function recordExtraLocalBooks(array $extraBooks, array &$summary, array &$issues): void
{
    foreach ($extraBooks as $local) {
        addIssue($summary, $issues, 'books_extra_local', 'book_extra_locally', [
            'book_number' => $local['number'],
            'local_book_id' => $local['id'],
            'what_differs' => 'presence',
            'local_value' => $local['names'],
            'api_value' => null,
        ]);
    }
}

function pairedLocalBooks(array $matches): array
{
    $paired = [];
    foreach (array_merge($matches['same_number'], $matches['number_mismatch']) as $pair) {
        $paired[$pair['api']['position']] = $pair['local'];
    }

    return $paired;
}

function compareChapterPairs(array $pairs, string $apiBookNumber, array &$summary, array &$issues): void
{
    foreach ($pairs as $pair) {
        $identifiers = [
            'book_number' => $apiBookNumber,
            'chapter_number' => $pair['api']['number'],
            'local_chapter_id' => $pair['local']['id'] ?? null,
        ];
        compareNames(
            $pair['local'],
            $pair['api'],
            'chapter_name_mismatches',
            'chapter_name',
            $identifiers,
            $summary,
            $issues,
        );
    }
}

function recordChapterPresenceIssues(
    array $matches,
    string $apiBookNumber,
    bool $recordExtras,
    array &$summary,
    array &$issues,
): void {
    foreach ($matches['missing_local'] as $api) {
        addIssue($summary, $issues, 'chapters_missing_local', 'chapter_missing_locally', [
            'book_number' => $apiBookNumber,
            'chapter_number' => $api['number'],
            'what_differs' => 'presence',
            'local_value' => null,
            'api_value' => $api['names'],
        ]);
    }
    if (! $recordExtras) {
        return;
    }
    foreach ($matches['extra_local'] as $local) {
        recordExtraChapter($local, $apiBookNumber, $summary, $issues);
    }
}

function recordExtraChapter(array $local, string $bookNumber, array &$summary, array &$issues): void
{
    addIssue($summary, $issues, 'chapters_extra_local', 'chapter_extra_locally', [
        'book_number' => $bookNumber,
        'chapter_number' => $local['number'],
        'local_chapter_id' => $local['id'],
        'what_differs' => 'presence',
        'local_value' => $local['names'],
        'api_value' => null,
    ]);
}

function compareChapters(
    array $localRows,
    array $apiRows,
    string $apiBookNumber,
    bool $recordExtras,
    array &$summary,
    array &$issues,
): void {
    $matches = pairRecords($localRows, $apiRows);
    compareChapterPairs($matches['same_number'], $apiBookNumber, $summary, $issues);
    compareChapterPairs($matches['number_mismatch'], $apiBookNumber, $summary, $issues);

    foreach ($matches['number_mismatch'] as $pair) {
        addIssue($summary, $issues, 'chapter_number_mismatches', 'chapter_number_mismatch', [
            'book_number' => $apiBookNumber,
            'chapter_number' => $pair['api']['number'],
            'local_chapter_id' => $pair['local']['id'] ?? null,
            'what_differs' => 'chapter_number',
            'local_value' => $pair['local']['number'],
            'api_value' => $pair['api']['number'],
        ]);
    }

    recordChapterPresenceIssues($matches, $apiBookNumber, $recordExtras, $summary, $issues);
}

function auditBookChapters(
    Client $client,
    string $apiCollection,
    array $apiBook,
    ?array $localBook,
    array $chaptersByBook,
    array $paging,
    array &$summary,
    array &$issues,
): void {
    $localRows = $localBook === null ? [] : ($chaptersByBook[$localBook['id']] ?? []);
    $uri = "collections/{$apiCollection}/books/{$apiBook['number']}/chapters";
    $chapterPages = fetchPages($client, $uri, $paging['limit'], $paging['max_pages'], $paging['delay_ms']);
    $summary['api_chapters_scanned'] += count($chapterPages['rows']);

    recordPageState($chapterPages, 'chapter', $apiBook['number'], $localRows, $summary, $issues);
    $apiRows = apiChapters($chapterPages['rows']);
    if ($apiRows === []) {
        return;
    }

    compareChapters($localRows, $apiRows, $apiBook['number'], $chapterPages['complete'], $summary, $issues);
}

function recordFailedOrLimitedPage(
    array $pageScan,
    string $scope,
    array $identifiers,
    int $localCount,
    array &$summary,
    array &$issues,
): void {
    if ($pageScan['error'] !== null) {
        addIssue($summary, $issues, 'request_failures', "{$scope}_fetch_failed", $identifiers + [
            'what_differs' => 'api_request',
            'local_value' => null,
            'api_value' => $pageScan['error'],
        ]);
    }
    if ($pageScan['limited']) {
        addIssue($summary, $issues, 'scan_limited', "{$scope}_scan_limited", $identifiers + [
            'what_differs' => 'pagination',
            'local_value' => $localCount,
            'api_value' => count($pageScan['rows']),
        ]);
    }
}

function recordPageState(
    array $pageScan,
    string $scope,
    ?string $bookNumber,
    array $localRows,
    array &$summary,
    array &$issues,
): void {
    $identifiers = ['book_number' => $bookNumber, 'chapter_number' => null];
    recordFailedOrLimitedPage($pageScan, $scope, $identifiers, count($localRows), $summary, $issues);
    if ($scope === 'chapter' && $pageScan['error'] === null && $pageScan['rows'] === [] && $localRows !== []) {
        addIssue($summary, $issues, 'chapter_api_empty', 'chapter_api_returned_nothing', $identifiers + [
            'what_differs' => 'api_availability',
            'local_value' => count($localRows),
            'api_value' => 0,
        ]);
    }
}

function recordExtraBookChapters(
    array $extraBooks,
    array $chaptersByBook,
    array &$summary,
    array &$issues,
): void {
    foreach ($extraBooks as $book) {
        foreach ($chaptersByBook[$book['id']] ?? [] as $chapter) {
            recordExtraChapter($chapter, $book['number'], $summary, $issues);
        }
    }
}

function auditAllChapters(
    Client $client,
    string $apiCollection,
    array $apiBookRows,
    array $pairedLocal,
    array $matches,
    array $chaptersByBook,
    array $paging,
    array &$summary,
    array &$issues,
): void {
    foreach ($apiBookRows as $apiBook) {
        auditBookChapters(
            $client,
            $apiCollection,
            $apiBook,
            $pairedLocal[$apiBook['position']] ?? null,
            $chaptersByBook,
            $paging,
            $summary,
            $issues,
        );
    }

    if ($summary['book_list_complete']) {
        recordExtraBookChapters($matches['extra_local'], $chaptersByBook, $summary, $issues);
    }
}

function newSummary(string $collection, string $sourceSlug, int $bookCount, int $chapterCount): array
{
    return [
        'collection' => $collection,
        'source_slug' => $sourceSlug,
        'status' => 'pending',
        'local_books' => $bookCount,
        'api_books_scanned' => 0,
        'api_book_total' => null,
        'book_list_complete' => false,
        'local_chapters' => $chapterCount,
        'api_chapters_scanned' => 0,
        'books_missing_local' => 0,
        'books_extra_local' => 0,
        'book_number_mismatches' => 0,
        'book_title_mismatches_ar' => 0,
        'book_title_mismatches_en' => 0,
        'chapters_missing_local' => 0,
        'chapters_extra_local' => 0,
        'chapter_number_mismatches' => 0,
        'chapter_name_mismatches_ar' => 0,
        'chapter_name_mismatches_en' => 0,
        'hadith_count_mismatches' => 0,
        'hadith_count_unavailable' => 0,
        'chapter_api_empty' => 0,
        'request_failures' => 0,
        'scan_limited' => 0,
    ];
}

function discrepancyTotal(array $summary): int
{
    $keys = [
        'books_missing_local', 'books_extra_local', 'book_number_mismatches',
        'book_title_mismatches_ar', 'book_title_mismatches_en',
        'chapters_missing_local', 'chapters_extra_local', 'chapter_number_mismatches',
        'chapter_name_mismatches_ar', 'chapter_name_mismatches_en',
        'hadith_count_mismatches',
    ];

    return array_sum(array_intersect_key($summary, array_flip($keys)));
}

function finalizeSummary(array &$summary): void
{
    if ($summary['status'] === 'api_returned_nothing') {
        return;
    }
    $incomplete = $summary['request_failures'] + $summary['scan_limited']
        + $summary['chapter_api_empty'] + $summary['hadith_count_unavailable'];
    $summary['status'] = $incomplete > 0
        ? 'inconclusive'
        : (discrepancyTotal($summary) > 0 ? 'fail' : 'pass');
}

function collectionWithoutApiRows(array $summary, array $booksResult): array
{
    $summary['status'] = 'api_returned_nothing';
    $issues = [[
        'collection' => $summary['collection'],
        'type' => 'collection_api_returned_nothing',
        'book_number' => null,
        'chapter_number' => null,
        'what_differs' => 'api_availability',
        'local_value' => $summary['local_books'],
        'api_value' => $booksResult['total'] ?? 0,
    ]];

    return [$summary, $issues];
}

function applyBookListResult(
    array $booksResult,
    array $localBookRows,
    array &$summary,
    array &$issues,
): void {
    $summary['api_books_scanned'] = count($booksResult['rows']);
    $summary['api_book_total'] = $booksResult['total'];
    $summary['book_list_complete'] = $booksResult['complete'];
    recordPageState($booksResult, 'book_list', null, $localBookRows, $summary, $issues);
}

function terminalCollectionResult(array $booksResult, array &$summary, array $issues): ?array
{
    if ($booksResult['rows'] === [] && ! $booksResult['complete']) {
        finalizeSummary($summary);

        return [$summary, $issues];
    }
    if ($booksResult['rows'] === []) {
        return collectionWithoutApiRows($summary, $booksResult);
    }

    return null;
}

function fetchBookPages(Client $client, string $apiCollection, array $paging): array
{
    return fetchPages(
        $client,
        "collections/{$apiCollection}/books",
        $paging['limit'],
        $paging['max_pages'],
        $paging['delay_ms'],
    );
}

function auditCollection(Client $client, string $collection, array $paging): array
{
    $sourceSlug = sourceSlugForApiCode($collection);
    $localBookRows = localBooks($sourceSlug);
    $chaptersByBook = localChapters($sourceSlug);
    $summary = newSummary($collection, $sourceSlug, count($localBookRows), array_sum(array_map('count', $chaptersByBook)));
    $issues = [];
    $apiCollection = sunnahApiCollectionSlug($collection);
    $booksResult = fetchBookPages($client, $apiCollection, $paging);
    applyBookListResult($booksResult, $localBookRows, $summary, $issues);
    $terminalResult = terminalCollectionResult($booksResult, $summary, $issues);
    if ($terminalResult !== null) {
        return $terminalResult;
    }

    $apiBookRows = apiBooks($booksResult['rows']);
    $matches = pairRecords($localBookRows, $apiBookRows);
    $pairedLocal = compareBooks($matches, $summary, $issues);
    if ($summary['book_list_complete']) {
        recordExtraLocalBooks($matches['extra_local'], $summary, $issues);
    }
    auditAllChapters($client, $apiCollection, $apiBookRows, $pairedLocal, $matches, $chaptersByBook, $paging, $summary, $issues);
    finalizeSummary($summary);

    return [$summary, $issues];
}

function printTable(array $summaries): void
{
    echo "\n";
    echo "Collection         Status               BkMiss BkExtra BkNum BkAr BkEn ChMiss ChExtra ChNum ChAr ChEn Count Req\n";
    echo "---------------------------------------------------------------------------------------------------------------\n";
    foreach ($summaries as $summary) {
        printf(
            "%-18s %-20s %6d %7d %5d %4d %4d %6d %7d %5d %4d %4d %5d %3d\n",
            $summary['collection'],
            $summary['status'],
            $summary['books_missing_local'],
            $summary['books_extra_local'],
            $summary['book_number_mismatches'],
            $summary['book_title_mismatches_ar'],
            $summary['book_title_mismatches_en'],
            $summary['chapters_missing_local'],
            $summary['chapters_extra_local'],
            $summary['chapter_number_mismatches'],
            $summary['chapter_name_mismatches_ar'],
            $summary['chapter_name_mismatches_en'],
            $summary['hadith_count_mismatches'],
            $summary['request_failures'],
        );
    }
}

function verdict(array $summaries): string
{
    if (array_filter($summaries, static fn (array $summary): bool => in_array(
        $summary['status'],
        ['inconclusive', 'api_returned_nothing'],
        true,
    ))) {
        return 'INCONCLUSIVE — one or more collections could not be fully audited.';
    }
    if (array_sum(array_map('discrepancyTotal', $summaries)) > 0) {
        return 'FAIL — books/chapters parity discrepancies found.';
    }

    return 'PASS — no books/chapters parity discrepancies found.';
}

$paging = ['limit' => $limit, 'max_pages' => $maxPages, 'delay_ms' => $delayMs];
$allSummaries = [];
$allIssues = [];

foreach ($collections as $collection) {
    [$summary, $issues] = auditCollection($client, $collection, $paging);
    $allSummaries[] = $summary;
    $allIssues[$collection] = $issues;
}

file_put_contents(
    "{$outDir}/books_chapters_summary.json",
    json_encode($allSummaries, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
);
file_put_contents(
    "{$outDir}/books_chapters_issues.json",
    json_encode($allIssues, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
);

printTable($allSummaries);
echo "\nVERDICT: ".verdict($allSummaries)."\n";
echo "Summary: {$outDir}/books_chapters_summary.json\n";
echo "Issues:  {$outDir}/books_chapters_issues.json\n";
