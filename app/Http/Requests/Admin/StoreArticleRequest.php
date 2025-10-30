<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Domain\Blog\Enums\ArticleStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Store Article Request (Admin)
 * Validates article creation data for admin portal
 */
class StoreArticleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'super_admin']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'slug' => ['required', 'string', 'max:255', 'unique:articles,slug'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'series_id' => ['nullable', 'integer', 'exists:series,id'],
            'title' => ['required', 'array'],
            'title.ar' => ['required', 'string', 'max:500'],
            'title.en' => ['nullable', 'string', 'max:500'],
            'title.fr' => ['nullable', 'string', 'max:500'],
            'title.tr' => ['nullable', 'string', 'max:500'],
            'title.ur' => ['nullable', 'string', 'max:500'],
            'excerpt' => ['nullable', 'array'],
            'excerpt.ar' => ['nullable', 'string'],
            'excerpt.en' => ['nullable', 'string'],
            'excerpt.fr' => ['nullable', 'string'],
            'excerpt.tr' => ['nullable', 'string'],
            'excerpt.ur' => ['nullable', 'string'],
            'content' => ['required', 'array'],
            'content.ar' => ['required', 'string'],
            'content.en' => ['nullable', 'string'],
            'content.fr' => ['nullable', 'string'],
            'content.tr' => ['nullable', 'string'],
            'content.ur' => ['nullable', 'string'],
            'featured_image' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'string', Rule::in(ArticleStatus::values())],
            'is_featured' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', 'exists:tags,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.ar.required' => 'Arabic title is required',
            'content.ar.required' => 'Arabic content is required',
            'slug.unique' => 'This slug is already taken',
        ];
    }

    /**
     * Prepare data for validation
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('user_id')) {
            $this->merge([
                'user_id' => $this->user()->id,
            ]);
        }
    }
}
