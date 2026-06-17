<?php

declare(strict_types=1);

/**
 * Fix Jami` at-Tirmidhi production JSON from Sunnah.com API data.
 *
 * Tirmidhi repeats printed hadith references for separate "another chain"
 * narrations. Match by ordered source/API position when counts match exactly;
 * do not treat duplicate (book_number, hadith_number) pairs as duplicate rows.
 *
 * Usage:
 *   php scripts/fix_tirmidhi_from_sunnah_api.php
 *   php scripts/fix_tirmidhi_from_sunnah_api.php --apply
 */

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$apply = in_array('--apply', array_slice($argv, 1), true);
$verbose = in_array('--verbose', array_slice($argv, 1), true);
$sourceRoot = dirname(__DIR__, 2).'/prophet-data-generator/hadith/production/tirmidhi';
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

function tirmidhiText(?string $html): string
{
    $text = html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = str_replace(["\xc2\xa0", '“', '”', '‘', '’', '`'], [' ', '"', '"', "'", "'", "'"], $text);

    return trim((string) preg_replace('/\s+/u', ' ', $text));
}

function stripArabicMarksTirmidhi(string $text): string
{
    $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}\x{06D6}-\x{06ED}]/u', '', $text);

    return trim((string) preg_replace('/[ \t]+/u', ' ', (string) $text));
}

function looseTirmidhiText(?string $html): string
{
    $text = mb_strtolower(tirmidhiText($html), 'UTF-8');
    $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}\x{06D6}-\x{06ED}]/u', '', (string) $text);

    return (string) preg_replace('/[^\p{L}\p{N}]+/u', '', (string) $text);
}

function tirmidhiTextScore(string $left, string $right): float
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

function tirmidhiRefKey(string $bookNumber, string $hadithNumber): string
{
    $book = mb_strtolower(trim($bookNumber), 'UTF-8');
    $hadith = mb_strtolower(trim($hadithNumber), 'UTF-8');
    $hadith = preg_replace('/\s+/u', '', (string) $hadith);

    return "{$book}|{$hadith}";
}

function getJsonWithRetryTirmidhi(Client $client, string $uri, array $query = [], int $attempts = 5): object
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
            sleep($status === 429 ? 15 * $attempt : 5 * $attempt);
        }
    }

    throw $last;
}

