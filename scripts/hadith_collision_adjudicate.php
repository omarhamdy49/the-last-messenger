<?php

declare(strict_types=1);

/**
 * Adjudicates hadith number collisions against sunnah.com canonical text.
 *
 * Input: storage/app/hadith_collision_genuinely_different.json
 *        (produced by scripts/hadith_import_audit.php + the normalization pass)
 *
 * For each contested (book, hadith_number) slot where the source dataset contains
 * two genuinely different texts, this script:
 *   1. Fetches the canonical page (https://sunnah.com/<slug>:<number>) — cached on disk.
 *   2. Extracts the canonical Arabic matn (arabic_hadith_full block).
 *   3. Scores the DB winner text and the losing source file text against it
 *      (tashkeel-stripped similarity + containment).
 *   4. Decides: keep_winner | reimport_loser | inconclusive.
 *
 * With --apply, files decided as reimport_loser are re-imported through the
 * existing JsonImportService (idempotent updateOrCreate on book_id+hadith_number),
 * which restores the canonical text into the occupied slot.
 *
 * Usage:
 *   php scripts/hadith_collision_adjudicate.php           # dry run, report only
 *   php scripts/hadith_collision_adjudicate.php --apply   # re-import canonical losers
 */

use App\Domain\Hadith\Services\JsonImportService;
use App\Services\Json\HadithJsonTransformer;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$apply = in_array('--apply', $argv, true);

$sourceRoot = '/Volumes/Data/projects/the-last-massenger/prophet-data-generator/hadith/production';
$inputPath = storage_path('app/hadith_collision_genuinely_different.json');
$cacheDir = storage_path('app/sunnah_canonical_cache');

if (! is_dir($cacheDir)) {
    mkdir($cacheDir, 0775, true);
}

$cases = json_decode((string) file_get_contents($inputPath), true);
$transformer = $app->make(HadithJsonTransformer::class);
$importService = $app->make(JsonImportService::class);

/** Strip tashkeel/tatweel and everything except letters/digits. */
function normalizeArabic(string $text): string
{
    $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}\x{06D6}-\x{06ED}]/u', '', $text);

    return (string) preg_replace('/[^\p{L}\p{N}]+/u', '', (string) $text);
}

/** Fetch a sunnah.com page with on-disk caching; returns HTML or null. */
function fetchCanonicalPage(string $url, string $cacheDir): ?string
{
    $cacheFile = $cacheDir.'/'.sha1($url).'.html';
    if (file_exists($cacheFile)) {
        return (string) file_get_contents($cacheFile);
    }

    $context = stream_context_create([
        'http' => [
            'header' => "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36\r\n",
            'timeout' => 20,
        ],
    ]);

    $html = @file_get_contents($url, false, $context);
    if ($html === false || $html === '') {
        return null;
    }

    file_put_contents($cacheFile, $html);
    usleep(700_000); // be polite to sunnah.com

    return $html;
}

/** Extract concatenated normalized Arabic matn blocks from a sunnah.com page. */
function canonicalArabicFromHtml(string $html): string
{
    preg_match_all('/<div class="arabic_hadith_full arabic">(.*?)<\/div>/s', $html, $matches);
    $combined = '';
    foreach ($matches[1] as $block) {
        $combined .= ' '.html_entity_decode(strip_tags($block), ENT_QUOTES | ENT_HTML5);
    }

    return normalizeArabic($combined);
}

/** Score candidate against canonical: containment beats similarity. */
function scoreCandidate(string $candidateNorm, string $canonicalNorm): float
{
    if ($candidateNorm === '' || $canonicalNorm === '') {
        return 0.0;
    }
    if (str_contains($canonicalNorm, $candidateNorm) || str_contains($candidateNorm, $canonicalNorm)) {
        return 100.0;
    }
    similar_text(mb_substr($candidateNorm, 0, 2000), mb_substr($canonicalNorm, 0, 2000), $percent);

    return (float) $percent;
}

$decisions = [];
$counts = ['keep_winner' => 0, 'reimport_loser' => 0, 'inconclusive' => 0, 'fetch_failed' => 0];

foreach ($cases as $case) {
    $loserFile = $case['file'];
    $winnerFile = $case['winner'];

    $winnerRow = DB::table('hadith_hadiths')
        ->where('source_file', $winnerFile)
        ->select(['id', 'source_url', 'hadith_number'])
        ->first();

    if (! $winnerRow || empty($winnerRow->source_url)) {
        $counts['fetch_failed']++;
        $decisions[] = ['file' => $loserFile, 'decision' => 'fetch_failed', 'note' => 'winner row or canonical URL missing'];

        continue;
    }

    $html = fetchCanonicalPage($winnerRow->source_url, $cacheDir);
    if ($html === null) {
        $counts['fetch_failed']++;
        $decisions[] = ['file' => $loserFile, 'decision' => 'fetch_failed', 'url' => $winnerRow->source_url];

        continue;
    }

    $canonicalNorm = canonicalArabicFromHtml($html);

    $winnerArText = (string) DB::table('hadith_translations')
        ->where('hadith_id', $winnerRow->id)
        ->where('language_code', 'ar')
        ->value('text');

    $loserJson = json_decode((string) file_get_contents($sourceRoot.'/'.$loserFile), true);
    $loserRecord = $transformer->transform($loserJson)['records'][0];
    $loserArText = '';
    foreach ($loserRecord['translations'] as $translation) {
        if (($translation['language_code'] ?? '') === 'ar') {
            $loserArText = (string) $translation['text'];
            break;
        }
    }

    $winnerScore = scoreCandidate(normalizeArabic($winnerArText), $canonicalNorm);
    $loserScore = scoreCandidate(normalizeArabic($loserArText), $canonicalNorm);

    if ($loserScore >= 80.0 && $loserScore > $winnerScore + 10.0) {
        $decision = 'reimport_loser';
    } elseif ($winnerScore >= $loserScore) {
        $decision = 'keep_winner';
    } else {
        $decision = 'inconclusive';
    }

    $counts[$decision]++;
    $decisions[] = [
        'file' => $loserFile,
        'winner_file' => $winnerFile,
        'number' => $winnerRow->hadith_number,
        'url' => $winnerRow->source_url,
        'winner_score' => round($winnerScore, 1),
        'loser_score' => round($loserScore, 1),
        'decision' => $decision,
    ];
}

echo "Adjudication summary:\n";
foreach ($counts as $decision => $count) {
    echo "  {$decision}: {$count}\n";
}

$outputPath = storage_path('app/hadith_collision_adjudication.json');
file_put_contents($outputPath, json_encode($decisions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
echo "Detail written to: {$outputPath}\n";

if (! $apply) {
    echo "\nDry run — pass --apply to re-import canonical losers.\n";
    exit(0);
}

// ---------------------------------------------------------------------------
// Apply: re-import the loser file where it is the canonical text.
// ---------------------------------------------------------------------------
$applied = 0;
$failed = 0;
foreach ($decisions as $decision) {
    if ($decision['decision'] !== 'reimport_loser') {
        continue;
    }

    $filePath = $sourceRoot.'/'.$decision['file'];

    try {
        $importService->importFromFile($filePath);
        $applied++;
        echo "re-imported: {$decision['file']} -> #{$decision['number']} ({$decision['url']})\n";
    } catch (Throwable $e) {
        $failed++;
        fwrite(STDERR, "FAILED: {$decision['file']} — {$e->getMessage()}\n");
    }
}

echo "\nApplied: {$applied}, Failed: {$failed}\n";
