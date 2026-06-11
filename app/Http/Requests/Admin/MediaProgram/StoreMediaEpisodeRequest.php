<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\MediaProgram;

use Illuminate\Foundation\Http\FormRequest;

class StoreMediaEpisodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'super_admin']);
    }

    public function rules(): array
    {
        return [
            'program_id' => ['required', 'integer', 'exists:media_programs,id'],
            'season_number' => ['nullable', 'integer', 'min:1'],
            'episode_number' => ['required', 'integer', 'min:1'],
            'slug' => ['required', 'string', 'max:255', 'unique:media_episodes,slug'],
            'video_url' => ['nullable', 'url', 'max:500'],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
            'is_featured' => ['nullable', 'boolean'],
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
        ];
    }

    public function messages(): array
    {
        return [
            'title.ar.required' => 'Arabic title is required.',
            'slug.unique' => 'This slug is already taken.',
            'program_id.exists' => 'Program not found.',
        ];
    }
}
