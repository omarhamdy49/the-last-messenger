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
            'name' => $this->name,
            'title' => $this->title,
            'biography' => $this->biography,
            'person_type' => $this->person_type?->value,
            'person_type_label' => $this->person_type?->label(),
            'gender' => $this->gender,
            'birth_date_hijri' => $this->birth_date_hijri?->format('Y-m-d'),
            'birth_date_gregorian' => $this->birth_date_gregorian?->format('Y-m-d'),
            'birth_place' => $this->birth_place,
            'death_date_hijri' => $this->death_date_hijri?->format('Y-m-d'),
            'death_date_gregorian' => $this->death_date_gregorian?->format('Y-m-d'),
            'death_place' => $this->death_place,
            'burial_place' => $this->burial_place,
            'tribe' => $this->tribe,
            'occupation' => $this->occupation,
            'is_verified' => $this->is_verified,
            'notes' => $this->notes,
            'father' => $this->when($this->relationLoaded('father'), function () {
                return $this->father ? [
                    'id' => $this->father->id,
                    'name' => $this->father->name,
                ] : null;
            }),
            'mother' => $this->when($this->relationLoaded('mother'), function () {
                return $this->mother ? [
                    'id' => $this->mother->id,
                    'name' => $this->mother->name,
                ] : null;
            }),
            'sahabi' => $this->when($this->relationLoaded('sahabi'), function () {
                return $this->sahabi ? [
                    'id' => $this->sahabi->id,
                    'name' => $this->sahabi->name,
                ] : null;
            }),
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
