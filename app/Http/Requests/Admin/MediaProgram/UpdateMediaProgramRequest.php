<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\MediaProgram;

use App\Domain\Media\Enums\MaturityRating;
use App\Domain\Media\Enums\ProgramCategory;
use App\Http\Requests\Admin\Content\Concerns\SanitizesRichText;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMediaProgramRequest extends FormRequest
{
    use SanitizesRichText;

    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'super_admin']);
    }

    public function rules(): array
    {
        $programId = $this->route('media_program');

        return [
            'slug' => ['required', 'string', 'max:255', Rule::unique('media_programs', 'slug')->ignore($programId)],
            'category' => ['required', 'string', Rule::in(array_column(ProgramCategory::cases(), 'value'))],
            'maturity_rating' => ['nullable', 'string', Rule::in(array_column(MaturityRating::cases(), 'value'))],
            'year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'total_seasons' => ['nullable', 'integer', 'min:1', 'max:500'],
            'total_episodes' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'is_featured' => ['nullable', 'boolean'],
            'is_trending' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
            'publish_at' => ['nullable', 'date'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'title' => ['required', 'array'],
            'title.ar' => ['required', 'string', 'max:500'],
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
            'host' => ['nullable', 'array'],
            'host.ar' => ['nullable', 'string', 'max:255'],
            'host.en' => ['nullable', 'string', 'max:255'],
            'host.fr' => ['nullable', 'string', 'max:255'],
            'host.tr' => ['nullable', 'string', 'max:255'],
            'host.ur' => ['nullable', 'string', 'max:255'],
            'seo_title' => ['nullable', 'array'],
            'seo_title.*' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'array'],
            'seo_description.*' => ['nullable', 'string', 'max:500'],
            'seo_keywords' => ['nullable', 'array'],
            'seo_keywords.*' => ['nullable', 'string', 'max:255'],
            'poster' => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
            'banner' => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
            'poster_id' => ['nullable', 'integer', 'exists:media,id'],
            'banner_id' => ['nullable', 'integer', 'exists:media,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.ar.required' => 'Arabic title is required.',
            'slug.unique' => 'This slug is already taken.',
        ];
    }

    /**
     * These render with {!! !!} on /media/{slug} and in the admin episode page, and
     * the same columns also receive third-party YouTube text (sanitised in
     * YouTubeSyncService). Purify here so both write paths land clean.
     */
    protected function prepareForValidation(): void
    {
        $this->sanitizeRichTextField('description');
        $this->sanitizeRichTextField('host');
    }
}