function tirmidhiLangPayload(object $apiHadith, string $lang): array
{
    foreach (($apiHadith->hadith ?? []) as $translation) {
        if (($translation->lang ?? null) !== $lang) {
            continue;
        }

        return [
            'text' => tirmidhiText($translation->body ?? ''),
            'urn' => isset($translation->urn) ? (int) $translation->urn : null,
            'chapter_number' => isset($translation->chapterNumber) ? (string) $translation->chapterNumber : null,
            'chapter_title' => tirmidhiText($translation->chapterTitle ?? ''),
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

function tirmidhiBookName(object $book, string $lang): string
{
    foreach (($book->book ?? []) as $translation) {
        if (($translation->lang ?? null) === $lang) {
            return tirmidhiText($translation->name ?? '');
        }
    }

    return '';
}

function tirmidhiApiRow(object $apiHadith, object $book): array
{
    $en = tirmidhiLangPayload($apiHadith, 'en');
    $ar = tirmidhiLangPayload($apiHadith, 'ar');
    $chapterNumber = $ar['chapter_number'] ?: $en['chapter_number'] ?: (isset($apiHadith->chapterId) ? (string) $apiHadith->chapterId : null);

    return [
        'hadith_number' => (string) $apiHadith->hadithNumber,
        'book_number' => (string) $apiHadith->bookNumber,
        'book_name_en' => tirmidhiBookName($book, 'en'),
        'book_name_ar' => tirmidhiBookName($book, 'ar'),
        'chapter_number' => $chapterNumber,
        'chapter_name_en' => $en['chapter_title'],
        'chapter_name_ar' => $ar['chapter_title'],
        'source_url' => 'https://sunnah.com/tirmidhi:'.rawurlencode((string) $apiHadith->hadithNumber),
        'en' => $en['text'],
        'ar' => $ar['text'],
        'urn_en' => $en['urn'],
        'urn_ar' => $ar['urn'],
        'grade_en' => $en['grade'],
        'grade_ar' => $ar['grade'],
    ];
}

function tirmidhiSourceFallbackRow(array $json, object $book): array
{
    $collection = $json['collections'][0] ?? [];
    $reference = $json['metadata']['sunnah_reference'] ?? [];

    return [
        'hadith_number' => (string) ($collection['hadith_number'] ?? $reference['hadith_number'] ?? ''),
        'book_number' => (string) ($collection['book_number'] ?? $reference['book_number'] ?? ''),
        'book_name_en' => tirmidhiBookName($book, 'en') ?: tirmidhiText((string) ($collection['book_name_en'] ?? '')),
        'book_name_ar' => tirmidhiBookName($book, 'ar') ?: tirmidhiText((string) ($collection['book_name_ar'] ?? '')),
        'chapter_number' => (string) ($collection['chapter_number'] ?? $json['chapter_info'] ?? ''),
        'chapter_name_en' => tirmidhiText((string) ($collection['chapter_name_en'] ?? '')),
        'chapter_name_ar' => tirmidhiText((string) ($collection['chapter_name_ar'] ?? '')),
        'source_url' => (string) ($collection['source_url'] ?? $reference['url'] ?? ''),
        'en' => tirmidhiText((string) ($json['hadith_text']['en'] ?? '')),
        'ar' => tirmidhiText((string) ($json['hadith_text']['ar'] ?? '')),
        'urn_en' => isset($reference['urn_en']) ? (int) $reference['urn_en'] : null,
        'urn_ar' => isset($reference['urn']) ? (int) $reference['urn'] : null,
        'grade_en' => (string) ($json['grading']['status'] ?? ''),
        'grade_ar' => '',
    ];
}

function tirmidhiSourceFiles(string $sourceRoot): array
{
    $files = glob(rtrim($sourceRoot, '/').'/hadith_*_final.json') ?: [];
    usort($files, static function (string $a, string $b): int {
        preg_match('/hadith_(\d+)_final\.json$/', basename($a), $left);
        preg_match('/hadith_(\d+)_final\.json$/', basename($b), $right);

        return ((int) ($left[1] ?? 0)) <=> ((int) ($right[1] ?? 0));
    });

    return $files;
}

$files = tirmidhiSourceFiles($sourceRoot);
$sourceRowsByBook = [];
foreach ($files as $file) {
    $json = json_decode((string) file_get_contents($file), true);
    if (! is_array($json)) {
        continue;
    }

    $collection = $json['collections'][0] ?? [];
    $bookNumber = (string) ($collection['book_number'] ?? $json['metadata']['sunnah_reference']['book_number'] ?? '');
    if ($bookNumber === '') {
        continue;
    }

    $sourceRowsByBook[$bookNumber][] = [
        'file' => $file,
        'json' => $json,
    ];
}

$books = [];
$page = 1;
do {
    $response = getJsonWithRetryTirmidhi($client, 'collections/tirmidhi/books', ['limit' => 100, 'page' => $page]);
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
$apiRows = 0;
$individualFallbacks = 0;
$sourceFallbacks = 0;
foreach ($books as $bookNumber => $book) {
    $bookRows = 0;
    $hadithPage = 1;
    do {
        $response = getJsonWithRetryTirmidhi($client, "collections/tirmidhi/books/{$bookNumber}/hadiths", [
            'limit' => $limit,
            'page' => $hadithPage,
        ]);

        foreach (($response->data ?? []) as $apiHadith) {
            $row = tirmidhiApiRow($apiHadith, $book);
            $key = tirmidhiRefKey($row['book_number'], $row['hadith_number']);
            if (! isset($apiRowsByKey[$key])) {
                $apiRowsByKey[$key] = $row;
            }
            $apiRowsOrdered[] = $row;
            $apiRows++;
            $bookRows++;
        }

        $hadithPage = isset($response->next) && $response->next !== null ? (int) $response->next : 0;
        if ($hadithPage > 0) {
            usleep($delayMs * 1000);
        }
    } while ($hadithPage > 0);

    if ($bookRows === 0 && isset($sourceRowsByBook[$bookNumber])) {
        $seenFallbackRefs = [];
        foreach ($sourceRowsByBook[$bookNumber] as $sourceRow) {
            $sourceJson = $sourceRow['json'];
            $sourceCollection = $sourceJson['collections'][0] ?? [];
            $hadithNumber = (string) ($sourceCollection['hadith_number'] ?? $sourceJson['metadata']['sunnah_reference']['hadith_number'] ?? '');
            $sourceKey = tirmidhiRefKey((string) $bookNumber, $hadithNumber);
            $sourceLoose = looseTirmidhiText((string) ($sourceJson['hadith_text']['ar'] ?? '')).' '.looseTirmidhiText((string) ($sourceJson['hadith_text']['en'] ?? ''));

            if (isset($seenFallbackRefs[$sourceKey]) && $seenFallbackRefs[$sourceKey] !== $sourceLoose) {
                $row = tirmidhiSourceFallbackRow($sourceJson, $book);
                $sourceFallbacks++;
            } else {
                $lookupHadithNumber = preg_replace('/\s+/u', '', $hadithNumber);
                try {
                    $apiHadith = getJsonWithRetryTirmidhi($client, 'collections/tirmidhi/hadiths/'.rawurlencode((string) $lookupHadithNumber));
                } catch (RequestException $e) {
                    $status = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
                    if ($status !== 404) {
                        throw $e;
                    }
                    $apiHadith = null;
                }

                if ($apiHadith === null || ! isset($apiHadith->hadithNumber)) {
                    $row = tirmidhiSourceFallbackRow($sourceJson, $book);
                    $sourceFallbacks++;
                } else {
                    $row = tirmidhiApiRow($apiHadith, $book);
                    $individualFallbacks++;
                }
            }

            if (! isset($apiRowsByKey[$sourceKey])) {
                $apiRowsByKey[$sourceKey] = $row;
            }
            $apiRowsOrdered[] = $row;
            $apiRows++;
            $bookRows++;
            $seenFallbackRefs[$sourceKey] = $sourceLoose;

            usleep($delayMs * 1000);
        }
    }

    if ($verbose) {
        echo "book {$bookNumber}: {$bookRows} rows\n";
    }

    usleep($delayMs * 1000);
}

$sourceKeyCounts = [];
foreach ($files as $file) {
    $json = json_decode((string) file_get_contents($file), true);
    if (! is_array($json)) {
        continue;
    }
    $oldCollection = $json['collections'][0] ?? [];
    $sourceBook = (string) ($oldCollection['book_number'] ?? '');
    $sourceHadith = (string) ($oldCollection['hadith_number'] ?? $json['metadata']['sunnah_reference']['hadith_number'] ?? '');
    $sourceKeyCounts[tirmidhiRefKey($sourceBook, $sourceHadith)] = ($sourceKeyCounts[tirmidhiRefKey($sourceBook, $sourceHadith)] ?? 0) + 1;
}
$apiKeysMissingFromSource = array_values(array_diff(array_keys($apiRowsByKey), array_keys($sourceKeyCounts)));

$updated = 0;
$textDiffs = 0;
$metadataDiffs = 0;
$duplicateTextRemaps = 0;
$sequenceRemaps = 0;
$missingApi = [];
$seenSourceKeys = [];

foreach ($files as $sourceIndex => $file) {
    $json = json_decode((string) file_get_contents($file), true);
    if (! is_array($json)) {
        continue;
    }

    $oldCollection = $json['collections'][0] ?? [];
    $sourceBook = (string) ($oldCollection['book_number'] ?? '');
    $sourceHadith = (string) ($oldCollection['hadith_number'] ?? $json['metadata']['sunnah_reference']['hadith_number'] ?? '');
    $key = tirmidhiRefKey($sourceBook, $sourceHadith);
    $api = $apiRowsByKey[$key] ?? null;

    if (count($apiRowsOrdered) === count($files)) {
        $api = $apiRowsOrdered[$sourceIndex] ?? null;
        if ($api !== null) {
            $sequenceKey = tirmidhiRefKey($api['book_number'], $api['hadith_number']);
            if ($sequenceKey !== $key) {
                $sequenceRemaps++;
                if ($verbose) {
                    echo 'sequence remap '.basename($file)." {$key} -> {$sequenceKey}\n";
                }
            }
            $key = $sequenceKey;
        }
    } elseif (($sourceKeyCounts[$key] ?? 0) > 1 && $apiKeysMissingFromSource !== []) {
        $sourceLoose = looseTirmidhiText((string) ($json['hadith_text']['ar'] ?? '')).' '.looseTirmidhiText((string) ($json['hadith_text']['en'] ?? ''));
        $bestKey = null;
        $bestScore = 0.0;

        foreach ($apiKeysMissingFromSource as $candidateKey) {
            $candidate = $apiRowsByKey[$candidateKey] ?? null;
            if ($candidate === null) {
                continue;
            }

            $candidateLoose = looseTirmidhiText($candidate['ar']).' '.looseTirmidhiText($candidate['en']);
            $score = tirmidhiTextScore($sourceLoose, $candidateLoose);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestKey = $candidateKey;
            }
        }

        if ($bestKey !== null && $bestScore >= 98.0) {
            if ($verbose) {
                echo 'remap '.basename($file)." {$key} -> {$bestKey} score=".round($bestScore, 2)."\n";
            }
            $key = $bestKey;
            $api = $apiRowsByKey[$key];
            $duplicateTextRemaps++;
        } elseif ($verbose && $bestKey !== null && $bestScore > 0) {
            echo 'no remap '.basename($file)." {$key}; best {$bestKey} score=".round($bestScore, 2)."\n";
        }
    }

    if ($api === null) {
        $missingApi[] = basename($file).":{$sourceBook}:{$sourceHadith}";

        continue;
    }
    $seenSourceKeys[$key] = true;

    $oldTexts = $json['hadith_text'] ?? [];
    $textChanged = ($api['en'] !== '' && looseTirmidhiText((string) ($oldTexts['en'] ?? '')) !== looseTirmidhiText($api['en']))
        || ($api['ar'] !== '' && looseTirmidhiText((string) ($oldTexts['ar'] ?? '')) !== looseTirmidhiText($api['ar']));

    $metadataChanged = (string) ($oldCollection['hadith_number'] ?? '') !== $api['hadith_number']
        || (string) ($oldCollection['book_number'] ?? '') !== $api['book_number']
        || tirmidhiText((string) ($oldCollection['book_name_en'] ?? '')) !== $api['book_name_en']
        || tirmidhiText((string) ($oldCollection['book_name_ar'] ?? '')) !== $api['book_name_ar']
        || (string) ($oldCollection['chapter_number'] ?? '') !== (string) ($api['chapter_number'] ?? '')
        || tirmidhiText((string) ($oldCollection['chapter_name_en'] ?? '')) !== $api['chapter_name_en']
        || tirmidhiText((string) ($oldCollection['chapter_name_ar'] ?? '')) !== $api['chapter_name_ar']
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
    $json['hadith_text']['ar_notashkeel'] = stripArabicMarksTirmidhi($api['ar']);
    $json['verification_status'] = 'source_verified';
    $json['uncertainty_notes'] = null;
    $json['source_verification']['sunnah'] = [
        'status' => 'source_verified',
        'url' => $api['source_url'],
    ];

    $json['collections'] = [[
        'name_en' => 'Jami` at-Tirmidhi',
        'name_ar' => 'جامع الترمذي',
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
        'collection' => 'tirmidhi',
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

$missingSource = count($apiRowsOrdered) === count($files)
    ? []
    : array_values(array_diff(array_keys($apiRowsByKey), array_keys($seenSourceKeys)));

echo 'tirmidhi api_books='.count($books)
    ." api_hadiths={$apiRows}"
    .' source_hadiths='.count($files)
    ." text_diffs={$textDiffs}"
    ." metadata_diffs={$metadataDiffs}"
    ." updated={$updated}"
    ." sequence_remaps={$sequenceRemaps}"
    ." duplicate_text_remaps={$duplicateTextRemaps}"
    ." individual_fallbacks={$individualFallbacks}"
    ." source_fallbacks={$sourceFallbacks}"
    .' missing_api='.count($missingApi)
    .' missing_source='.count($missingSource)
    ."\n";

if ($missingApi !== []) {
    echo 'Missing API for source rows: '.implode(', ', array_slice($missingApi, 0, 40)).(count($missingApi) > 40 ? ' ...' : '')."\n";
}
if ($missingSource !== []) {
    echo 'Missing source for API rows: '.implode(', ', array_slice($missingSource, 0, 40)).(count($missingSource) > 40 ? ' ...' : '')."\n";
}

exit($missingApi === [] && $missingSource === [] ? 0 : 1);
