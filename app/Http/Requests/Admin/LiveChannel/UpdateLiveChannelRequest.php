<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\LiveChannel;

use App\Domain\Media\Enums\LiveStatus;
use App\Domain\Media\Enums\StreamType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLiveChannelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'super_admin']);
    }

    public function rules(): array
    {
        $id = $this->route('live_channel');

        return [
            'slug' => ['required', 'string', 'max:255', Rule::unique('live_channels', 'slug')->ignore($id)],
            'streaming_url' => ['nullable', 'url:http,https', 'max:1000'],
            // Blank means "auto" — StreamType::detect() reads it off the URL.
            'stream_type' => ['nullable', 'string', Rule::in(array_column(StreamType::cases(), 'value'))],
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
