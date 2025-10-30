<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Blog\Enums\ArticleStatus;
use App\Rules\TranslatableRequired;
use Illuminate\Validation\Rule;

/**
 * Store Article Request
 * Validates article creation data
 */
class StoreArticleRequest extends BaseTranslatableRequest
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
     * Get required translatable field names
     *
     * @return array
     */
    protected function requiredTranslatableFields(): array
    {
        return ['title', 'content'];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Get translatable field rules
        $translatableRules = $this->mergeTranslatableRules();

        // Non-translatable field rules
        $customRules = [
            'slug' => ['required', 'string', 'max:255', 'unique:articles,slug'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'series_id' => ['nullable', 'integer', 'exists:series,id'],
            'featured_image' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'string', Rule::in(ArticleStatus::values())],
            'published_at' => ['nullable', 'date'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', 'exists:tags,id'],
        ];

        return array_merge($translatableRules, $customRules);
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
