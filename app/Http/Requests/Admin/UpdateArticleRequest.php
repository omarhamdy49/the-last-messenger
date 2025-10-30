<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Domain\Blog\Enums\ArticleStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update Article Request (Admin)
 * Validates article update data for admin portal
 */
class UpdateArticleRequest extends FormRequest
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
        $articleId = $this->route('id');

        return [
            'slug' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('articles', 'slug')->ignore($articleId)],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'series_id' => ['nullable', 'integer', 'exists:series,id'],
            'title' => ['sometimes', 'required', 'array'],
            'title.ar' => ['required_with:title', 'string', 'max:500'],
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
            'content' => ['sometimes', 'required', 'array'],
            'content.ar' => ['required_with:content', 'string'],
            'content.en' => ['nullable', 'string'],
            'content.fr' => ['nullable', 'string'],
            'content.tr' => ['nullable', 'string'],
            'content.ur' => ['nullable', 'string'],
            'featured_image' => ['nullable', 'string', 'max:500'],
            'status' => ['sometimes', 'required', 'string', Rule::in(ArticleStatus::values())],
            'is_featured' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', 'exists:tags,id'],
            'meta_title' => ['nullable', 'string', 'max:60'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'meta_keywords' => ['nullable', 'string', 'max:200'],
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
            'title.ar.required_with' => 'Arabic title is required',
            'content.ar.required_with' => 'Arabic content is required',
            'slug.unique' => 'This slug is already taken',
        ];
    }
}
