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
