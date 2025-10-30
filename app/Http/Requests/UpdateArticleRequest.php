<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Blog\Enums\ArticleStatus;
use App\Rules\TranslatableArray;
use Illuminate\Validation\Rule;

/**
 * Update Article Request
 * Validates article update data
 */
class UpdateArticleRequest extends BaseTranslatableRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware/policy
    }

    /**
     * Get translatable field names
     *
     * @return array
     */
    protected function translatableFields(): array
    {
        return [
            'title' => ['string', 'max:500'],
            'excerpt' => ['string'],
            'content' => ['string'],
        ];
    }

    /**
     * Get required translatable field names (empty for update - all optional)
     *
     * @return array
     */
    protected function requiredTranslatableFields(): array
    {
        return [];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $articleId = $this->route('article');

        // For update, fields are optional but if provided must be valid
        $translatableRules = [];
        foreach ($this->translatableFields() as $field => $extraRules) {
            if ($this->has($field)) {
                $translatableRules = array_merge(
                    $translatableRules,
                    $this->translatableRules($field, false, $extraRules)
                );
            }
        }

        // Non-translatable field rules
        $customRules = [
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('articles', 'slug')->ignore($articleId)],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'series_id' => ['nullable', 'integer', 'exists:series,id'],
            'featured_image' => ['nullable', 'string', 'max:500'],
            'status' => ['sometimes', 'string', Rule::in(ArticleStatus::values())],
            'published_at' => ['nullable', 'date'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['integer', 'exists:tags,id'],
        ];

        return array_merge($translatableRules, $customRules);
    }
}
