<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

/**
 * Update Seerah Event Request (Admin)
 *
 * Same fields as a store; the identity fields become optional so a partial
 * update only touches what it sends.
 */
class UpdateSeerahEventRequest extends StoreSeerahEventRequest
{
    public function rules(): array
    {
        $routeParameter = $this->route('seerah_event') ?? $this->route('id');
        $eventId = is_object($routeParameter) ? $routeParameter->id : $routeParameter;

        $rules = parent::rules();

        $rules['slug'] = ['sometimes', 'required', 'string', 'max:255', 'regex:/^[a-z0-9\-]+$/', Rule::unique('seerah_events', 'slug')->ignore($eventId)];
        $rules['title'] = ['sometimes', 'required', 'array'];
        $rules['title.ar'] = ['required_with:title', 'string', 'max:500'];

        foreach (['event_type', 'order', 'status'] as $field) {
            $rules[$field] = ['sometimes', ...$rules[$field]];
        }

        return $rules;
    }
}
