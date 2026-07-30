<?php

declare(strict_types=1);

namespace App\Domain\Hadith\DTOs;

use App\Domain\Hadith\Enums\HadithGrade;

/**
 * A validated search request.
 *
 * `perPage()` is a method rather than a property so the cap cannot be bypassed
 * by constructing the DTO directly: an unbounded `per_page` is how a public
 * search endpoint becomes a denial-of-service vector.
 */
final readonly class SearchCriteria
{
    public const MAX_PER_PAGE = 100;

    private const DEFAULT_PER_PAGE = 20;

    public function __construct(
        public string $query,
        public ?string $collectionCode = null,
        public ?HadithGrade $grade = null,
        public ?string $locale = null,
        public int $page = 1,
        private int $requestedPerPage = self::DEFAULT_PER_PAGE,
    ) {}

    /**
     * Oversized values clamp rather than error, per contracts/hadith-api.md.
     */
    public function perPage(): int
    {
        return max(1, min($this->requestedPerPage, self::MAX_PER_PAGE));
    }

    public function page(): int
    {
        return max(1, $this->page);
    }

    /**
     * @param  array<string, mixed>  $validated  output of HadithSearchRequest
     */
    public static function fromValidated(array $validated): self
    {
        $grade = $validated['grade'] ?? null;

        return new self(
            query: (string) $validated['q'],
            collectionCode: $validated['collection'] ?? null,
            grade: $grade !== null ? HadithGrade::from((string) $grade) : null,
            locale: $validated['locale'] ?? null,
            page: (int) ($validated['page'] ?? 1),
            requestedPerPage: (int) ($validated['per_page'] ?? self::DEFAULT_PER_PAGE),
        );
    }
}
