<?php

declare(strict_types=1);

/**
 * Fix Bulugh al-Maram production JSON from Sunnah.com web pages.
 *
 * Bulugh API metadata is incomplete: the books route lists only 5 books, and
 * many individual hadith routes return 404. The web index exposes all 16 books
 * and all 1,767 rows, so this script matches local source files to Sunnah web
 * rows by text content and rewrites the source text/metadata from the web.
 *
 * Usage:
 *   php scripts/fix_bulugh_from_sunnah_web.php
 *   php scripts/fix_bulugh_from_sunnah_web.php --apply
 */

use GuzzleHttp\Client;

require __DIR__.'/../vendor/autoload.php';

$apply = in_array('--apply', array_slice($argv, 1), true);
$verbose = in_array('--verbose', array_slice($argv, 1), true);
$sourceRoot = dirname(__DIR__, 2).'/prophet-data-generator/hadith/production/bulugh_almaram';
$delayMs = 350;

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

function bulughText(string $html): string
{
    $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = str_replace(["\xc2\xa0", '“', '”', '‘', '’', '`'], [' ', '"', '"', "'", "'", "'"], $text);

    return trim((string) preg_replace('/\s+/u', ' ', $text));
}

function stripArabicMarksBulugh(string $text): string
{
    $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}\x{06D6}-\x{06ED}]/u', '', $text);

    return trim((string) preg_replace('/[ \t]+/u', ' ', (string) $text));
}

function looseBulughText(string $text): string
{
    $text = mb_strtolower(bulughText($text), 'UTF-8');
    $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}\x{06D6}-\x{06ED}]/u', '', (string) $text);

    return (string) preg_replace('/[^\p{L}\p{N}]+/u', '', (string) $text);
}

function bulughScore(string $left, string $right): float
{
    if ($left === '' || $right === '') {
        return 0.0;
    }
    $score = 0.0;
    similar_text(mb_substr($left, 0, 2200), mb_substr($right, 0, 2200), $score);

    return $score;
}

function getBulughHtml(Client $client, string $path): string
{
    $last = null;
    for ($attempt = 1; $attempt <= 5; $attempt++) {
        try {
            return (string) $client->get(ltrim($path, '/'))->getBody();
        } catch (Throwable $e) {
            $last = $e;
            sleep(6 * $attempt);
        }
    }

    throw $last;
}

function extractBulughBooks(string $html): array
{
    preg_match_all(
        '/<a href="\/bulugh\/(\d+)">\s*<div class="book_number title_number">.*?<div class="english english_book_name">(.*?)<\/div><div class="arabic arabic_book_name">(.*?)<\/div>/s',
        $html,
        $matches,
        PREG_SET_ORDER
    );

    $books = [];
    foreach ($matches as $match) {
        $books[(int) $match[1]] = [
            'book_number' => (int) $match[1],
            'book_name_en' => bulughText($match[2]),
            'book_name_ar' => bulughText($match[3]),
        ];
    }
    ksort($books);

    return $books;
}

function extractBulughRowsFromBook(string $html, array $book): array
{
    $parts = preg_split('/<div class="actualHadithContainer\b/', $html) ?: [];
    array_shift($parts);

    $rows = [];
    foreach ($parts as $part) {
        $english = '';
        if (preg_match('/<div class="english_hadith_full">(.*?)<div class=clear><\/div><\/div><\/div><div class="arabic_hadith_full/s', $part, $match)) {
            $english = bulughText($match[1]);
        }

        $arabic = '';
        if (preg_match('/<div class="arabic_hadith_full arabic">(.*?)<\/div>\s*<\/div>\s*<!-- End hadith -->/s', $part, $match)) {
            $arabic = bulughText($match[1]);
        }

        if ($english === '' && $arabic === '') {
            continue;
        }

        $rows[] = [
            'book_number' => (string) $book['book_number'],
            'book_name_en' => $book['book_name_en'],
            'book_name_ar' => $book['book_name_ar'],
            'source_url' => "https://sunnah.com/bulugh/{$book['book_number']}",
            'en' => $english,
            'ar' => $arabic,
            'loose' => looseBulughText($arabic.' '.$english),
        ];
    }

    return $rows;
}

$indexHtml = getBulughHtml($client, '/bulugh');
$books = extractBulughBooks($indexHtml);

$webRows = [];
foreach ($books as $bookNumber => $book) {
    $bookHtml = getBulughHtml($client, "/bulugh/{$bookNumber}");
    $bookRows = extractBulughRowsFromBook($bookHtml, $book);
    if ($verbose) {
        echo "book {$bookNumber}: ".count($bookRows)." rows\n";
    }
    foreach ($bookRows as $row) {
        $webRows[] = $row;
    }
    usleep($delayMs * 1000);
}

