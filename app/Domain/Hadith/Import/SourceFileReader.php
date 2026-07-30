<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Import;

use RuntimeException;

/**
 * Reads one extracted book file off disk and validates its envelope.
 *
 * Extraction and import are deliberately separate steps: a parsing fix must
 * never require re-fetching 609 pages from a site that rate-limits and sits
 * behind Cloudflare.
 */
final class SourceFileReader
{
    /**
     * The largest book observed is well under a megabyte; this guards against
     * json_decode being handed something pathological, since decoding happens
     * fully in memory.
     */
    private const MAX_BYTES = 16 * 1024 * 1024;

    /**
     * @return array{source: array<string, mixed>, book: array<string, mixed>, chapters: list<array<string, mixed>>, records: list<array<string, mixed>>}
     *
     * @throws RuntimeException when the file is missing, oversized, or malformed
     */
    public function read(string $path): array
    {
        if (! is_file($path)) {
            throw new RuntimeException("Source file not found: {$path}");
        }

        $bytes = filesize($path);

        if ($bytes === false || $bytes > self::MAX_BYTES) {
            throw new RuntimeException("Source file is implausibly large: {$path}");
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        if (! is_array($decoded)) {
            throw new RuntimeException("Source file is not valid JSON: {$path} (".json_last_error_msg().')');
        }

        foreach (['source', 'book', 'chapters', 'records'] as $key) {
            if (! isset($decoded[$key]) || ! is_array($decoded[$key])) {
                throw new RuntimeException("Source file is missing the '{$key}' section: {$path}");
            }
        }

        // A truncated download that still parses is the dangerous case: the
        // import would succeed and quietly lose records.
        $expected = $decoded['counts']['records'] ?? null;

        if (is_int($expected) && $expected !== count($decoded['records'])) {
            throw new RuntimeException(sprintf(
                'Source file %s declares %d records but carries %d — refusing a partial import.',
                $path, $expected, count($decoded['records']),
            ));
        }

        return $decoded;
    }

    /**
     * Book files for one collection, in natural book order.
     *
     * @return list<string>
     */
    public function booksFor(string $directory): array
    {
        $files = glob(rtrim($directory, '/').'/book_*.json') ?: [];
        $files = array_values(array_filter($files, static fn (string $f): bool => ! str_contains($f, '.manifest.')));

        // Natural order so book_2 precedes book_10, and non-numeric books
        // (introduction, letter) sort predictably rather than arbitrarily.
        usort($files, static fn (string $a, string $b): int => strnatcasecmp($a, $b));

        return $files;
    }
}
