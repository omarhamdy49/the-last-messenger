<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\DTOs;

use App\Domain\Quran\Models\Surah;
use JsonSerializable;

/**
 * Surah Data Transfer Object
 *
 * Represents Surah (chapter) data for API responses.
 */
final readonly class SurahDTO implements JsonSerializable
{
    public function __construct(
        public int $id,
        public int $number,
        public string $nameAr,
        public string $nameEn,
        public ?string $transliteration,
        public string $revelationPlace,
        public int $revelationOrder,
        public int $versesCount,
        public bool $bismillahPre,
        public ?array $pages,
        public ?string $description,
        public ?string $createdAt,
        public ?string $updatedAt,
    ) {}

    /**
     * Create from Surah model
     */
    public static function fromModel(Surah $surah, string $locale = 'ar'): self
    {
        // Get description from SurahInfo if loaded
        $description = null;
        if ($surah->relationLoaded('info')) {
            $info = $surah->info->where('lang', $locale)->first();
            $description = $info?->desc ?? $info?->content;
        }

        return new self(
            id: $surah->id,
            number: $surah->id, // In Quran, surah number is the ID (1-114)
            nameAr: $surah->name_ar ?? '',
            nameEn: $surah->name_en ?? '',
            transliteration: $surah->transliteration,
            revelationPlace: $surah->revelation_place ?? 'makkah',
            revelationOrder: $surah->revelation_order ?? 0,
            versesCount: $surah->verses_count ?? 0,
            bismillahPre: $surah->bismillah_pre ?? true,
            pages: $surah->pages,
            description: $description,
            createdAt: $surah->created_at?->toIso8601String(),
            updatedAt: $surah->updated_at?->toIso8601String(),
        );
    }

    /**
     * Get display name in locale
     */
    public function getDisplayName(string $locale = 'ar'): string
    {
        return $locale === 'ar' ? $this->nameAr : $this->nameEn;
    }

    /**
     * Check if Makkan revelation
     */
    public function isMakkan(): bool
    {
        return $this->revelationPlace === 'makkah';
    }

    /**
     * Check if Madinan revelation
     */
    public function isMadinan(): bool
    {
        return $this->revelationPlace === 'madinah';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'name_ar' => $this->nameAr,
            'name_en' => $this->nameEn,
            'transliteration' => $this->transliteration,
            'revelation_place' => $this->revelationPlace,
            'revelation_order' => $this->revelationOrder,
            'verses_count' => $this->versesCount,
            'bismillah_pre' => $this->bismillahPre,
            'pages' => $this->pages,
            'description' => $this->description,
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