$files = glob(rtrim($sourceRoot, '/').'/hadith_*_final.json') ?: [];
usort($files, static function (string $a, string $b): int {
    $left = json_decode((string) file_get_contents($a), true);
    $right = json_decode((string) file_get_contents($b), true);

    return ((int) ($left['metadata']['id_in_book'] ?? 0)) <=> ((int) ($right['metadata']['id_in_book'] ?? 0));
});

$sourceEntries = [];
$invalid = [];
foreach ($files as $file) {
    $json = json_decode((string) file_get_contents($file), true);
    if (! is_array($json)) {
        $invalid[] = basename($file);

        continue;
    }

    $sourceEntries[] = [
        'file' => $file,
        'json' => $json,
        'loose' => looseBulughText((string) ($json['hadith_text']['ar'] ?? '').' '.(string) ($json['hadith_text']['en'] ?? '')),
    ];
}

$webByLoose = [];
foreach ($webRows as $webIndex => $row) {
    $webByLoose[$row['loose']][] = $webIndex;
}

$assignedSource = [];
$assignedWeb = [];
$assignments = [];
foreach ($sourceEntries as $sourceIndex => $entry) {
    $hits = $webByLoose[$entry['loose']] ?? [];
    if (count($hits) === 1 && ! isset($assignedWeb[$hits[0]])) {
        $assignments[$sourceIndex] = ['web' => $hits[0], 'score' => 100.0];
        $assignedSource[$sourceIndex] = true;
        $assignedWeb[$hits[0]] = true;
    }
}

foreach ($sourceEntries as $sourceIndex => $entry) {
    if (isset($assignedSource[$sourceIndex])) {
        continue;
    }
    $hits = [];
    foreach ($webRows as $webIndex => $row) {
        if (isset($assignedWeb[$webIndex])) {
            continue;
        }
        if ($entry['loose'] !== '' && (
            str_contains($row['loose'], $entry['loose'])
            || str_contains($entry['loose'], $row['loose'])
            || str_starts_with($row['loose'], mb_substr($entry['loose'], 0, 300))
            || str_starts_with($entry['loose'], mb_substr($row['loose'], 0, 300))
        )) {
            $hits[] = $webIndex;
        }
    }
    if (count($hits) === 1) {
        $assignments[$sourceIndex] = ['web' => $hits[0], 'score' => 95.0];
        $assignedSource[$sourceIndex] = true;
        $assignedWeb[$hits[0]] = true;
    }
}

foreach ($sourceEntries as $sourceIndex => $entry) {
    if (isset($assignedSource[$sourceIndex])) {
        continue;
    }

    $bestWeb = null;
    $bestScore = 0.0;
    foreach ($webRows as $webIndex => $row) {
        if (isset($assignedWeb[$webIndex])) {
            continue;
        }
        $score = bulughScore($entry['loose'], $row['loose']);
        if ($score > $bestScore) {
            $bestScore = $score;
            $bestWeb = $webIndex;
        }
    }

    if ($bestWeb !== null && $bestScore >= 60.0) {
        $assignments[$sourceIndex] = ['web' => $bestWeb, 'score' => $bestScore];
        $assignedSource[$sourceIndex] = true;
        $assignedWeb[$bestWeb] = true;
    }
}

$remainingSources = array_values(array_filter(
    array_keys($sourceEntries),
    static fn (int $sourceIndex): bool => ! isset($assignedSource[$sourceIndex]),
));
$remainingWeb = array_values(array_filter(
    array_keys($webRows),
    static fn (int $webIndex): bool => ! isset($assignedWeb[$webIndex]),
));
foreach ($remainingSources as $offset => $sourceIndex) {
    $webIndex = $remainingWeb[$offset] ?? null;
    if ($webIndex === null) {
        break;
    }

    $assignments[$sourceIndex] = [
        'web' => $webIndex,
        'score' => bulughScore($sourceEntries[$sourceIndex]['loose'], $webRows[$webIndex]['loose']),
    ];
    $assignedSource[$sourceIndex] = true;
    $assignedWeb[$webIndex] = true;
}

$updated = 0;
$textDiffs = 0;
$metadataDiffs = 0;
$lowTextMatches = [];
$missingSource = count($webRows) - count($assignedWeb);
$missingWeb = count($sourceEntries) - count($assignedSource);

