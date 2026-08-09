<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Import\Sunnah;

use RuntimeException;

/**
 * Builds the `reference_key` segment used to identify a hadith within a
 * collection.
 *
 * Reference keys are the upsert identity for imports — `(collection_id,
 * reference_key)` is what makes a re-import idempotent rather than duplicating
 * every row. The two mappers previously carried their own copy of this logic
 * and had drifted: the collection mapper lowercased the result, the forty
 * mapper did not, so a number like "1A" produced a different key depending on
 * which import path ran. Lowercase is the canonical form (it matches all
 * existing stored keys).
 */
final class ReferenceKey
{
    public static function part(string $value): string
    {
        $part = preg_replace('/[^A-Za-z0-9._-]+/u', '-', trim($value)) ?? '';
        $part = trim($part, '-');

        if ($part === '') {
            throw new RuntimeException("Cannot derive a reference key from '{$value}'.");
        }

        return mb_strtolower($part, 'UTF-8');
    }
}
