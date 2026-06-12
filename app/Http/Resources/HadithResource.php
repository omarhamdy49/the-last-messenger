<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Hadith API Resource
 *
 * Transforms Hadith model for API responses.
 *
 * @mixin \App\Domain\Hadith\Models\Hadith
 */
class HadithResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = $request->header('Accept-Language', 'ar');

        return [
            'id' => $this->id,
            'hadith_number' => $this->hadith_number,
            'grading_status' => $this->grading_status,
            'grading_agreement' => $this->grading_agreement,
            'is_authentic' => $this->isAuthentic(),
            'book' => $this->whenLoaded('book', fn () => [
                'id' => $this->book->id,
                'title' => $this->book->getTitleInLocale($locale),
                'collection' => $this->when(
                    $this->book->relationLoaded('collection'),
                    fn () => [
                        'id' => $this->book->collection->id,
                        'name' => $this->book->collection->getNameInLocale($locale),
                        'abbreviation' => $this->book->collection->abbreviation,
                    ]
                ),
            ]),
            'companion' => $this->whenLoaded('companion', fn () => [
                'id' => $this->companion->id,
                'name' => $locale === 'ar' ? $this->companion->name_ar : $this->companion->name_en,
            ]),
            'translations' => $this->whenLoaded('translations', function () {
                return $this->translations->map(fn ($t) => [
                    'language' => $t->language_code,
                    'text' => $t->text,
                    'translation' => $t->translation,
                ]);
            }),
            'themes' => $this->whenLoaded('themes', function () use ($locale) {
                return $this->themes->map(fn ($t) => [
                    'id' => $t->id,
                    'name' => $locale === 'ar' ? $t->name_ar : $t->name_en,
                ]);
            }),
            'gradings' => $this->whenLoaded('gradings', function () use ($locale) {
                return $this->gradings->map(fn ($g) => [
                    'grade' => $g->grade,
                    'scholar' => $g->scholar?->{'name_'.$locale},
                ]);
            }),
            'significance' => $locale === 'ar' ? $this->significance_ar : $this->significance_en,
            'historical_context' => $locale === 'ar' ? $this->historical_context_ar : $this->historical_context_en,
            'full_reference' => $this->when(
                $this->relationLoaded('book') && $this->book?->relationLoaded('collection'),
                fn () => $this->getFullReference()
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
