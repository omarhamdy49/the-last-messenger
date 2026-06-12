<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\DTOs;

use App\Domain\Quran\Models\RevelationContext;
use JsonSerializable;

/**
 * Revelation Context (Asbab al-Nuzul) Data Transfer Object
 *
 * Locale-resolved representation for public listings and detail views.
 */
final readonly class RevelationContextDTO implements JsonSerializable
{
    public function __construct(
        public int $id,
        public int $surahId,
        public ?int $surahNumber,
        public ?string $surahNameAr,
        public ?string $surahNameEn,
        public int $verseFrom,
        public ?int $verseTo,
        public string $verseRange,
        public string $period,
        public ?string $eventKey,
        public ?string $title,
        public ?string $narrative,
        public ?string $sourceReference,
        public int $displayOrder,
        public bool $isActive,
        public ?string $verseExcerpt,
    ) {}

    /**
     * Create from RevelationContext model (translations + surah must be eager-loaded).
     */
    public static function fromModel(
        RevelationContext $context,
        string $locale = 'ar',
        ?string $verseExcerpt = null,
    ): self {
        $surah = $context->relationLoaded('surah') ? $context->surah : null;

        return new self(
            id: $context->id,
            surahId: $context->surah_id,
            surahNumber: $surah?->revelation_order,
            surahNameAr: $surah?->name_ar,
            surahNameEn: $surah?->name_en,
            verseFrom: $context->verse_from,
            verseTo: $context->verse_to,
            verseRange: $context->verse_range,
            period: $context->period,
            eventKey: $context->event_key,
            title: $context->getTranslation('title', $locale),
            narrative: $context->getTranslation('narrative', $locale),
            sourceReference: $context->getTranslation('source_reference', $locale),
            displayOrder: $context->display_order,
            isActive: $context->is_active,
            verseExcerpt: $verseExcerpt,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'surah_id' => $this->surahId,
            'surah_number' => $this->surahNumber,
            'surah_name_ar' => $this->surahNameAr,
            'surah_name_en' => $this->surahNameEn,
            'verse_from' => $this->verseFrom,
            'verse_to' => $this->verseTo,
            'verse_range' => $this->verseRange,
            'period' => $this->period,
            'event_key' => $this->eventKey,
            'title' => $this->title,
            'narrative' => $this->narrative,
            'source_reference' => $this->sourceReference,
            'display_order' => $this->displayOrder,
            'is_active' => $this->isActive,
            'verse_excerpt' => $this->verseExcerpt,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
