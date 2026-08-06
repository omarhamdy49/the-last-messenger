<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\People\Models\Muslim;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Muslim
 *
 * Deliberately absent, by recorded decision:
 *   is_sahabi / is_companion / person_type  -> collapsed into roles[]
 *   battles[] / timeline[] / categories[]   -> retired (Owner Decision 6)
 *   tribe / status                          -> no surviving column
 *   gallery / media[]                       -> scalar `thumb` only (Decision 4)
 */
class MuslimResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();

        return [
            'slug' => $this->slug,
            'name' => $this->name($locale),
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'kunya' => $this->getTranslation('kunya', $locale, false),
            'thumb' => $this->thumb,
            'gender' => $this->gender?->value,
            'birth_place' => $this->getTranslation('birth_place', $locale, false),
            // Signed: negative years are pre-Hijra and entirely normal.
            'birth_year_hijri' => $this->birth_year_hijri,
            'death_year_hijri' => $this->death_year_hijri,
            'generation' => $this->generation,
            'bio' => $this->getTranslation('bio', $locale, false),
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->map(fn ($role) => [
                'slug' => $role->slug,
                'name' => $role->name($locale),
                'is_featured' => (bool) $role->pivot->is_featured,
            ])->all()),
            'father' => $this->whenLoaded('father', fn () => $this->father ? [
                'slug' => $this->father->slug,
                'name' => $this->father->name($locale),
            ] : null),
            'mother' => $this->whenLoaded('mother', fn () => $this->mother ? [
                'slug' => $this->mother->slug,
                'name' => $this->mother->name($locale),
            ] : null),
        ];
    }
}
