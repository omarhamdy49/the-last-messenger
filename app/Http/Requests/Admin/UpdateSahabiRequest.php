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
        $sahabiId = $this->route('id');

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
            'biography' => ['nullable', 'array'],
            'biography.ar' => ['nullable', 'string'],
            'biography.en' => ['nullable', 'string'],
            'biography.fr' => ['nullable', 'string'],
            'biography.tr' => ['nullable', 'string'],
            'biography.ur' => ['nullable', 'string'],
            'gender' => ['sometimes', 'required', 'string', 'in:male,female'],
            'status' => ['nullable', 'string', 'in:prophet_companion,ten_promised_paradise,badr_participant,uhud_participant,early_muslim,muhajir,ansar,tabi'],
            'birth_date_hijri' => ['nullable', 'string', 'max:100'],
            'death_date_hijri' => ['nullable', 'string', 'max:100'],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'death_place' => ['nullable', 'string', 'max:255'],
            'burial_place' => ['nullable', 'string', 'max:255'],
            'tribe' => ['nullable', 'string', 'max:255'],
            'clan' => ['nullable', 'string', 'max:255'],
            'hadith_count' => ['nullable', 'integer', 'min:0'],
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
