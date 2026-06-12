<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Domain\Quran\Models\VerseTafsir
 */
class TafseerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    /**
     * Columns from quran_verse_tafsirs: id, verse_id, verse_key, lang, text, active.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'verse_id' => $this->verse_id,
            'verse_key' => $this->verse_key,
            'lang' => $this->lang,
            'text' => $this->text,
            'active' => $this->active,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
