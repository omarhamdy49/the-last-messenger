<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Domain\Quran\Models\Surah
 */
class SurahResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * Columns from quran_surahs: id, revelation_place, revelation_order,
     * bismillah_pre, name_ar, name_en, transliteration, verses_count, pages.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'number'           => $this->revelation_order,
            'name_ar'          => $this->name_ar,
            'name_en'          => $this->name_en,
            'transliteration'  => $this->transliteration,
            'revelation_place' => $this->revelation_place,
            'revelation_order' => $this->revelation_order,
            'bismillah_pre'    => $this->bismillah_pre,
            'verses_count'     => $this->verses_count,
            'pages'            => $this->pages,
            'created_at'       => $this->created_at?->toIso8601String(),
            'updated_at'       => $this->updated_at?->toIso8601String(),
        ];
    }
}
