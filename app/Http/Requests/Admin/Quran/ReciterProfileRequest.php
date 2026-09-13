<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Quran;

use App\Domain\Quran\Models\ReciterProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Shared by create and update of a reciter profile (the person, not a recording). */
final class ReciterProfileRequest extends FormRequest
{
    public const LOCALES = ['ar', 'en', 'fr', 'tr', 'ur'];

    public function authorize(): bool
    {
        return true; // Admin middleware guards the route group.
    }

    protected function prepareForValidation(): void
    {
        if (filled($this->input('slug'))) {
            $this->merge(['slug' => Str::slug((string) $this->input('slug'))]);

            return;
        }
        if ($this->profileId()) {
            // An emptied slug on edit keeps the public URL rather than regenerating it.
            $this->merge(['slug' => ReciterProfile::whereKey($this->profileId())->value('slug')]);

            return;
        }
        $base = Str::slug((string) ($this->input('names.en') ?: $this->input('names.ar'))) ?: 'reciter';
        $slug = $base;
        while (ReciterProfile::where('slug', $slug)->where('id', '!=', $this->profileId() ?? 0)->exists()) {
            $slug = $base.'-'.Str::lower(Str::random(4));
        }
        $this->merge(['slug' => $slug]);
    }

    public function rules(): array
    {
        $id = $this->profileId();

        return [
            'names' => ['required', 'array'],
            'names.ar' => ['required', 'string', 'max:255'],
            'names.*' => ['nullable', 'string', 'max:255'],
            'descriptions' => ['nullable', 'array'],
            'descriptions.*' => ['nullable', 'string', 'max:10000'],
            'slug' => ['required', 'alpha_dash', 'max:255', Rule::unique('quran_reciter_profiles', 'slug')->ignore($id)],
            'country' => ['nullable', 'string', 'size:2', 'alpha'],
            // <x-admin.image-upload>: upload, media-library pick, external URL, or removal.
            'profile_image_file' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
            'profile_image_id' => ['nullable', 'integer', 'exists:media,id'],
            'profile_image_url' => ['nullable', 'string', 'max:255', 'url:http,https'],
            'remove_profile_image' => ['nullable', 'boolean'],
            // A new profile has no recordings yet, so the default can only be chosen on edit.
            'default_recording_id' => [$id ? 'required' : 'prohibited', 'nullable', 'integer',
                Rule::exists('quran_reciters', 'id')->where('profile_id', $id)->where('is_active', true)->where('kind', 'recitation')],
            'is_active' => ['required', 'boolean'],
            'is_featured' => ['required', 'boolean'],
            'display_order' => ['required', 'integer', 'min:0'],
        ];
    }

    /** @return array{translations: array<string, array{name: string, description: ?string}>, attributes: array<string, mixed>} */
    public function payload(): array
    {
        $data = $this->validated();
        $translations = [];
        foreach (self::LOCALES as $locale) {
            if (filled($data['names'][$locale] ?? null)) {
                $translations[$locale] = ['name' => $data['names'][$locale], 'description' => $data['descriptions'][$locale] ?? null];
            }
        }
        unset($data['names'], $data['descriptions'], $data['profile_image_file'], $data['profile_image_id'], $data['profile_image_url'], $data['remove_profile_image']);
        if (isset($data['country'])) {
            $data['country'] = strtoupper($data['country']);
        }

        return ['translations' => $translations, 'attributes' => $data];
    }

    private function profileId(): ?int
    {
        return $this->route('profile') ? (int) $this->route('profile') : null;
    }
}
