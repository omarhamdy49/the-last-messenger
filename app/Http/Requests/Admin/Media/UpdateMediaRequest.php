<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Media;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Media Request
 *
 * Validation rules for updating media metadata
 */
class UpdateMediaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('edit media');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'alt_text' => ['nullable', 'string', 'max:500'],
            'caption' => ['nullable', 'string', 'max:1000'],
            'collection_name' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'metadata' => ['nullable', 'array'],
            'metadata.*' => ['nullable'],
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
            'title.max' => 'Title cannot exceed 255 characters.',
            'alt_text.string' => 'Alt text must be text.',
            'alt_text.max' => 'Alt text cannot exceed 500 characters.',
            'caption.string' => 'Caption must be text.',
            'caption.max' => 'Caption cannot exceed 1000 characters.',
            'collection_name.string' => 'Collection name must be text.',
            'collection_name.max' => 'Collection name cannot exceed 255 characters.',
            'tags.array' => 'Tags must be an array.',
            'tags.*.string' => 'Each tag must be text.',
            'tags.*.max' => 'Each tag cannot exceed 50 characters.',
            'metadata.array' => 'Metadata must be an object.',
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
            'title' => 'title',
            'alt_text' => 'alternative text',
            'caption' => 'caption',
            'collection_name' => 'collection',
            'tags' => 'tags',
            'metadata' => 'metadata',
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
    }
}
