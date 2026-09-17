<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

/**
 * Update Seerah Event Type Request (Admin)
 */
class UpdateSeerahEventTypeRequest extends StoreSeerahEventTypeRequest
{
    public function rules(): array
    {
        $routeParameter = $this->route('seerah_event_type') ?? $this->route('id');
        $typeId = is_object($routeParameter) ? $routeParameter->id : $routeParameter;

        return [
            ...parent::rules(),
            'slug' => ['sometimes', 'required', 'string', 'max:255', 'regex:/^[a-z0-9\-]+$/', Rule::unique('seerah_event_types', 'slug')->ignore($typeId)],
            'locale' => ['sometimes', 'required', 'array'],
            'locale.ar' => ['required_with:locale', 'string', 'max:255'],
        ];
    }
}