foreach ($assignments as $sourceIndex => $assignment) {
    $entry = $sourceEntries[$sourceIndex];
    $web = $webRows[$assignment['web']];
    $json = $entry['json'];
    $texts = $json['hadith_text'] ?? [];
    $hadithNumber = (string) ($json['metadata']['id_in_book'] ?? ($sourceIndex + 1));

    if ($assignment['score'] < 80.0) {
        $lowTextMatches[] = basename($entry['file']).":{$web['book_number']}:{$hadithNumber}:".round($assignment['score'], 2);
    }

    $textChanged = looseBulughText((string) ($texts['ar'] ?? '')) !== looseBulughText($web['ar'])
        || looseBulughText((string) ($texts['en'] ?? '')) !== looseBulughText($web['en']);
    $oldCollection = $json['collections'][0] ?? [];
    $metadataChanged = (string) ($oldCollection['hadith_number'] ?? '') !== $hadithNumber
        || (string) ($oldCollection['book_number'] ?? '') !== $web['book_number']
        || bulughText((string) ($oldCollection['book_name_en'] ?? '')) !== $web['book_name_en']
        || bulughText((string) ($oldCollection['book_name_ar'] ?? '')) !== $web['book_name_ar']
        || (string) ($oldCollection['source_url'] ?? '') !== $web['source_url']
        || ($oldCollection['verified'] ?? null) !== true
        || ($oldCollection['source_verified'] ?? null) !== true
        || (string) ($json['metadata']['book_id'] ?? '') !== $web['book_number']
        || (string) ($json['metadata']['sunnah_reference']['collection'] ?? '') !== 'bulugh'
        || (string) ($json['metadata']['sunnah_reference']['book_number'] ?? '') !== $web['book_number']
        || (string) ($json['metadata']['sunnah_reference']['hadith_number'] ?? '') !== $hadithNumber
        || (string) ($json['metadata']['sunnah_reference']['url'] ?? '') !== $web['source_url']
        || (string) ($json['source_verification']['sunnah']['status'] ?? '') !== 'source_verified'
        || (string) ($json['source_verification']['sunnah']['url'] ?? '') !== $web['source_url'];

    if ($textChanged) {
        $textDiffs++;
    }
    if ($metadataChanged) {
        $metadataDiffs++;
    }
    if (! $apply || (! $textChanged && ! $metadataChanged)) {
        continue;
    }

    $json['hadith_text']['en'] = $web['en'];
    $json['hadith_text']['ar'] = $web['ar'];
    $json['hadith_text']['ar_notashkeel'] = stripArabicMarksBulugh($web['ar']);
    $json['verification_status'] = 'source_verified';
    $json['uncertainty_notes'] = null;
    $json['collections'] = [[
        'name_en' => 'Bulugh al-Maram',
        'name_ar' => 'بلوغ المرام',
        'hadith_number' => $hadithNumber,
        'book_number' => $web['book_number'],
        'book_name_en' => $web['book_name_en'],
        'book_name_ar' => $web['book_name_ar'],
        'chapter_number' => null,
        'chapter_name_en' => null,
        'chapter_name_ar' => null,
        'verified' => true,
        'source_url' => $web['source_url'],
        'source_verified' => true,
    ]];
    $json['metadata']['book_id'] = $web['book_number'];
    $json['metadata']['chapter_id'] = null;
    $json['metadata']['collection'] = [
        'id' => 18,
        'name_ar' => 'بلوغ المرام',
        'name_en' => 'Bulugh al-Maram',
        'author_ar' => 'ابن حجر العسقلاني',
        'author_en' => 'Ibn Hajar al-Asqalani',
        'total_hadiths' => 1767,
    ];
    $json['metadata']['sunnah_reference'] = [
        'collection' => 'bulugh',
        'book_number' => $web['book_number'],
        'hadith_number' => $hadithNumber,
        'url' => $web['source_url'],
        'urn' => null,
        'urn_en' => null,
    ];
    $json['chapter_info'] = null;
    $json['source_verification']['sunnah'] = [
        'status' => 'source_verified',
        'web_only' => true,
        'api_slug' => 'bulugh',
        'hadith_number' => $hadithNumber,
        'book_number' => $web['book_number'],
        'url' => $web['source_url'],
    ];

    file_put_contents($entry['file'], json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
    $updated++;
}

echo 'bulugh web_books='.count($books)
    .' web_hadiths='.count($webRows)
    .' source_hadiths='.count($sourceEntries)
    .' mapped='.count($assignments)
    ." text_diffs={$textDiffs}"
    ." metadata_diffs={$metadataDiffs}"
    ." updated={$updated}"
    ." missing_web={$missingWeb}"
    ." missing_source={$missingSource}"
    .' low_text_matches='.count($lowTextMatches)
    .' invalid='.count($invalid)
    ."\n";

if ($lowTextMatches !== []) {
    echo 'Low text matches: '.implode(', ', array_slice($lowTextMatches, 0, 60)).(count($lowTextMatches) > 60 ? ' ...' : '')."\n";
}
if ($invalid !== []) {
    echo 'Invalid source JSON: '.implode(', ', array_slice($invalid, 0, 40)).(count($invalid) > 40 ? ' ...' : '')."\n";
}

exit($missingWeb === 0 && $missingSource === 0 && $invalid === [] ? 0 : 1);
