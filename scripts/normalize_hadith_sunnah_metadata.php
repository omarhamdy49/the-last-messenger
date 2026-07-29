<?php

declare(strict_types=1);

/**
 * Backfill nested Sunnah metadata from canonical collection metadata.
 *
 * Collection repair scripts normalize `collections[0]` first because the
 * importer reads that block. This script completes the newer nested
 * `metadata.sunnah_reference` and `source_verification.sunnah` blocks for
 * older rows whose top-level collection metadata is already canonical.
 *
 * Usage:
 *   php scripts/normalize_hadith_sunnah_metadata.php
 *   php scripts/normalize_hadith_sunnah_metadata.php --apply
 */
$apply = in_array('--apply', array_slice($argv, 1), true);
$sourceRoot = dirname(__DIR__, 2).'/prophet-data-generator/hadith/production';

foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--source=')) {
        $sourceRoot = substr($arg, strlen('--source='));
    }
}

if (! is_dir($sourceRoot)) {
    fwrite(STDERR, "Source directory not found: {$sourceRoot}\n");
    exit(1);
}

function sunnahSlugFromUrl(string $url, string $fallback): string
{
    if (preg_match('#sunnah\.com/([^/:?]+)#', $url, $match)) {
        return $match[1];
    }

    return $fallback;
}

$checked = 0;
$updated = 0;
$missingSourceUrl = [];
$invalid = [];
$collections = [];

foreach (glob(rtrim($sourceRoot, '/').'/*', GLOB_ONLYDIR) ?: [] as $collectionDir) {
    $collectionName = basename($collectionDir);

    foreach (glob($collectionDir.'/hadith_*_final.json') ?: [] as $file) {
        $checked++;
        $json = json_decode((string) file_get_contents($file), true);
        if (! is_array($json)) {
            $invalid[] = $file;

            continue;
        }

        $collection = $json['collections'][0] ?? null;
        if (! is_array($collection)) {
            $missingSourceUrl[] = $file;

            continue;
        }

        $sourceUrl = trim((string) ($collection['source_url'] ?? ''));
        if ($sourceUrl === '') {
            $missingSourceUrl[] = $file;

            continue;
        }

        $slug = sunnahSlugFromUrl($sourceUrl, $collectionName);
        $bookNumber = (string) ($collection['book_number'] ?? '');
        $hadithNumber = (string) ($collection['hadith_number'] ?? ($json['metadata']['id_in_book'] ?? ''));

        $changed = false;
        if (($collection['verified'] ?? null) !== true) {
            $json['collections'][0]['verified'] = true;
            $changed = true;
        }
        if (($collection['source_verified'] ?? null) !== true) {
            $json['collections'][0]['source_verified'] = true;
            $changed = true;
        }

        $existingReference = $json['metadata']['sunnah_reference'] ?? [];
        if (! is_array($existingReference)) {
            $existingReference = [];
        }
        $nextReference = $existingReference + [
            'urn' => null,
            'urn_en' => null,
        ];
        $nextReference['collection'] = (string) ($nextReference['collection'] ?? '') !== '' ? $nextReference['collection'] : $slug;
        $nextReference['book_number'] = (string) ($nextReference['book_number'] ?? '') !== '' ? $nextReference['book_number'] : $bookNumber;
        $nextReference['hadith_number'] = (string) ($nextReference['hadith_number'] ?? '') !== '' ? $nextReference['hadith_number'] : $hadithNumber;
        $nextReference['url'] = (string) ($nextReference['url'] ?? '') !== '' ? $nextReference['url'] : $sourceUrl;
        if ($existingReference !== $nextReference) {
            $json['metadata']['sunnah_reference'] = $nextReference;
            $changed = true;
        }

        $existingVerification = $json['source_verification']['sunnah'] ?? [];
        if (! is_array($existingVerification)) {
            $existingVerification = [];
        }
        $nextVerification = $existingVerification;
        $nextVerification['status'] = (string) ($nextVerification['status'] ?? '') !== '' ? $nextVerification['status'] : 'source_verified';
        $nextVerification['collection'] = (string) ($nextVerification['collection'] ?? '') !== '' ? $nextVerification['collection'] : $slug;
        $nextVerification['book_number'] = (string) ($nextVerification['book_number'] ?? '') !== '' ? $nextVerification['book_number'] : $bookNumber;
        $nextVerification['hadith_number'] = (string) ($nextVerification['hadith_number'] ?? '') !== '' ? $nextVerification['hadith_number'] : $hadithNumber;
        $nextVerification['url'] = (string) ($nextVerification['url'] ?? '') !== '' ? $nextVerification['url'] : $sourceUrl;
        if (($json['source_verification']['sunnah'] ?? null) !== $nextVerification) {
            $json['source_verification']['sunnah'] = $nextVerification;
            $changed = true;
        }

        if (! $changed) {
            continue;
        }

        $collections[$collectionName] = ($collections[$collectionName] ?? 0) + 1;
        if ($apply) {
            file_put_contents($file, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
        }
        $updated++;
    }
}

ksort($collections);
echo ($apply ? 'apply' : 'dry-run')
    ." checked={$checked}"
    ." updated={$updated}"
    .' missing_source_url='.count($missingSourceUrl)
    .' invalid='.count($invalid)
    ."\n";

foreach ($collections as $collection => $count) {
    echo "- {$collection}: {$count}\n";
}

if ($missingSourceUrl !== []) {
    echo 'Missing source URLs: '.implode(', ', array_slice(array_map('basename', $missingSourceUrl), 0, 40)).(count($missingSourceUrl) > 40 ? ' ...' : '')."\n";
}
if ($invalid !== []) {
    echo 'Invalid JSON: '.implode(', ', array_slice(array_map('basename', $invalid), 0, 40)).(count($invalid) > 40 ? ' ...' : '')."\n";
}

exit($missingSourceUrl === [] && $invalid === [] ? 0 : 1);
