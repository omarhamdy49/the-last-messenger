<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Media;

use Illuminate\Foundation\Http\FormRequest;

class BulkUploadMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create media') ?? false;
    }

    public function rules(): array
    {
        return [
            'files' => ['required', 'array', 'min:1', 'max:10'],
            'files.*' => [
                'required',
                'file',
                function ($attribute, $value, $fail): void {
                    $mimeType = (string) $value->getMimeType();
                    $size = (int) $value->getSize();

                    $allowed = [
                        'image/jpeg' => 10 * 1024 * 1024,
                        'image/png' => 10 * 1024 * 1024,
                        'image/gif' => 10 * 1024 * 1024,
                        'image/webp' => 10 * 1024 * 1024,
                        'video/mp4' => 100 * 1024 * 1024,
                        'video/webm' => 100 * 1024 * 1024,
                        'video/avi' => 100 * 1024 * 1024,
                        'video/quicktime' => 100 * 1024 * 1024,
                        'audio/mpeg' => 30 * 1024 * 1024,
                        'audio/mp3' => 30 * 1024 * 1024,
                        'audio/wav' => 30 * 1024 * 1024,
                        'audio/x-wav' => 30 * 1024 * 1024,
                        'audio/ogg' => 30 * 1024 * 1024,
                        'audio/mp4' => 30 * 1024 * 1024,
                        'application/pdf' => 10 * 1024 * 1024,
                    ];

                    if (! array_key_exists($mimeType, $allowed)) {
                        $fail('The file type is not supported.');

                        return;
                    }

                    if ($size > $allowed[$mimeType]) {
                        $fail('One of the selected files exceeds the allowed upload size.');
                    }
                },
            ],
            'collection_name' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'alt_text' => ['nullable', 'string', 'max:500'],
            'caption' => ['nullable', 'string', 'max:1000'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'optimize' => ['nullable', 'boolean'],
            'generate_sizes' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('tags') && is_string($this->tags)) {
            $this->merge([
                'tags' => array_values(array_filter(array_map('trim', explode(',', $this->tags)))),
            ]);
        }

        $this->merge([
            'optimize' => $this->boolean('optimize', true),
            'generate_sizes' => $this->boolean('generate_sizes', true),
        ]);
    }
}
