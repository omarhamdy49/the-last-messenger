<?php

declare(strict_types=1);

namespace App\Domain\Genealogy\DTOs;

/**
 * Admin listing filters for the Genealogy data table.
 * Carries validated request input across the controller -> service ->
 * repository boundary instead of a raw array.
 */
final readonly class PersonListFilters
{
    public function __construct(
        public ?string $search = null,
        public ?string $gender = null,
        public ?string $personType = null,
        public ?string $tribe = null,
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
            personType: $validated['person_type'] ?? null,
            tribe: filled($validated['tribe'] ?? null) ? trim($validated['tribe']) : null,
            isVerified: isset($validated['is_verified']) ? (bool) $validated['is_verified'] : null,
        );
    }

    public function hasAny(): bool
    {
        return $this->search !== null || $this->hasAdvanced();
    }

    /**
     * Whether any filter besides the search box is active — used to decide
     * whether the advanced-filters panel should auto-open.
     */
    public function hasAdvanced(): bool
    {
        return $this->gender !== null
            || $this->personType !== null
            || $this->tribe !== null
            || $this->isVerified !== null;
    }

    public function activeCount(): int
    {
        return (int) ($this->search !== null)
            + (int) ($this->gender !== null)
            + (int) ($this->personType !== null)
            + (int) ($this->tribe !== null)
            + (int) ($this->isVerified !== null);
    }
}
