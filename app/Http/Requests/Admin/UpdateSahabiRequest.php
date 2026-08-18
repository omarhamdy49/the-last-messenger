<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update Sahabi Request (Admin)
 */
class UpdateSahabiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'super_admin']);
    }

    public function rules(): array
    {
        $sahabiId = $this->route('sahaba');

        return [
            'slug' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('sahaba', 'slug')->ignore($sahabiId)],
            'name' => ['sometimes', 'required', 'array'],
            'name.ar' => ['required_with:name', 'string', 'max:255'],
            'name.en' => ['nullable', 'string', 'max:255'],
            'name.fr' => ['nullable', 'string', 'max:255'],
            'name.tr' => ['nullable', 'string', 'max:255'],
            'name.ur' => ['nullable', 'string', 'max:255'],
            'full_name' => ['nullable', 'array'],
            'full_name.ar' => ['nullable', 'string', 'max:500'],
            'full_name.en' => ['nullable', 'string', 'max:500'],
            'full_name.fr' => ['nullable', 'string', 'max:500'],
            'full_name.tr' => ['nullable', 'string', 'max:500'],
            'full_name.ur' => ['nullable', 'string', 'max:500'],
            'kunya' => ['nullable', 'array'],
            'kunya.ar' => ['nullable', 'string', 'max:255'],
            'kunya.en' => ['nullable', 'string', 'max:255'],
            'kunya.fr' => ['nullable', 'string', 'max:255'],
            'kunya.tr' => ['nullable', 'string', 'max:255'],
            'kunya.ur' => ['nullable', 'string', 'max:255'],
            'laqab' => ['nullable', 'array'],
            'laqab.ar' => ['nullable', 'string', 'max:255'],
            'laqab.en' => ['nullable', 'string', 'max:255'],
            'laqab.fr' => ['nullable', 'string', 'max:255'],
            'laqab.tr' => ['nullable', 'string', 'max:255'],
            'laqab.ur' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'array'],
            'title.ar' => ['nullable', 'string', 'max:255'],
            'title.en' => ['nullable', 'string', 'max:255'],
            'title.fr' => ['nullable', 'string', 'max:255'],
            'title.tr' => ['nullable', 'string', 'max:255'],
            'title.ur' => ['nullable', 'string', 'max:255'],
            'occupation' => ['nullable', 'array'],
            'occupation.ar' => ['nullable', 'string', 'max:255'],
            'occupation.en' => ['nullable', 'string', 'max:255'],
            'occupation.fr' => ['nullable', 'string', 'max:255'],
            'occupation.tr' => ['nullable', 'string', 'max:255'],
            'occupation.ur' => ['nullable', 'string', 'max:255'],
            'biography' => ['nullable', 'array'],
            'biography.ar' => ['nullable', 'string'],
            'biography.en' => ['nullable', 'string'],
            'biography.fr' => ['nullable', 'string'],
            'biography.tr' => ['nullable', 'string'],
            'biography.ur' => ['nullable', 'string'],
            'virtues' => ['nullable', 'array'],
            'virtues.ar' => ['nullable', 'string'],
            'virtues.en' => ['nullable', 'string'],
            'virtues.fr' => ['nullable', 'string'],
            'virtues.tr' => ['nullable', 'string'],
            'virtues.ur' => ['nullable', 'string'],
            'famous_narrations' => ['nullable', 'array'],
            'famous_narrations.ar' => ['nullable', 'string'],
            'famous_narrations.en' => ['nullable', 'string'],
            'famous_narrations.fr' => ['nullable', 'string'],
            'famous_narrations.tr' => ['nullable', 'string'],
            'famous_narrations.ur' => ['nullable', 'string'],
            'martyrdom' => ['nullable', 'array'],
            'martyrdom.ar' => ['nullable', 'string'],
            'martyrdom.en' => ['nullable', 'string'],
            'martyrdom.fr' => ['nullable', 'string'],
            'martyrdom.tr' => ['nullable', 'string'],
            'martyrdom.ur' => ['nullable', 'string'],
            'notes' => ['nullable', 'array'],
            'notes.ar' => ['nullable', 'string'],
            'notes.en' => ['nullable', 'string'],
            'notes.fr' => ['nullable', 'string'],
            'notes.tr' => ['nullable', 'string'],
            'notes.ur' => ['nullable', 'string'],
            'gender' => ['sometimes', 'required', 'string', 'in:male,female'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['string', 'in:ten_promised,badr,uhud,early_muslim,muhajir,ansar,tabi'],
            'birth_date_hijri' => ['nullable', 'string', 'max:100'],
            'death_date_hijri' => ['nullable', 'string', 'max:100'],
            'birth_date_gregorian' => ['nullable', 'date'],
            'death_date_gregorian' => ['nullable', 'date'],
            'age_at_death' => ['nullable', 'integer', 'min:0'],
            'conversion_date_hijri' => ['nullable', 'string', 'max:100'],
            'conversion_order' => ['nullable', 'integer', 'min:1'],
            'roles' => ['nullable', 'string', 'max:1000'],
            'birth_place' => ['nullable', 'string', 'max:255'],
            // The canonical birthplace. Additive: `birth_place` above keeps
            // whatever text was originally recorded.
            'birth_place_id' => ['nullable', 'integer', 'exists:locations,id'],
            'death_place' => ['nullable', 'string', 'max:255'],
            'burial_place' => ['nullable', 'string', 'max:255'],
            'tribe' => ['nullable', 'string', 'max:255'],
            'clan' => ['nullable', 'string', 'max:255'],
            'generation' => ['nullable', 'integer', 'min:0'],
            'is_verified' => ['nullable', 'boolean'],
            'reported_hadith_count' => ['nullable', 'integer', 'min:0'],
            'importance_rank' => ['nullable', 'integer', 'min:0'],
            'is_featured' => ['nullable', 'boolean'],

            // Profile image - support both upload and picker
            'profile_image' => ['nullable', 'file', 'image', 'max:10240'],
            'profile_image_media_id' => ['nullable', 'exists:media,id'],

            // Gallery - support both upload and picker
            'gallery' => ['nullable', 'array'],
            'gallery.*' => ['file', 'image', 'max:10240'],
            'gallery_media_ids' => ['nullable', 'array'],
            'gallery_media_ids.*' => ['exists:media,id'],
            'replace_gallery' => ['nullable', 'boolean'],
        ];
    }
}
