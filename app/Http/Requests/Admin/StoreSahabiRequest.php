<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Sahabi Request (Admin)
 */
class StoreSahabiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'super_admin']);
    }

    public function rules(): array
    {
        return [
            'slug' => ['required', 'string', 'max:255', 'unique:sahaba,slug'],
            'name' => ['required', 'array'],
            'name.ar' => ['required', 'string', 'max:255'],
            'name.en' => ['nullable', 'string', 'max:255'],
            'name.fr' => ['nullable', 'string', 'max:255'],
            'name.tr' => ['nullable', 'string', 'max:255'],
            'name.ur' => ['nullable', 'string', 'max:255'],
            'biography' => ['nullable', 'array'],
            'biography.ar' => ['nullable', 'string'],
            'biography.en' => ['nullable', 'string'],
            'biography.fr' => ['nullable', 'string'],
            'biography.tr' => ['nullable', 'string'],
            'biography.ur' => ['nullable', 'string'],
            'gender' => ['required', 'string', 'in:male,female'],
            'birth_date' => ['nullable', 'string', 'max:100'],
            'death_date' => ['nullable', 'string', 'max:100'],
            'relationship_to_prophet' => ['nullable', 'string', 'max:500'],
            'notable_events' => ['nullable', 'array'],

            // Profile image - support both upload and picker
            'profile_image' => ['nullable', 'file', 'image', 'max:10240'],
            'profile_image_media_id' => ['nullable', 'exists:media,id'],

            // Gallery - support both upload and picker
            'gallery' => ['nullable', 'array'],
            'gallery.*' => ['file', 'image', 'max:10240'],
            'gallery_media_ids' => ['nullable', 'array'],
            'gallery_media_ids.*' => ['exists:media,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.ar.required' => 'Arabic name is required',
            'slug.unique' => 'This slug is already taken',
        ];
    }
}
