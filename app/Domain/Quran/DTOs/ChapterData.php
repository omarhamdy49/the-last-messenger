<?php

declare(strict_types=1);

namespace App\Domain\Quran\DTOs;

/**
 * Editable chapter fields. The corpus columns (names from QF, verse counts, pages)
 * are not editable; editors localize the name, publish/unpublish, link a location
 * and set a cover.
 */
final readonly class ChapterData
{
    /**
     * @param  array<string, string|null>  $name  locale => name
     */
    public function __construct(
        public array $name,
        public bool $isPublished,
        public ?int $locationId,
        public ?string $coverPath,
        public bool $removeCover,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(array $validated, ?string $coverPath = null): self
    {
        return new self(
            name: array_map(static fn ($v): ?string => $v === null ? null : (string) $v, (array) ($validated['name'] ?? [])),
            isPublished: (bool) ($validated['is_published'] ?? true),
            locationId: isset($validated['revelation_location_id']) ? (int) $validated['revelation_location_id'] : null,
            coverPath: $coverPath,
            removeCover: (bool) ($validated['remove_cover'] ?? false),
        );
    }
}
