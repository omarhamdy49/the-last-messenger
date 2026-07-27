<?php

declare(strict_types=1);

namespace App\Domain\Sahaba\DTOs;

/**
 * Admin listing filters for the Sahaba data table.
 * Carries validated request input across the controller -> service ->
 * repository boundary instead of a raw array.
 */
final readonly class SahabiListFilters
{
    /**
     * @param  array<int, string>  $categories  Category keys (badr, ansar, muhajir, ...)
     */
    public function __construct(
        public ?string $search = null,
        public ?string $gender = null,
        public array $categories = [],
        public ?string $tribe = null,
        public ?bool $isFeatured = null,
        public ?bool $isVerified = null,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            search: filled($validated['search'] ?? null) ? trim($validated['search']) : null,
            gender: $validated['gender'] ?? null,
            categories: $validated['categories'] ?? [],
            tribe: filled($validated['tribe'] ?? null) ? trim($validated['tribe']) : null,
            isFeatured: isset($validated['is_featured']) ? (bool) $validated['is_featured'] : null,
            isVerified: isset($validated['is_verified']) ? (bool) $validated['is_verified'] : null,
        );
    }

    public function hasAny(): bool
    {
        return $this->search !== null || $this->hasAdvanced();
    }

    /**
     * Whether any filter besides the search box is active — used to decide
     * whether the advanced-filters panel should auto-open. A plain name
     * search has its own always-visible input and shouldn't pop the panel.
     */
    public function hasAdvanced(): bool
    {
        return $this->gender !== null
            || $this->categories !== []
            || $this->tribe !== null
            || $this->isFeatured !== null
            || $this->isVerified !== null;
    }

    public function activeCount(): int
    {
        return (int) ($this->search !== null)
            + (int) ($this->gender !== null)
            + (int) ($this->categories !== [])
            + (int) ($this->tribe !== null)
            + (int) ($this->isFeatured !== null)
            + (int) ($this->isVerified !== null);
    }
}
