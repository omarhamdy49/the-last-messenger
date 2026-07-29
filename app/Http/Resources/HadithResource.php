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
            'book' => $this->when(
                $this->relationLoaded('chapter') && $this->chapter?->relationLoaded('book'),
                fn () => [
                    'id' => $this->chapter->book->id,
                    'title' => $this->chapter->book->getTitleInLocale($locale),
                    'collection' => $this->when(
                        $this->chapter->book->relationLoaded('collection'),
                        fn () => [
                            'id' => $this->chapter->book->collection->id,
                            'name' => $this->chapter->book->collection->getNameInLocale($locale),
                            'abbreviation' => $this->chapter->book->collection->abbreviation,
                        ]
                    ),
                ],
            ),
            'primary_narrator' => $this->whenLoaded('primaryNarrator', fn () => [
                'id' => $this->primaryNarrator->id,
                'name' => $this->primaryNarrator->getDisplayName($locale),
            ]),
            'translations' => $this->whenLoaded('translations', function () {
                return $this->translations->map(fn ($t) => [
                    'language' => $t->locale,
                    'text' => $t->text,
                ]);
            }),
            'themes' => $this->whenLoaded('themes', function () use ($locale) {
                return $this->themes->map(fn ($t) => [
                    'id' => $t->id,
                    'name' => $locale === 'ar' ? $t->theme_ar : ($t->theme_en ?? $t->theme_ar),
                ]);
            }),
            'gradings' => $this->whenLoaded('gradings', function () use ($locale) {
                return $this->gradings->map(fn ($g) => [
                    'grade' => $g->raw_grade,
                    'grade_key' => $g->grade_key,
                    'scholar' => $g->scholar?->{'name_'.$locale},
                ]);
            }),
            'full_reference' => $this->when(
                $this->relationLoaded('chapter')
                    && $this->chapter?->relationLoaded('book')
                    && $this->chapter->book?->relationLoaded('collection'),
                fn () => $this->getFullReference()
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
