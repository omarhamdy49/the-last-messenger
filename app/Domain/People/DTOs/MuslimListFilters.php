<?php

declare(strict_types=1);

namespace App\Domain\People\DTOs;

use App\Domain\People\Enums\Gender;
use Illuminate\Http\Request;

/**
 * `array $data` never crosses a layer boundary (constitution III).
 */
final readonly class MuslimListFilters
{
    private const MAX_PER_PAGE = 100;

    public function __construct(
        public ?string $roleSlug = null,
        public ?bool $featured = null,
        public ?Gender $gender = null,
        public ?string $search = null,
        public bool $publishedOnly = true,
        public int $perPage = 20,
        /**
         * Omit one person from the results. The parent picker needs this: a
         * person must never be offered as their own father or mother.
         */
        public ?int $excludeId = null,
        /**
         * Newest first instead of by id. The picker opens on "the latest
         * people", because a just-imported person is the one an editor is
         * most likely to be looking for.
         */
        public bool $latestFirst = false,
        /**
         * The editor's review queue: people who record a birthplace in text but
         * carry no canonical place reference. People with no birthplace at all
         * are excluded — they are not unresolved, they simply record none.
         */
        public bool $unmappedBirthPlaceOnly = false,
        /**
         * Tri-state publication filter for the admin list: null = both, true =
         * published only, false = drafts only. Distinct from `publishedOnly`,
         * which is the public-surface guard and can only ever narrow.
         */
        public ?bool $published = null,
        /** Records hidden from the genealogy tree. null = don't filter. */
        public ?bool $hiddenFromTree = null,
    ) {}

    public static function fromRequest(Request $request, bool $publishedOnly = true): self
    {
        $gender = $request->query('gender');

        return new self(
            roleSlug: $request->filled('role') ? (string) $request->query('role') : null,
            featured: $request->has('featured') ? $request->boolean('featured') : null,
            gender: is_string($gender) ? Gender::tryFrom($gender) : null,
            search: $request->filled('q') ? trim((string) $request->query('q')) : null,
            publishedOnly: $publishedOnly,
            // Cast and cap — an uncapped per_page is an unbounded query
            // (constitution II).
            perPage: min(max((int) $request->query('per_page', 20), 1), self::MAX_PER_PAGE),
        );
    }
}
