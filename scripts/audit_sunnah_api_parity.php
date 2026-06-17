<?php

declare(strict_types=1);

/**
 * Compare local hadith rows against live Sunnah.com API data.
 *
 * Read-only. Writes reports to storage/app/hadith_sunnah_parity/.
 *
 * Usage:
 *   php scripts/audit_sunnah_api_parity.php
 *   php scripts/audit_sunnah_api_parity.php --collections=bukhari,muslim --max-pages=2
 *   php scripts/audit_sunnah_api_parity.php --delay-ms=750
 */

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$options = [
    'collections' => 'bukhari,muslim,abudawud,tirmidhi,nasai,ibnmajah,malik',
    'max-pages' => null,
    'delay-ms' => 350,
    'limit' => 100,
];

foreach (array_slice($argv, 1) as $arg) {
    if (! str_starts_with($arg, '--') || ! str_contains($arg, '=')) {
        continue;
    }

    [$key, $value] = explode('=', substr($arg, 2), 2);
    if (array_key_exists($key, $options)) {
        $options[$key] = $value;
    }
}

$collections = array_values(array_filter(array_map(
    static fn (string $value): string => trim($value),
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

$webClient = new Client([
    'base_uri' => 'https://sunnah.com/',
    'timeout' => 45,
    'headers' => [
        'Accept' => 'text/html',
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

function textScore(string $left, string $right): float
{
    if ($left === '' || $right === '') {
        return 0.0;
    }
    if ($left === $right || str_contains($left, $right) || str_contains($right, $left)) {
        return 100.0;
    }

    similar_text(mb_substr($left, 0, 3000), mb_substr($right, 0, 3000), $percent);

    return (float) $percent;
}

function apiLangPayload(object $apiHadith, string $lang): array
{
    foreach (($apiHadith->hadith ?? []) as $translation) {
        if (($translation->lang ?? null) !== $lang) {
            continue;
        }

        return [
            'text' => (string) ($translation->body ?? ''),
            'urn' => isset($translation->urn) ? (int) $translation->urn : null,
        ];
    }

    return ['text' => '', 'urn' => null];
}

function getWithRetry(Client $client, string $uri, array $options = [], int $attempts = 4)
{
    $last = null;
    for ($attempt = 1; $attempt <= $attempts; $attempt++) {
        try {
            return $client->get($uri, $options);
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

function webGetWithRetry(Client $client, string $uri, int $attempts = 3)
{
    $last = null;
    for ($attempt = 1; $attempt <= $attempts; $attempt++) {
        try {
            return $client->get($uri);
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

function webHadithPayload(Client $webClient, string $collection, string $hadithNumber, string $bookNumber = ''): ?object
{
    $response = webGetWithRetry($webClient, 'https://sunnah.com/'.rawurlencode($collection).':'.rawurlencode($hadithNumber));
    $html = (string) $response->getBody();

    $english = '';
    if (preg_match('/<div class="english_hadith_full">(.*?)<div class=clear><\/div>/s', $html, $match)) {
        $english = html_entity_decode(strip_tags($match[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    $arabic = '';
    if (preg_match('/<div class="arabic_hadith_full arabic">(.*?)<\/div>/s', $html, $match)) {
        $arabic = html_entity_decode(strip_tags($match[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    if (trim($english) === '' && trim($arabic) === '') {
        return null;
    }

    return (object) [
        'bookNumber' => $bookNumber,
        'hadithNumber' => $hadithNumber,
        'hadith' => [
            (object) ['lang' => 'en', 'body' => $english, 'urn' => null],
            (object) ['lang' => 'ar', 'body' => $arabic, 'urn' => null],
        ],
    ];
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

function localRowsForCollection(string $sourceSlug): array
{
    $rows = DB::table('hadith_hadiths as h')
        ->join('hadith_books as b', 'b.id', '=', 'h.book_id')
        ->join('hadith_collections as c', 'c.id', '=', 'b.collection_id')
        ->leftJoin('hadith_translations as ar', function ($join): void {
            $join->on('ar.hadith_id', '=', 'h.id')->where('ar.language_code', '=', 'ar');
        })
        ->leftJoin('hadith_translations as en', function ($join): void {
            $join->on('en.hadith_id', '=', 'h.id')->where('en.language_code', '=', 'en');
        })
        ->where('c.source_slug', $sourceSlug)
        ->select([
            'h.id',
            'h.hadith_number',
            'h.source_file',
            'h.source_url',
            'h.sunnah_urn_ar',
            'h.sunnah_urn_en',
            'b.book_number',
            'b.title_en as book_title_en',
            'ar.text as ar_text',
            'ar.urn as ar_urn',
            'en.text as en_text',
            'en.urn as en_urn',
        ])
        ->get();

    $byKey = [];
    $duplicates = [];
    foreach ($rows as $row) {
        $key = ((string) $row->book_number).'|'.((string) $row->hadith_number);
        if (isset($byKey[$key])) {
            $duplicates[] = [
                'key' => $key,
                'first_id' => $byKey[$key]->id,
                'duplicate_id' => $row->id,
            ];

            continue;
        }

        $byKey[$key] = $row;
    }

    return [$byKey, $duplicates, $rows->count()];
}

function localBookNumbersForCollection(string $sourceSlug): array
{
    return DB::table('hadith_books as b')
        ->join('hadith_collections as c', 'c.id', '=', 'b.collection_id')
        ->where('c.source_slug', $sourceSlug)
        ->whereNotNull('b.book_number')
        ->where('b.book_number', '<>', '')
        ->where('b.book_number', '<>', '0')
        ->distinct()
        ->orderByRaw('CAST(b.book_number AS UNSIGNED), b.book_number')
        ->pluck('b.book_number')
        ->map(static fn ($value): string => (string) $value)
        ->all();
}

$allSummaries = [];
$allIssues = [];

foreach ($collections as $collection) {
    $sourceSlug = sourceSlugForApiCode($collection);
    $apiCollection = sunnahApiCollectionSlug($collection);
    [$localByKey, $duplicateLocalKeys, $localCount] = localRowsForCollection($sourceSlug);
    $bookNumbers = localBookNumbersForCollection($sourceSlug);
    if (in_array($collection, ['nawawi40', 'qudsi40', 'shahwaliullah40'], true)) {
        $bookNumbers = ['__individual__'];
    } elseif ($collection === 'malik') {
        // Sunnah API currently exposes Malik collection metadata, but the book
        // hadith route returns empty data and the individual route returns 404.
        // Use the canonical web pages for text parity until the API route works.
        $bookNumbers = ['__web_individual__'];
    }

    $seenApiKeys = [];
    $issues = [];
    $summary = [
        'collection' => $collection,
        'source_slug' => $sourceSlug,
        'local_rows' => $localCount,
        'api_total' => null,
        'api_rows_scanned' => 0,
        'book_fetch_failed' => 0,
        'duplicate_local_keys' => count($duplicateLocalKeys),
        'missing_in_db' => 0,
        'extra_in_db' => 0,
        'text_mismatch' => 0,
        'text_variant' => 0,
        'urn_mismatch' => 0,
    ];

    foreach ($duplicateLocalKeys as $duplicate) {
        $issues[] = ['type' => 'duplicate_local_key'] + $duplicate;
    }

    if ($bookNumbers === []) {
        $bookNumbers = ['__individual__'];
    }

    $apiTotalSeenBooks = [];
    foreach ($bookNumbers as $bookNumberToFetch) {
        $page = 1;
        do {
            try {
                if (in_array($bookNumberToFetch, ['__individual__', '__web_individual__'], true)) {
                    $individualRows = array_values($localByKey);
                    $localForIndividual = $individualRows[$page - 1] ?? null;
                    if ($localForIndividual === null) {
                        break;
                    }
                    if ($bookNumberToFetch === '__web_individual__') {
                        $payload = webHadithPayload(
                            $webClient,
                            $collection,
                            (string) $localForIndividual->hadith_number,
                            (string) ($localForIndividual->book_number ?? '')
                        );
                        if ($payload === null) {
                            throw new RuntimeException("Could not extract web payload for {$collection}:{$localForIndividual->hadith_number}");
                        }
                        $apiRows = [$payload];
                        $next = $page + 1 <= count($localByKey) ? $page + 1 : null;
                        goto process_payload;
                    }
                    $response = getWithRetry($client, "collections/{$apiCollection}/hadiths/{$localForIndividual->hadith_number}");
                } else {
                    $response = getWithRetry($client, "collections/{$apiCollection}/books/{$bookNumberToFetch}/hadiths", [
                        'query' => [
                            'limit' => $limit,
                            'page' => $page,
                        ],
                    ]);
                }
            } catch (RequestException $e) {
                if ($bookNumberToFetch === '__individual__' && isset($localForIndividual)) {
                    try {
                        $webPayload = webHadithPayload(
                            $webClient,
                            $collection,
                            (string) $localForIndividual->hadith_number,
                            (string) ($localForIndividual->book_number ?? '')
                        );
                        if ($webPayload !== null) {
                            $payload = $webPayload;
                            $apiRows = [$payload];
                            $next = $page + 1 <= count($localByKey) ? $page + 1 : null;
                            goto process_payload;
                        }
                    } catch (Throwable $webError) {
                        $issues[] = [
                            'type' => 'web_fetch_failed',
                            'book_number' => null,
                            'hadith_number' => $localForIndividual->hadith_number ?? null,
                            'message' => $webError->getMessage(),
                        ];
                        // Fall through to the normal fetch-failed report below.
                    }
                }

                $summary['book_fetch_failed']++;
                $issues[] = [
                    'type' => 'book_fetch_failed',
                    'book_number' => in_array($bookNumberToFetch, ['__individual__', '__web_individual__'], true) ? null : $bookNumberToFetch,
                    'hadith_number' => $localForIndividual->hadith_number ?? null,
                    'status' => $e->hasResponse() ? $e->getResponse()->getStatusCode() : null,
                    'message' => $e->getMessage(),
                ];
                break;
            }

            $payload = json_decode((string) $response->getBody());
            if (in_array($bookNumberToFetch, ['__individual__', '__web_individual__'], true)) {
                $apiRows = [$payload];
                $next = $page + 1 <= count($localByKey) ? $page + 1 : null;
            } else {
                $apiRows = $payload->data ?? [];
                $next = $payload->next ?? null;
            }

            process_payload:

            if (! in_array($bookNumberToFetch, ['__individual__', '__web_individual__'], true) && isset($payload->total) && ! isset($apiTotalSeenBooks[(string) $bookNumberToFetch])) {
                $summary['api_total'] = (int) ($summary['api_total'] ?? 0) + (int) $payload->total;
                $apiTotalSeenBooks[(string) $bookNumberToFetch] = true;
            }

            foreach ($apiRows as $apiHadith) {
                $bookNumber = (string) ($apiHadith->bookNumber ?? (in_array($bookNumberToFetch, ['__individual__', '__web_individual__'], true) ? '' : $bookNumberToFetch));
                $hadithNumber = (string) ($apiHadith->hadithNumber ?? '');
                $key = "{$bookNumber}|{$hadithNumber}";
                $seenApiKeys[$key] = true;
                $summary['api_rows_scanned']++;

                $local = $localByKey[$key] ?? null;
                if ($local === null) {
                    $summary['missing_in_db']++;
                    $issues[] = [
                        'type' => 'missing_in_db',
                        'book_number' => $bookNumber,
                        'hadith_number' => $hadithNumber,
                        'url' => "https://sunnah.com/{$apiCollection}:{$hadithNumber}",
                    ];

                    continue;
                }

                foreach (['ar', 'en'] as $lang) {
                    $api = apiLangPayload($apiHadith, $lang);
                    $localText = (string) ($lang === 'ar' ? $local->ar_text : $local->en_text);
                    $apiLoose = looseText($api['text']);
                    $localLoose = looseText($localText);

                    if ($apiLoose === '' || $localLoose === '' || $apiLoose === $localLoose) {
                        continue;
                    }

                    $score = textScore($apiLoose, $localLoose);
                    $type = $score >= 98.0 ? 'text_variant' : 'text_mismatch';
                    $summary[$type]++;
                    $issues[] = [
                        'type' => $type,
                        'language' => $lang,
                        'score' => round($score, 2),
                        'book_number' => $bookNumber,
                        'hadith_number' => $hadithNumber,
                        'hadith_id' => $local->id,
                        'source_file' => $local->source_file,
                        'url' => "https://sunnah.com/{$apiCollection}:{$hadithNumber}",
                        'api_preview' => mb_substr(plainText($api['text']), 0, 220),
                        'db_preview' => mb_substr(plainText($localText), 0, 220),
                    ];
                }

                $apiAr = apiLangPayload($apiHadith, 'ar');
                $apiEn = apiLangPayload($apiHadith, 'en');
                $urnPairs = [
                    'ar' => [$apiAr['urn'], $local->sunnah_urn_ar ?: $local->ar_urn],
                    'en' => [$apiEn['urn'], $local->sunnah_urn_en ?: $local->en_urn],
                ];

                foreach ($urnPairs as $lang => [$apiUrn, $localUrn]) {
                    if ($apiUrn === null || $localUrn === null || (int) $apiUrn === (int) $localUrn) {
                        continue;
                    }

                    $summary['urn_mismatch']++;
                    $issues[] = [
                        'type' => 'urn_mismatch',
                        'language' => $lang,
                        'book_number' => $bookNumber,
                        'hadith_number' => $hadithNumber,
                        'hadith_id' => $local->id,
                        'api_urn' => $apiUrn,
                        'db_urn' => (int) $localUrn,
                        'source_file' => $local->source_file,
                        'url' => "https://sunnah.com/{$apiCollection}:{$hadithNumber}",
                    ];
                }
            }

            if ($maxPages !== null && $page >= $maxPages) {
                $next = null;
            }
            if ($next !== null && $delayMs > 0) {
                usleep($delayMs * 1000);
            }
            $page = (int) $next;
        } while ($page > 0);
    }

    foreach ($localByKey as $key => $local) {
        if (isset($seenApiKeys[$key])) {
            continue;
        }

        [$bookNumber, $hadithNumber] = explode('|', $key, 2);
        $summary['extra_in_db']++;
        $issues[] = [
            'type' => 'extra_in_db',
            'book_number' => $bookNumber,
            'hadith_number' => $hadithNumber,
            'hadith_id' => $local->id,
            'source_file' => $local->source_file,
            'source_url' => $local->source_url,
        ];
    }

    $allSummaries[] = $summary;
    $allIssues[$collection] = $issues;

    printf(
        "%-18s local=%5d api=%5s scanned=%5d fetchfail=%3d missing=%4d extra=%4d mismatch=%4d variant=%4d urn=%4d\n",
        $collection,
        $summary['local_rows'],
        $summary['api_total'] ?? 'n/a',
        $summary['api_rows_scanned'],
        $summary['book_fetch_failed'],
        $summary['missing_in_db'],
        $summary['extra_in_db'],
        $summary['text_mismatch'],
        $summary['text_variant'],
        $summary['urn_mismatch'],
    );
}

file_put_contents(
    "{$outDir}/summary.json",
    json_encode($allSummaries, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
);
file_put_contents(
    "{$outDir}/issues.json",
    json_encode($allIssues, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
);

echo "\nSummary: {$outDir}/summary.json\n";
echo "Issues:  {$outDir}/issues.json\n";
