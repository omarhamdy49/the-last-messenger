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
     * Columns from quran_verse_translations: id, verse_id, resource_id, lang, text, text_notashkeel.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Transliteration rows (lang `*-tl`) carry tajwid markup tags (<u>/<b>) from the
        // source data that would otherwise leak as literal text to API consumers.
        $isTransliteration = str_ends_with((string) $this->lang, '-tl');

        return [
            'id' => $this->id,
            'verse_id' => $this->verse_id,
            'resource_id' => $this->resource_id,
            'lang' => $this->lang,
            'edition' => $this->whenLoaded('resource', fn () => [
                'id' => $this->resource->id,
                'slug' => $this->resource->slug,
                'name' => $this->resource->getName(app()->getLocale()),
                'language' => $this->resource->language,
                'is_default' => $this->resource->is_default,
            ]),
            'text' => $isTransliteration ? $this->stripMarkup($this->text) : $this->text,
            'text_notashkeel' => $this->text_notashkeel,
        ];
    }

    /**
     * Strip embedded HTML/tajwid markup and collapse whitespace.
     */
    private function stripMarkup(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        return trim((string) preg_replace('/\s+/', ' ', strip_tags($value)));
    }
}
