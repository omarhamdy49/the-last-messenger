<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\MediaProgram;

use App\Domain\Media\Enums\MaturityRating;
use App\Domain\Media\Enums\ProgramCategory;
use App\Domain\Media\Support\YouTubeUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMediaProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'super_admin']);
    }

    public function rules(): array
    {
        return [
            'slug' => ['required', 'string', 'max:255', 'unique:media_programs,slug'],
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
            // Optional: import episodes from this playlist/video right after
            // the program is created. Validated against the same host-locked
            // rule the import endpoints use.
            'youtube_url' => [
                'nullable', 'string', 'max:1000',
                static function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! YouTubeUrl::isSupportedSource(is_string($value) ? $value : null)) {
                        $fail(__('admin.yt_source_url_invalid'));
                    }
                },
            ],
            // Per-video mapping confirmed in the import modal before saving.
            'youtube_items' => ['nullable', 'array', 'max:500'],
            'youtube_items.*.video_id' => ['required', 'string', 'max:32'],
            'youtube_items.*.episode_number' => ['required', 'integer', 'min:1', 'max:10000'],
            'youtube_items.*.title' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.ar.required' => 'Arabic title is required.',
            'slug.unique' => 'This slug is already taken.',
            'category.in' => 'Invalid category value.',
        ];
    }
}
