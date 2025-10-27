<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Blog\Enums\ArticleStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update Article Request
 * Validates article update data
 */
class UpdateArticleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware/policy
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $articleId = $this->route('article');

        return [
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('articles', 'slug')->ignore($articleId)],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'series_id' => ['nullable', 'integer', 'exists:series,id'],
            'title' => ['sometimes', 'array'],
            'title.ar' => ['sometimes', 'string', 'max:500'],
            'title.en' => ['nullable', 'string', 'max:500'],
            'title.fr' => ['nullable', 'string', 'max:500'],
            'title.tr' => ['nullable', 'string', 'max:500'],
            'title.ur' => ['nullable', 'string', 'max:500'],
            'excerpt' => ['sometimes', 'array'],
            'excerpt.ar' => ['nullable', 'string'],
            'excerpt.en' => ['nullable', 'string'],
            'excerpt.fr' => ['nullable', 'string'],
            'excerpt.tr' => ['nullable', 'string'],
            'excerpt.ur' => ['nullable', 'string'],
            'content' => ['sometimes', 'array'],
            'content.ar' => ['sometimes', 'string'],
            'content.en' => ['nullable', 'string'],
            'content.fr' => ['nullable', 'string'],
            'content.tr' => ['nullable', 'string'],
            'content.ur' => ['nullable', 'string'],
            'featured_image' => ['nullable', 'string', 'max:500'],
            'status' => ['sometimes', 'string', Rule::in(ArticleStatus::values())],
            'published_at' => ['nullable', 'date'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['integer', 'exists:tags,id'],
        ];
    }
}

