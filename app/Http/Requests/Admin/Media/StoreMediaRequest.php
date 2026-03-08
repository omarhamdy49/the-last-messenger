<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Media;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Media Request
 *
 * Validation rules for uploading new media files
 */
class StoreMediaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create media');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                $this->validateMediaFile(),
            ],
            'title' => ['nullable', 'string', 'max:255'],
            'collection_name' => ['nullable', 'string', 'max:255'],
            'alt_text' => ['nullable', 'string', 'max:500'],
            'caption' => ['nullable', 'string', 'max:1000'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'optimize' => ['nullable', 'boolean'],
            'generate_sizes' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom error messages
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Please select a file to upload.',
            'file.file' => 'The uploaded item must be a valid file.',
            'title.max' => 'Title cannot exceed 255 characters.',
            'collection_name.string' => 'Collection name must be text.',
            'collection_name.max' => 'Collection name cannot exceed 255 characters.',
            'alt_text.string' => 'Alt text must be text.',
            'alt_text.max' => 'Alt text cannot exceed 500 characters.',
            'caption.string' => 'Caption must be text.',
            'caption.max' => 'Caption cannot exceed 1000 characters.',
            'tags.array' => 'Tags must be an array.',
            'tags.*.string' => 'Each tag must be text.',
            'tags.*.max' => 'Each tag cannot exceed 50 characters.',
            'optimize.boolean' => 'Optimize must be true or false.',
            'generate_sizes.boolean' => 'Generate sizes must be true or false.',
        ];
    }

    /**
     * Get custom attribute names
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'file' => 'media file',
            'title' => 'title',
            'collection_name' => 'collection',
            'alt_text' => 'alternative text',
            'caption' => 'caption',
            'tags' => 'tags',
            'optimize' => 'optimization',
            'generate_sizes' => 'generated sizes',
        ];
    }

    /**
     * Prepare data for validation
     */
    protected function prepareForValidation(): void
    {
        // Convert tags from comma-separated string to array if needed
        if ($this->has('tags') && is_string($this->tags)) {
            $this->merge([
                'tags' => array_filter(array_map('trim', explode(',', $this->tags))),
            ]);
        }

        // Set defaults
        $this->merge([
            'optimize' => $this->boolean('optimize', true),
            'generate_sizes' => $this->boolean(
                'generate_sizes',
                $this->boolean('generate_thumbnails', true)
            ),
        ]);
    }

    protected function validateMediaFile(): \Closure
    {
        return function ($attribute, $value, $fail): void {
            $mimeType = (string) $value->getMimeType();
            $size = (int) $value->getSize();

            $allowedImages = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $allowedVideos = ['video/mp4', 'video/webm', 'video/avi', 'video/quicktime'];
            $allowedAudio = ['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/x-wav', 'audio/ogg', 'audio/mp4'];
            $allowedDocuments = ['application/pdf'];

            if (in_array($mimeType, $allowedImages, true)) {
                if ($size > 10 * 1024 * 1024) {
                    $fail('The image must not exceed 10MB.');
                }

                return;
            }

            if (in_array($mimeType, $allowedVideos, true)) {
                if ($size > 100 * 1024 * 1024) {
                    $fail('The video must not exceed 100MB.');
                }

                return;
            }

            if (in_array($mimeType, $allowedAudio, true)) {
                if ($size > 30 * 1024 * 1024) {
                    $fail('The audio file must not exceed 30MB.');
                }

                return;
            }

            if (in_array($mimeType, $allowedDocuments, true)) {
                if ($size > 10 * 1024 * 1024) {
                    $fail('The document must not exceed 10MB.');
                }

                return;
            }

            $fail('The file type is not supported. Allowed: images, videos, audio files, and PDF documents.');
        };
    }
}
