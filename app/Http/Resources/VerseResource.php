<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Domain\Quran\Models\Verse
 */
class VerseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'surah_id' => $this->surah_id,
            'verse_number' => $this->verse_number,
            'verse_key' => $this->verse_key,
            'reference' => $this->when(
                $this->relationLoaded('surah'),
                fn () => $this->getVerseKey()
            ),
            'text_uthmani' => $this->text_uthmani,
            'text_simple' => $this->text_simple,
            'text_imlaei' => $this->text_imlaei,
            'translations' => $this->translations,
            'transliteration' => $this->transliteration,
            'juz_number' => $this->juz_number,
            'hizb_number' => $this->hizb_number,
            'page_number' => $this->page_number,
            'rub_number' => $this->rub_number,
            'is_sajda' => $this->is_sajda,
            'sajda_type' => $this->when(
                $this->is_sajda && $this->sajda_type,
                fn () => [
                    'value' => $this->sajda_type->value,
                    'label' => $this->sajda_type->label(),
                    'label_ar' => $this->sajda_type->labelArabic(),
                ]
            ),
            'audio_urls' => $this->audio_urls,
            'words_count' => $this->words_count,
            'letters_count' => $this->letters_count,
            'surah' => new SurahResource($this->whenLoaded('surah')),
            'tafseer' => TafseerResource::collection($this->whenLoaded('tafseer')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
