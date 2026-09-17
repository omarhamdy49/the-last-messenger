<?php

declare(strict_types=1);

namespace App\Domain\Shared\Contracts;

use App\Domain\Shared\Enums\LinkedContentType;

/**
 * Resolves ids of content in other modules to {@see ContentReference} values.
 *
 * Lets a domain module keep cross-module links as bare ids (database keys may
 * cross modules; PHP imports may not — constitution IV). The application layer
 * supplies the implementation.
 */
interface ContentReferenceLookup
{
    /**
     * One query per type present (never per id). Unknown ids are omitted, and
     * with $publicOnly so is anything a visitor may not see (draft article,
     * unpublished episode, hidden hadith, …). Result preserves the input order.
     *
     * @param  array<string, array<int, int>>  $idsByType  LinkedContentType value => ids
     * @return array<string, array<int, ContentReference>> type value => list of references in input order
     */
    public function resolve(array $idsByType, string $locale, bool $publicOnly = false): array;

    /**
     * id => localized label for a picker, for types small enough to list whole
     * (tags, categories). Empty for other types.
     *
     * @return array<int, string>
     */
    public function options(LinkedContentType $type, string $locale): array;
}
