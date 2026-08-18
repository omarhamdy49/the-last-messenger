<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Quran\Models\Verse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Verse
 */
class VerseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * Columns from quran_verses: id, surah_id, verse_number, verse_key,
     * juz_number, hizb_number, rub_el_hizb_number, ruku_number,
     * manzil_number, sajdah_number, page_number.
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
            'juz_number' => $this->juz_number,
            'hizb_number' => $this->hizb_number,
            'rub_el_hizb_number' => $this->rub_el_hizb_number,
            'ruku_number' => $this->ruku_number,
            'manzil_number' => $this->manzil_number,
            'sajdah_number' => $this->sajdah_number,
            'is_sajda' => $this->sajdah_number !== null,
            'page_number' => $this->page_number,
            'translations' => TranslationResource::collection($this->whenLoaded('translations')),
            'tafsirs' => TafseerResource::collection($this->whenLoaded('tafsirs')),
            'surah' => new SurahResource($this->whenLoaded('surah')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
