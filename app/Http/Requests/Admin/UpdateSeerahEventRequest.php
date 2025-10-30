<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update Seerah Event Request (Admin)
 */
class UpdateSeerahEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'super_admin']);
    }

    public function rules(): array
    {
        $eventId = $this->route('id');

        return [
            'slug' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('seerah_events', 'slug')->ignore($eventId)],
            'title' => ['sometimes', 'required', 'array'],
            'title.ar' => ['required_with:title', 'string', 'max:500'],
            'title.en' => ['nullable', 'string', 'max:500'],
            'title.fr' => ['nullable', 'string', 'max:500'],
            'title.tr' => ['nullable', 'string', 'max:500'],
            'title.ur' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'array'],
            'description.ar' => ['nullable', 'string'],
            'description.en' => ['nullable', 'string'],
            'description.fr' => ['nullable', 'string'],
            'description.tr' => ['nullable', 'string'],
            'description.ur' => ['nullable', 'string'],
            'event_date' => ['nullable', 'string', 'max:100'],
            'hijri_date' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'category' => ['nullable', 'string', 'max:100'],
            'importance' => ['nullable', 'integer', 'between:1,5'],
        ];
    }
}
