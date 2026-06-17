<?php

declare(strict_types=1);

/**
 * Fix curated 40-hadith production JSON from Sunnah.com web pages.
 *
 * This is intentionally source-first: it updates prophet-data-generator files.
 * Re-import after running this script.
 *
 * Usage:
 *   php scripts/fix_curated_sunnah_collection.php --collection=nawawi40
 *   php scripts/fix_curated_sunnah_collection.php --collection=qudsi40
 */

use GuzzleHttp\Client;

require __DIR__.'/../vendor/autoload.php';

$collection = null;
$sourceRoot = dirname(__DIR__, 2).'/prophet-data-generator/hadith/production';

foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--collection=')) {
        $collection = substr($arg, strlen('--collection='));
    }
    if (str_starts_with($arg, '--source=')) {
        $sourceRoot = substr($arg, strlen('--source='));
    }
}

if (! is_string($collection) || $collection === '') {
    fwrite(STDERR, "Missing --collection.\n");
    exit(1);
}

$dir = rtrim($sourceRoot, '/')."/{$collection}";
if (! is_dir($dir)) {
    fwrite(STDERR, "Collection directory not found: {$dir}\n");
    exit(1);
}

$client = new Client([
    'base_uri' => 'https://sunnah.com/',
    'timeout' => 45,
    'headers' => [
        'Accept' => 'text/html',
        'User-Agent' => 'TheLastMessengerHadithFixer/1.0',
    ],
]);

function htmlText(string $html): string
{
    $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = str_replace(["\xc2\xa0", '“', '”', '‘', '’'], [' ', '"', '"', "'", "'"], $text);

    return trim((string) preg_replace('/[ \t]+/u', ' ', (string) preg_replace('/\R+/u', "\n", $text)));
}

function stripArabicMarks(string $text): string
{
    $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}\x{06D6}-\x{06ED}]/u', '', $text);

    return trim((string) preg_replace('/[ \t]+/u', ' ', (string) $text));
}

function extractPageText(Client $client, string $collection, string $hadithNumber): array
{
    $response = $client->get('https://sunnah.com/'.rawurlencode($collection).':'.rawurlencode($hadithNumber));
    $html = (string) $response->getBody();

    $english = '';
    if (preg_match('/<div class="english_hadith_full">(.*?)<div class=clear><\/div>/s', $html, $match)) {
        $english = htmlText($match[1]);
    }

    $arabic = '';
    if (preg_match('/<div class="arabic_hadith_full arabic">(.*?)<\/div>/s', $html, $match)) {
        $arabic = htmlText($match[1]);
    }

    if ($english === '' && $arabic === '') {
        throw new RuntimeException("Could not extract text for {$collection}:{$hadithNumber}");
    }

    return ['en' => $english, 'ar' => $arabic];
}

function canonicalCollectionRow(string $collection, string $hadithNumber): array
{
    $row = match ($collection) {
        'nawawi40' => [
            'name_en' => 'The Forty Hadith of Imam Nawawi',
            'name_ar' => 'الأربعون النووية',
            'book_name_en' => 'The Forty Hadith of Imam Nawawi',
            'book_name_ar' => 'الأربعون النووية',
        ],
        'qudsi40' => [
            'name_en' => 'The Forty Hadith Qudsi',
            'name_ar' => 'الأربعون القدسية',
            'book_number' => 11,
            'book_name_en' => '',
            'book_name_ar' => '',
            'chapter_number' => 0,
            'chapter_name_en' => '',
            'chapter_name_ar' => '',
        ],
        'shahwaliullah40' => [
            'name_en' => 'The Forty Hadith of Shah Waliullah',
            'name_ar' => 'الأربعون حديثا شاه ولي الله',
            'book_name_en' => 'The Forty Hadith of Shah Waliullah',
            'book_name_ar' => 'الأربعون حديثا شاه ولي الله',
        ],
        default => throw new InvalidArgumentException("Unsupported curated collection: {$collection}"),
    };

    $row['hadith_number'] = $hadithNumber;
    $row['verified'] = true;
    $row['source_url'] = "https://sunnah.com/{$collection}:{$hadithNumber}";
    $row['source_verified'] = true;

    if ($collection === 'nawawi40' && in_array((int) $hadithNumber, array_merge(range(11, 14), range(17, 42)), true)) {
        $row['chapter_number'] = 1;
        $row['chapter_name_en'] = 'Comprehensive Ethical and Legal Principles';
        $row['chapter_name_ar'] = 'الأصول الجامعة للأخلاق والأحكام';
    }

    return $row;
}

$files = glob("{$dir}/hadith_*_final.json") ?: [];
sort($files);

$updated = 0;
$failed = [];

foreach ($files as $file) {
    $json = json_decode((string) file_get_contents($file), true);
    if (! is_array($json)) {
        $failed[] = [basename($file), 'invalid JSON'];

        continue;
    }

    $hadithNumber = (string) (
        $json['metadata']['id_in_book']
        ?? $json['collections'][0]['hadith_number']
        ?? $json['metadata']['sunnah_reference']['hadith_number']
        ?? ''
    );

    if ($hadithNumber === '') {
        $failed[] = [basename($file), 'missing hadith number'];

        continue;
    }

    try {
        $pageText = extractPageText($client, $collection, $hadithNumber);
    } catch (Throwable $e) {
        $failed[] = [basename($file), $e->getMessage()];

        continue;
    }

    $json['hadith_text']['ar'] = $pageText['ar'];
    $json['hadith_text']['ar_notashkeel'] = stripArabicMarks($pageText['ar']);
    $json['hadith_text']['en'] = $pageText['en'];
    $json['verification_status'] = 'source_verified';
    $json['uncertainty_notes'] = null;

    $json['collections'] = [canonicalCollectionRow($collection, $hadithNumber)];

    file_put_contents(
        $file,
        json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n"
    );

    $updated++;
    usleep(200_000);
}

echo "{$collection}: updated {$updated} file(s)";
if ($failed !== []) {
    echo ', failed '.count($failed);
}
echo "\n";

foreach ($failed as [$file, $reason]) {
    echo "FAILED {$file}: {$reason}\n";
}

exit($failed === [] ? 0 : 1);
