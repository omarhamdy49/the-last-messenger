<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\DTOs;

use App\Domain\Quran\Models\VerseTafsir;
use JsonSerializable;

/**
 * Tafsir Data Transfer Object
 *
 * Represents Tafsir (interpretation) data for API responses.
 */
final readonly class TafsirDTO implements JsonSerializable
{
    public function __construct(
        public int $id,
        public int $verseId,
        public string $verseKey,
        public string $lang,
        public string $text,
        public bool $isActive,
        public ?string $surahName,
        public ?int $verseNumber,
        public ?string $createdAt,
        public ?string $updatedAt,
    ) {}

    /**
     * Create from VerseTafsir model
     */
    public static function fromModel(VerseTafsir $tafsir, string $locale = 'ar'): self
    {
        $surahName = null;
        $verseNumber = null;

        if ($tafsir->relationLoaded('verse') && $tafsir->verse) {
            $verseNumber = $tafsir->verse->verse_number;
            if ($tafsir->verse->relationLoaded('surah') && $tafsir->verse->surah) {
                $surahName = $locale === 'ar'
                    ? $tafsir->verse->surah->name_ar
                    : $tafsir->verse->surah->name_en;
            }
        }

        return new self(
            id: $tafsir->id,
            verseId: $tafsir->verse_id,
            verseKey: $tafsir->verse_key ?? '',
            lang: $tafsir->lang ?? 'ar',
            text: $tafsir->text ?? '',
            isActive: $tafsir->active ?? true,
            surahName: $surahName,
            verseNumber: $verseNumber,
            createdAt: $tafsir->created_at?->toIso8601String(),
            updatedAt: $tafsir->updated_at?->toIso8601String(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'verse_id' => $this->verseId,
            'verse_key' => $this->verseKey,
            'lang' => $this->lang,
            'text' => $this->text,
            'is_active' => $this->isActive,
            'surah_name' => $this->surahName,
            'verse_number' => $this->verseNumber,
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
