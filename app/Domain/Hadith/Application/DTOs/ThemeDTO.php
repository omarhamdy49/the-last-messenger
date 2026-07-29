<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\DTOs;

use App\Domain\Hadith\Models\HadithTheme;
use JsonSerializable;

/**
 * Theme Data Transfer Object
 */
final readonly class ThemeDTO implements JsonSerializable
{
    public function __construct(
        public int $id,
        public ?string $themeAr,
        public ?string $themeEn,
        public ?string $name,
        public ?string $description,
        public ?string $reviewStatus,
        public ?string $sourceReference,
        public int $hadithsCount,
    ) {}

    /**
     * Create from HadithTheme model.
     */
    public static function fromModel(HadithTheme $theme, string $locale = 'ar'): self
    {
        return new self(
            id: $theme->id,
            themeAr: $theme->theme_ar,
            themeEn: $theme->theme_en,
            name: $theme->getNameInLocale($locale),
            description: null,
            reviewStatus: $theme->review_status,
            sourceReference: $theme->source_reference,
            hadithsCount: $theme->hadiths_count ?? $theme->hadiths()->count(),
        );
    }

    /**
     * Get name in specified locale.
     */
    public function getName(string $locale = 'ar'): string
    {
        return $locale === 'ar'
            ? ($this->themeAr ?? $this->themeEn ?? $this->name ?? '')
            : ($this->themeEn ?? $this->themeAr ?? $this->name ?? '');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'theme_ar' => $this->themeAr,
            'theme_en' => $this->themeEn,
            'name' => $this->name,
            'description' => $this->description,
            'review_status' => $this->reviewStatus,
            'source_reference' => $this->sourceReference,
            'hadiths_count' => $this->hadithsCount,
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
