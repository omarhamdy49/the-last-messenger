<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\DTOs;

use App\Domain\Hadith\Models\HadithNarrator;
use JsonSerializable;

/**
 * Narrator Data Transfer Object
 *
 * Represents HadithNarrator data for API responses.
 */
final readonly class NarratorDTO implements JsonSerializable
{
    public function __construct(
        public int $id,
        public string $nameAr,
        public ?string $nameEn,
        public ?string $kuniya,
        public ?string $nasab,
        public ?string $laqab,
        public ?int $birthYear,
        public ?int $deathYear,
        public ?string $biographyAr,
        public ?string $biographyEn,
        public ?string $reliability,
        public int $hadithsCount,
        public ?string $createdAt,
        public ?string $updatedAt,
    ) {}

    /**
     * Create from HadithNarrator model
     */
    public static function fromModel(HadithNarrator $narrator, string $locale = 'ar'): self
    {
        return new self(
            id: $narrator->id,
            nameAr: $narrator->name_ar ?? '',
            nameEn: $narrator->name_en,
            kuniya: $narrator->kuniya,
            nasab: $narrator->nasab,
            laqab: $narrator->laqab,
            birthYear: $narrator->birth_year,
            deathYear: $narrator->death_year,
            biographyAr: $narrator->biography_ar,
            biographyEn: $narrator->biography_en,
            reliability: $narrator->reliability,
            hadithsCount: $narrator->hadiths_count ?? 0,
            createdAt: $narrator->created_at?->toIso8601String(),
            updatedAt: $narrator->updated_at?->toIso8601String(),
        );
    }

    /**
     * Get full name with kuniya
     */
    public function getFullName(string $locale = 'ar'): string
    {
        $name = $locale === 'ar' ? $this->nameAr : ($this->nameEn ?? $this->nameAr);

        if ($this->kuniya) {
            return "{$this->kuniya} {$name}";
        }

        return $name;
    }

    /**
     * Get name in specified locale
     */
    public function getName(string $locale = 'ar'): string
    {
        return $locale === 'ar' ? $this->nameAr : ($this->nameEn ?? $this->nameAr);
    }

    /**
     * Get biography in specified locale
     */
    public function getBiography(string $locale = 'ar'): ?string
    {
        return $locale === 'ar' ? $this->biographyAr : ($this->biographyEn ?? $this->biographyAr);
    }

    /**
     * Get life span as string
     */
    public function getLifeSpan(): ?string
    {
        if ($this->birthYear === null && $this->deathYear === null) {
            return null;
        }

        $birth = $this->birthYear ?? '?';
        $death = $this->deathYear ?? '?';

        return "{$birth} - {$death} هـ";
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name_ar' => $this->nameAr,
            'name_en' => $this->nameEn,
            'kuniya' => $this->kuniya,
            'nasab' => $this->nasab,
            'laqab' => $this->laqab,
            'birth_year' => $this->birthYear,
            'death_year' => $this->deathYear,
            'biography_ar' => $this->biographyAr,
            'biography_en' => $this->biographyEn,
            'reliability' => $this->reliability,
            'hadiths_count' => $this->hadithsCount,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
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
