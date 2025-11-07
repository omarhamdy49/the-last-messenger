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
                function ($attribute, $value, $fail) {
                    $mimeType = $value->getMimeType();
                    $size = $value->getSize();

                    // Validate based on file type
                    if (str_starts_with($mimeType, 'image/')) {
                        // Images: jpg, png, gif, webp - max 10MB
                        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                        $maxSize = 10 * 1024 * 1024; // 10MB

                        if (!in_array($mimeType, $allowedMimes)) {
                            $fail('The file must be an image (jpg, png, gif, webp).');
                        }

                        if ($size > $maxSize) {
                            $fail('The image must not exceed 10MB.');
                        }
                    } elseif (str_starts_with($mimeType, 'video/')) {
                        // Videos: mp4, webm - max 100MB
                        $allowedMimes = ['video/mp4', 'video/webm', 'video/avi'];
                        $maxSize = 100 * 1024 * 1024; // 100MB

                        if (!in_array($mimeType, $allowedMimes)) {
                            $fail('The file must be a video (mp4, webm, avi).');
                        }

                        if ($size > $maxSize) {
                            $fail('The video must not exceed 100MB.');
                        }
                    } elseif ($mimeType === 'application/pdf') {
                        // Documents: pdf - max 5MB
                        $maxSize = 5 * 1024 * 1024; // 5MB

                        if ($size > $maxSize) {
                            $fail('The document must not exceed 5MB.');
                        }
                    } else {
                        $fail('The file type is not supported. Allowed: images (jpg, png, gif, webp), videos (mp4, webm), documents (pdf).');
                    }
                },
            ],
            'collection_name' => ['nullable', 'string', 'max:255'],
            'alt_text' => ['nullable', 'string', 'max:500'],
            'caption' => ['nullable', 'string', 'max:1000'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'optimize' => ['nullable', 'boolean'],
            'generate_thumbnails' => ['nullable', 'boolean'],
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
            'generate_thumbnails.boolean' => 'Generate thumbnails must be true or false.',
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
            'collection_name' => 'collection',
            'alt_text' => 'alternative text',
            'caption' => 'caption',
            'tags' => 'tags',
            'optimize' => 'optimization',
            'generate_thumbnails' => 'thumbnail generation',
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
            'generate_thumbnails' => $this->boolean('generate_thumbnails', true),
        ]);
    }
}
