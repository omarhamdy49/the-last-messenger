<?php

declare(strict_types=1);

namespace App\Domain\Quran\DTOs;

use App\Domain\Quran\Models\Verse;

/**
 * Editor changes to a verse. Saving any script text locks the verse against QF
 * re-imports unless the editor explicitly unlocks it.
 */
final readonly class VerseEditData
{
    /**
     * @param  array<string, string|null>  $texts  script column => text (only submitted columns)
     */
    public function __construct(
        public array $texts,
        public ?string $sajdahType,
        public ?int $sajdahNumber,
        public ?bool $isLocked,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(array $validated): self
    {
        $texts = array_intersect_key($validated, array_flip(Verse::TEXT_COLUMNS));

        return new self(
            texts: array_map(static fn ($v): ?string => $v === null ? null : (string) $v, $texts),
            sajdahType: isset($validated['sajdah_type']) && $validated['sajdah_type'] !== '' ? (string) $validated['sajdah_type'] : null,
            sajdahNumber: isset($validated['sajdah_number']) && $validated['sajdah_number'] !== '' ? (int) $validated['sajdah_number'] : null,
            isLocked: array_key_exists('is_locked', $validated) ? (bool) $validated['is_locked'] : null,
        );
    }
}
