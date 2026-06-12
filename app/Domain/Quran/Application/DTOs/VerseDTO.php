<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\DTOs;

use App\Domain\Quran\Models\Verse;
use JsonSerializable;

/**
 * Verse Data Transfer Object
 *
 * Represents Verse (ayah) data for API responses.
 */
final readonly class VerseDTO implements JsonSerializable
{
    /**
     * @param  array<string, string>  $translations
     */
    public function __construct(
        public int $id,
        public int $surahId,
        public int $verseNumber,
        public string $verseKey,
        public int $juzNumber,
        public int $hizbNumber,
        public int $rubElHizbNumber,
        public int $rukuNumber,
        public ?int $manzilNumber,
        public ?int $sajdahNumber,
        public int $pageNumber,
        public ?string $textArabic,
        public array $translations = [],
        public ?string $surahName = null,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
    ) {}

    /**
     * Create from Verse model
     */
    public static function fromModel(Verse $verse, string $locale = 'ar'): self
    {
        $translations = [];
        $textArabic = null;

        if ($verse->relationLoaded('translations')) {
            foreach ($verse->translations as $translation) {
                if ($translation->lang === 'ar') {
                    $textArabic = $translation->text;
                }
                $translations[$translation->lang] = $translation->text;
            }
        }

        return new self(
            id: $verse->id,
            surahId: $verse->surah_id,
            verseNumber: $verse->verse_number,
            verseKey: $verse->verse_key ?? "{$verse->surah_id}:{$verse->verse_number}",
            juzNumber: $verse->juz_number ?? 1,
            hizbNumber: $verse->hizb_number ?? 1,
            rubElHizbNumber: $verse->rub_el_hizb_number ?? 1,
            rukuNumber: $verse->ruku_number ?? 1,
            manzilNumber: $verse->manzil_number,
            sajdahNumber: $verse->sajdah_number,
            pageNumber: $verse->page_number ?? 1,
            textArabic: $textArabic,
            translations: $translations,
            surahName: $verse->relationLoaded('surah') ? $verse->surah->name_ar : null,
            createdAt: $verse->created_at?->toIso8601String(),
            updatedAt: $verse->updated_at?->toIso8601String(),
        );
    }

    /**
     * Get translation for specific language
     */
    public function getTranslation(string $lang): ?string
    {
        return $this->translations[$lang] ?? null;
    }

    /**
     * Check if verse has sajdah
     */
    public function hasSajdah(): bool
    {
        return $this->sajdahNumber !== null;
    }

    /**
     * Get verse reference
     */
    public function getReference(): string
    {
        return $this->verseKey;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'surah_id' => $this->surahId,
            'verse_number' => $this->verseNumber,
            'verse_key' => $this->verseKey,
            'juz_number' => $this->juzNumber,
            'hizb_number' => $this->hizbNumber,
            'rub_el_hizb_number' => $this->rubElHizbNumber,
            'ruku_number' => $this->rukuNumber,
            'manzil_number' => $this->manzilNumber,
            'sajdah_number' => $this->sajdahNumber,
            'page_number' => $this->pageNumber,
            'text_arabic' => $this->textArabic,
            'translations' => $this->translations,
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
