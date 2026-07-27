<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Genealogy Resource
 * Transforms Person model to JSON
 */
class GenealogyResource extends JsonResource
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
            'name' => $this->getTranslations('name'),
            'title' => $this->getTranslations('title'),
            'biography' => $this->getTranslations('biography'),
            'person_type' => $this->person_type?->value,
            'person_type_label' => $this->person_type?->label(),
            'gender' => $this->gender,
            'birth_date_hijri' => $this->birth_date_hijri,
            'birth_date_gregorian' => $this->birth_date_gregorian?->format('Y-m-d'),
            'birth_place' => $this->getTranslations('birth_place'),
            'death_date_hijri' => $this->death_date_hijri,
            'death_date_gregorian' => $this->death_date_gregorian?->format('Y-m-d'),
            'death_place' => $this->getTranslations('death_place'),
            'burial_place' => $this->getTranslations('burial_place'),
            'tribe' => $this->tribe,
            'occupation' => $this->getTranslations('occupation'),
            'is_verified' => $this->is_verified,
            'notes' => $this->getTranslations('notes'),
            'father' => $this->when($this->relationLoaded('father'), function () {
                return $this->father ? [
                    'id' => $this->father->id,
                    'name' => $this->father->getTranslations('name'),
                ] : null;
            }),
            'mother' => $this->when($this->relationLoaded('mother'), function () {
                return $this->mother ? [
                    'id' => $this->mother->id,
                    'name' => $this->mother->getTranslations('name'),
                ] : null;
            }),
            'is_sahabi' => $this->is_sahabi,
            'children_count' => $this->when(
                $this->relationLoaded('childrenAsFather') && $this->relationLoaded('childrenAsMother'),
                function () {
                    return $this->childrenAsFather->count() + $this->childrenAsMother->count();
                }
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
