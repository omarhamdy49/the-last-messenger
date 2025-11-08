<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Article;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateArticleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('edit articles');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $articleId = $this->route('article')->id;

        return [
            // Multi-language fields (required for at least ar and en)
            'title' => 'required|array',
            'title.ar' => 'required|string|max:200',
            'title.en' => 'required|string|max:200',
            'title.fr' => 'nullable|string|max:200',
            'title.tr' => 'nullable|string|max:200',
            'title.ur' => 'nullable|string|max:200',

            'slug' => 'nullable|array',
            'slug.ar' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('article_locale', 'slug')
                    ->where('locale', 'ar')
                    ->whereNot('article_id', $articleId)
            ],
            'slug.en' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('article_locale', 'slug')
                    ->where('locale', 'en')
                    ->whereNot('article_id', $articleId)
            ],
            'slug.fr' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('article_locale', 'slug')
                    ->where('locale', 'fr')
                    ->whereNot('article_id', $articleId)
            ],
            'slug.tr' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('article_locale', 'slug')
                    ->where('locale', 'tr')
                    ->whereNot('article_id', $articleId)
            ],
            'slug.ur' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('article_locale', 'slug')
                    ->where('locale', 'ur')
                    ->whereNot('article_id', $articleId)
            ],

            'excerpt' => 'nullable|array',
            'excerpt.ar' => 'nullable|string|max:500',
            'excerpt.en' => 'nullable|string|max:500',
            'excerpt.fr' => 'nullable|string|max:500',
            'excerpt.tr' => 'nullable|string|max:500',
            'excerpt.ur' => 'nullable|string|max:500',

            'description' => 'nullable|array',
            'description.ar' => 'nullable|string',
            'description.en' => 'nullable|string',
            'description.fr' => 'nullable|string',
            'description.tr' => 'nullable|string',
            'description.ur' => 'nullable|string',

            'content' => 'required|array',
            'content.ar' => 'required|string',
            'content.en' => 'required|string',
            'content.fr' => 'nullable|string',
            'content.tr' => 'nullable|string',
            'content.ur' => 'nullable|string',

            'summary' => 'nullable|array',
            'summary.ar' => 'nullable|string',
            'summary.en' => 'nullable|string',
            'summary.fr' => 'nullable|string',
            'summary.tr' => 'nullable|string',
            'summary.ur' => 'nullable|string',

            // SEO fields
            'seo_title' => 'nullable|array',
            'seo_title.ar' => 'nullable|string|max:70',
            'seo_title.en' => 'nullable|string|max:70',
            'seo_title.fr' => 'nullable|string|max:70',
            'seo_title.tr' => 'nullable|string|max:70',
            'seo_title.ur' => 'nullable|string|max:70',

            'seo_description' => 'nullable|array',
            'seo_description.ar' => 'nullable|string|max:160',
            'seo_description.en' => 'nullable|string|max:160',
            'seo_description.fr' => 'nullable|string|max:160',
            'seo_description.tr' => 'nullable|string|max:160',
            'seo_description.ur' => 'nullable|string|max:160',

            // Locale readiness
            'is_ready' => 'nullable|array',
            'is_ready.ar' => 'nullable|boolean',
            'is_ready.en' => 'nullable|boolean',
            'is_ready.fr' => 'nullable|boolean',
            'is_ready.tr' => 'nullable|boolean',
            'is_ready.ur' => 'nullable|boolean',

            // Metadata
            'category_id' => 'nullable|exists:categories,id',
            'additional_categories' => 'nullable|array',
            'additional_categories.*' => 'exists:categories,id',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',

            // Publishing
            'status' => 'required|in:draft,published,scheduled,archived',
            'published_at' => 'nullable|date',
            'is_featured' => 'nullable|boolean',
            'is_pinned' => 'nullable|boolean',

            // Media
            'thumb' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:5120',
            'gallery' => 'nullable|array',
            'gallery.*' => 'exists:media,id',
            'media_items' => 'nullable|array',
            'media_items.*' => 'exists:media,id',

            // TOC (Table of Contents)
            'toc' => 'nullable|array',
            'toc.ar' => 'nullable|array',
            'toc.ar.*.title' => 'nullable|string|max:200',
            'toc.ar.*.link' => 'nullable|string|max:255',
            'toc.en' => 'nullable|array',
            'toc.en.*.title' => 'nullable|string|max:200',
            'toc.en.*.link' => 'nullable|string|max:255',
            'toc.fr' => 'nullable|array',
            'toc.fr.*.title' => 'nullable|string|max:200',
            'toc.fr.*.link' => 'nullable|string|max:255',
            'toc.tr' => 'nullable|array',
            'toc.tr.*.title' => 'nullable|string|max:200',
            'toc.tr.*.link' => 'nullable|string|max:255',
            'toc.ur' => 'nullable|array',
            'toc.ur.*.title' => 'nullable|string|max:200',
            'toc.ur.*.link' => 'nullable|string|max:255',

            // References
            'references' => 'nullable|array',
            'references.*.name' => 'required_with:references|string|max:200',
            'references.*.url' => 'nullable|url|max:500',
            'references.*.type' => 'nullable|string|max:50',

            // Statistics
            'statistics' => 'nullable|array',
            'statistics.*.name' => 'required_with:statistics|string|max:200',
            'statistics.*.value' => 'required_with:statistics|string|max:200',

            // Advanced
            'writer_id' => 'nullable|exists:users,id',
            'read_time' => 'nullable|integer|min:1|max:999',
            'hijri_date' => 'nullable|string|max:50',
        ];
    }

    /**
     * Get custom validation messages
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.ar.required' => 'Arabic title is required',
            'title.en.required' => 'English title is required',
            'content.ar.required' => 'Arabic content is required',
            'content.en.required' => 'English content is required',
            'slug.*.unique' => 'This slug is already taken for this language',
            'category_id.exists' => 'Selected category does not exist',
            'additional_categories.*.exists' => 'One or more additional categories do not exist',
            'tags.*.exists' => 'One or more tags do not exist',
            'writer_id.exists' => 'Selected writer does not exist',
            'thumb.image' => 'Thumbnail must be an image file',
            'thumb.max' => 'Thumbnail size must not exceed 5MB',
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
            'title.ar' => 'Arabic title',
            'title.en' => 'English title',
            'title.fr' => 'French title',
            'title.tr' => 'Turkish title',
            'title.ur' => 'Urdu title',
            'content.ar' => 'Arabic content',
            'content.en' => 'English content',
            'content.fr' => 'French content',
            'content.tr' => 'Turkish content',
            'content.ur' => 'Urdu content',
            'category_id' => 'primary category',
            'additional_categories' => 'additional categories',
            'writer_id' => 'author',
            'read_time' => 'reading time',
            'hijri_date' => 'Hijri date',
        ];
    }

    /**
     * Prepare data for validation
     */
    protected function prepareForValidation(): void
    {
        // Convert string booleans to actual booleans
        if ($this->has('is_featured')) {
            $this->merge([
                'is_featured' => filter_var($this->is_featured, FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        if ($this->has('is_pinned')) {
            $this->merge([
                'is_pinned' => filter_var($this->is_pinned, FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        // Prepare is_ready array
        if ($this->has('is_ready')) {
            $isReady = [];
            foreach (['ar', 'en', 'fr', 'tr', 'ur'] as $locale) {
                $isReady[$locale] = filter_var($this->input("is_ready.{$locale}", false), FILTER_VALIDATE_BOOLEAN);
            }
            $this->merge(['is_ready' => $isReady]);
        }
    }
}
