<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\DTOs;

use App\Domain\Hadith\Models\HadithTheme;
use JsonSerializable;

/**
 * Theme Data Transfer Object
 *
 * Represents HadithTheme data for API responses.
 */
final readonly class ThemeDTO implements JsonSerializable
{
    /**
     * @param  array<ThemeDTO>  $children
     */
    public function __construct(
        public int $id,
        public string $nameAr,
        public ?string $nameEn,
        public string $slugAr,
        public ?string $slugEn,
        public ?string $descriptionAr,
        public ?string $descriptionEn,
        public ?int $parentId,
        public ?string $parentName,
        public int $sortOrder,
        public int $hadithsCount,
        public ?string $createdAt,
        public ?string $updatedAt,
        public array $children = [],
    ) {}

    /**
     * Create from HadithTheme model
     */
    public static function fromModel(HadithTheme $theme, string $locale = 'ar', bool $withChildren = false): self
    {
        $children = [];
        if ($withChildren && $theme->children && $theme->children->isNotEmpty()) {
            foreach ($theme->children as $child) {
                $children[] = self::fromModel($child, $locale, true);
            }
        }

        return new self(
            id: $theme->id,
            nameAr: $theme->name_ar ?? '',
            nameEn: $theme->name_en,
            slugAr: $theme->slug_ar ?? '',
            slugEn: $theme->slug_en,
            descriptionAr: $theme->description_ar,
            descriptionEn: $theme->description_en,
            parentId: $theme->parent_id,
            parentName: $theme->parent?->name_ar ?? $theme->parent?->name_en,
            sortOrder: $theme->sort_order ?? 0,
            hadithsCount: $theme->hadiths_count ?? $theme->hadiths()->count(),
            createdAt: $theme->created_at?->toIso8601String(),
            updatedAt: $theme->updated_at?->toIso8601String(),
            children: $children,
        );
    }

    /**
     * Get name in specified locale
     */
    public function getName(string $locale = 'ar'): string
    {
        return $locale === 'ar' ? $this->nameAr : ($this->nameEn ?? $this->nameAr);
    }

    /**
     * Get slug in specified locale
     */
    public function getSlug(string $locale = 'ar'): string
    {
        return $locale === 'ar' ? $this->slugAr : ($this->slugEn ?? $this->slugAr);
    }

    /**
     * Get description in specified locale
     */
    public function getDescription(string $locale = 'ar'): ?string
    {
        return $locale === 'ar' ? $this->descriptionAr : ($this->descriptionEn ?? $this->descriptionAr);
    }

    /**
     * Check if theme has children
     */
    public function hasChildren(): bool
    {
        return ! empty($this->children);
    }

    /**
     * Check if theme is a root theme (no parent)
     */
    public function isRoot(): bool
    {
        return $this->parentId === null;
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
            'slug_ar' => $this->slugAr,
            'slug_en' => $this->slugEn,
            'description_ar' => $this->descriptionAr,
            'description_en' => $this->descriptionEn,
            'parent_id' => $this->parentId,
            'parent_name' => $this->parentName,
            'sort_order' => $this->sortOrder,
            'hadiths_count' => $this->hadithsCount,
            'children' => array_map(fn (ThemeDTO $child) => $child->toArray(), $this->children),
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
