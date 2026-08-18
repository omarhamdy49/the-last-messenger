<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\MediaProgram;

use App\Http\Requests\Admin\Content\Concerns\SanitizesRichText;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMediaEpisodeRequest extends FormRequest
{
    use SanitizesRichText;

    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'super_admin']);
    }

    public function rules(): array
    {
        $episodeId = $this->route('media_episode');

        return [
            'season_number' => ['nullable', 'integer', 'min:1'],
            'episode_number' => ['required', 'integer', 'min:1'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('media_episodes', 'slug')->ignore($episodeId)],
            'video_url' => ['nullable', 'url', 'max:500'],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
            'is_featured' => ['nullable', 'boolean'],
            'thumbnail' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
            'thumbnail_id' => ['nullable', 'integer', 'exists:media,id'],
            'is_published' => ['nullable', 'boolean'],
            'publish_at' => ['nullable', 'date'],
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
            'transcript' => ['nullable', 'array'],
            'transcript.ar' => ['nullable', 'string'],
            'transcript.en' => ['nullable', 'string'],
            'transcript.fr' => ['nullable', 'string'],
            'transcript.tr' => ['nullable', 'string'],
            'transcript.ur' => ['nullable', 'string'],
            'seo_title' => ['nullable', 'array'],
            'seo_title.*' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'array'],
            'seo_description.*' => ['nullable', 'string', 'max:500'],
            'seo_keywords' => ['nullable', 'array'],
            'seo_keywords.*' => ['nullable', 'string', 'max:255'],
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
        $this->sanitizeRichTextField('transcript');
    }
}
