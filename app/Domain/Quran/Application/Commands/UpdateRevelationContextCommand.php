<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Commands;

/**
 * Command to update a revelation context (Asbab al-Nuzul)
 */
final readonly class UpdateRevelationContextCommand
{
    /**
     * @param  array<string, array<string, string|null>>  $translations  field => locale => value
     */
    public function __construct(
        public int $id,
        public int $surahId,
        public int $verseFrom,
        public ?int $verseTo,
        public string $period,
        public ?string $eventKey,
        public int $displayOrder,
        public bool $isActive,
        public array $translations,
    ) {}

    /**
     * Build from validated FormRequest data.
     *
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(int $id, array $validated): self
    {
        return new self(
            id: $id,
            surahId: (int) $validated['surah_id'],
            verseFrom: (int) $validated['verse_from'],
            verseTo: isset($validated['verse_to']) && $validated['verse_to'] !== null
                ? (int) $validated['verse_to']
                : null,
            period: (string) $validated['period'],
            eventKey: $validated['event_key'] ?? null,
            displayOrder: (int) ($validated['display_order'] ?? 0),
            isActive: (bool) ($validated['is_active'] ?? true),
            translations: [
                'title' => $validated['title'] ?? [],
                'narrative' => $validated['narrative'] ?? [],
                'source_reference' => $validated['source_reference'] ?? [],
            ],
        );
    }
}
