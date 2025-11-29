<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\DTOs;

use App\Domain\Quran\Models\SurahInfo;
use JsonSerializable;

/**
 * Surah Info Data Transfer Object
 *
 * Represents SurahInfo (asbab al-nuzul) data for API responses.
 */
final readonly class SurahInfoDTO implements JsonSerializable
{
    public function __construct(
        public int $id,
        public int $surahId,
        public string $lang,
        public ?string $description,
        public ?string $content,
        public ?string $source,
        public bool $isActive,
        public ?string $surahName,
        public ?string $createdAt,
        public ?string $updatedAt,
    ) {}

    /**
     * Create from SurahInfo model
     */
    public static function fromModel(SurahInfo $info, string $locale = 'ar'): self
    {
        $surahName = null;
        if ($info->relationLoaded('surah') && $info->surah) {
            $surahName = $locale === 'ar' ? $info->surah->name_ar : $info->surah->name_en;
        }

        return new self(
            id: $info->id,
            surahId: $info->surah_id,
            lang: $info->lang ?? 'ar',
            description: $info->desc,
            content: $info->content,
            source: $info->source,
            isActive: $info->active ?? true,
            surahName: $surahName,
            createdAt: $info->created_at?->toIso8601String(),
            updatedAt: $info->updated_at?->toIso8601String(),
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
            'lang' => $this->lang,
            'description' => $this->description,
            'content' => $this->content,
            'source' => $this->source,
            'is_active' => $this->isActive,
            'surah_name' => $this->surahName,
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
