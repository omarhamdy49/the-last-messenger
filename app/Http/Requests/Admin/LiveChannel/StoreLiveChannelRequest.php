<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\LiveChannel;

use App\Domain\Media\Enums\LiveStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLiveChannelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'super_admin']);
    }

    public function rules(): array
    {
        return [
            // Slug optional on create — auto-generated from the title when blank.
            'slug' => ['nullable', 'string', 'max:255', 'unique:live_channels,slug'],
            'streaming_url' => ['nullable', 'url', 'max:1000'],
            'status' => ['nullable', 'string', Rule::in(array_column(LiveStatus::cases(), 'value'))],
            'is_published' => ['nullable', 'boolean'],
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

            'thumbnail' => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
            'thumbnail_id' => ['nullable', 'integer', 'exists:media,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.ar.required' => __('admin.live_channel_title_ar_required'),
            'slug.unique' => __('admin.slug_taken'),
            'streaming_url.url' => __('admin.live_channel_url_invalid'),
        ];
    }
}
