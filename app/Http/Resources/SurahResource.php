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
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'name' => $this->name,
            'meaning' => $this->meaning,
            'revelation_place' => $this->revelation_place->value,
            'revelation_place_label' => $this->revelation_place->label(),
            'revelation_place_label_ar' => $this->revelation_place->labelArabic(),
            'revelation_order' => $this->revelation_order,
            'verses_count' => $this->verses_count,
            'words_count' => $this->words_count,
            'letters_count' => $this->letters_count,
            'juz' => $this->juz,
            'description' => $this->when($request->route('number'), $this->description),
            'theme' => $this->when($request->route('number'), $this->theme),
            'audio_urls' => $this->when($request->route('number'), $this->audio_urls),
            'page_number' => $this->page_number,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
