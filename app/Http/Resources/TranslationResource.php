<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Domain\Quran\Models\VerseTranslation
 */
class TranslationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * Columns from quran_verse_translations: id, verse_id, lang, text, text_notashkeel.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'verse_id'        => $this->verse_id,
            'lang'            => $this->lang,
            'text'            => $this->text,
            'text_notashkeel' => $this->text_notashkeel,
        ];
    }
}
