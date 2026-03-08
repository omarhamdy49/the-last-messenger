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
        $routeParameter = $this->route('seerah_event') ?? $this->route('id');
        $eventId = is_object($routeParameter) ? $routeParameter->id : $routeParameter;

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
            'content' => ['nullable', 'array'],
            'content.ar' => ['nullable', 'string'],
            'content.en' => ['nullable', 'string'],
            'content.fr' => ['nullable', 'string'],
            'content.tr' => ['nullable', 'string'],
            'content.ur' => ['nullable', 'string'],
            'hijri_date' => ['nullable', 'string', 'max:100'],
            'hijri_year' => ['nullable', 'integer'],
            'hijri_month' => ['nullable', 'integer', 'between:1,12'],
            'hijri_day' => ['nullable', 'integer', 'between:1,30'],
            'gregorian_date' => ['nullable', 'date'],
            'gregorian_year' => ['nullable', 'integer'],
            'event_type' => ['nullable', 'string', 'in:birth,death,battle,migration,revelation,treaty,marriage,miracle,other'],
            'importance' => ['nullable', 'string', 'in:high,medium,low'],
            'location' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'related_article_id' => ['nullable', 'integer', 'exists:articles,id'],
            'featured_image' => ['nullable', 'file', 'image', 'max:10240'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'is_featured' => ['nullable', 'boolean'],
        ];
    }
}
