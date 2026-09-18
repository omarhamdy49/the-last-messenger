<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Article;

use App\Domain\Blog\Enums\ArticleStatus;
use App\Http\Requests\Admin\Article\Concerns\SanitizesArticleContent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateArticleRequest extends FormRequest
{
    use SanitizesArticleContent;

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
        $locales = array_keys(config('app.supported_locales', ['ar' => [], 'en' => []]));
        $rules = [];

        // Multi-language fields
        $rules['title'] = 'required|array';
        $rules['slug'] = 'nullable|array';
        $rules['excerpt'] = 'nullable|array';
        $rules['description'] = 'nullable|array';
        $rules['content'] = 'required|array';
        $rules['content_blocks'] = 'nullable|array';
        $rules['summary'] = 'nullable|array';
        $rules['seo_title'] = 'nullable|array';
        $rules['seo_description'] = 'nullable|array';
        $rules['is_ready'] = 'nullable|array';

        foreach ($locales as $locale) {
            // Only Arabic title and content are required; other locales are optional
            $isRequired = $locale === 'ar';
            $rules["title.{$locale}"] = ($isRequired ? 'required' : 'nullable').'|string|max:200';
            $rules["slug.{$locale}"] = [
                'nullable',
                'string',
                'max:255',
                Rule::unique('article_locale', 'slug')
                    ->where('locale', $locale)
                    ->whereNot('article_id', $articleId),
            ];
            $rules["excerpt.{$locale}"] = 'nullable|string|max:500';
            $rules["description.{$locale}"] = 'nullable|string';
            $rules["content.{$locale}"] = $isRequired ? 'required' : 'nullable';
            $rules["content_blocks.{$locale}"] = 'nullable|string';
            $rules["summary.{$locale}"] = 'nullable|string';
            $rules["seo_title.{$locale}"] = 'nullable|string|max:70';
            $rules["seo_description.{$locale}"] = 'nullable|string|max:160';
            $rules["is_ready.{$locale}"] = 'nullable|boolean';
        }

        // TOC (Table of Contents) - dynamic per locale
        $rules['toc'] = 'nullable|array';
        foreach ($locales as $locale) {
            $rules["toc.{$locale}"] = 'nullable|array';
            $rules["toc.{$locale}.*.title"] = 'nullable|string|max:200';
            $rules["toc.{$locale}.*.link"] = 'nullable|string|max:255';
        }

        // Continue with rest of the rules (same as StoreArticleRequest)
        // Metadata
        $rules['category_id'] = 'nullable|exists:categories,id';
        $rules['additional_categories'] = 'nullable|array';
        $rules['additional_categories.*'] = 'exists:categories,id';
        $rules['tags'] = 'nullable|array';
        $rules['tags.*'] = 'exists:tags,id';

        // Publishing
        // Without a status rule, validated() drops the field and the DTO falls back
        // to the article's current status — making status unchangeable from the form.
        $rules['status'] = ['nullable', Rule::in(ArticleStatus::values())];
        $rules['scheduled_at'] = ['nullable', 'date', 'required_if:status,scheduled'];
        $rules['is_featured'] = 'nullable|boolean';
        $rules['is_pinned'] = 'nullable|boolean';

        // Media
        $rules['thumb'] = 'nullable|image|mimes:jpeg,jpg,png,webp|max:5120';
        $rules['thumb_id'] = 'nullable|integer|exists:media,id';
        $rules['cover'] = 'nullable|image|mimes:jpeg,jpg,png,webp|max:10240';
        $rules['cover_id'] = 'nullable|integer|exists:media,id';
        $rules['gallery'] = 'nullable|array';
        $rules['gallery.*'] = 'exists:media,id';
        $rules['media_items'] = 'nullable|array';
        $rules['media_items.*'] = 'exists:media,id';
        // Video queue: ids of existing episodes, in the editor's chosen order.
        $rules['episodes'] = 'nullable|array';
        $rules['episodes.*'] = 'integer|exists:media_episodes,id';

        // References
        $rules['references'] = 'nullable|array';
        $rules['references.*.name'] = 'required_with:references|string|max:200';
        $rules['references.*.url'] = 'nullable|string|max:500';
        $rules['references.*.type'] = 'nullable|string|max:50';

        // Statistics
        $rules['statistics'] = 'nullable|array';
        $rules['statistics.*.name'] = 'required_with:statistics|string|max:200';
        $rules['statistics.*.value'] = 'required_with:statistics|string|max:200';

        // Series (many-to-many)
        $rules['series'] = 'nullable|array';
        $rules['series.*'] = 'exists:series,id';

        // Advanced
        $rules['writer_id'] = 'nullable|exists:users,id';
        $rules['read_time'] = 'nullable|integer|min:1|max:999';
        $rules['hijri_date'] = 'nullable|string|max:50';

        return $rules;
    }

    /**
     * Get custom validation messages (same pattern as Store)
     */
    public function messages(): array
    {
        return [
            'title.ar.required' => 'Arabic title is required',
            'content.ar.required' => 'Arabic content is required',
            'slug.*.unique' => 'This slug is already taken for this language',
            'category_id.exists' => 'Selected category does not exist',
            'additional_categories.*.exists' => 'One or more additional categories do not exist',
            'tags.*.exists' => 'One or more tags do not exist',
            'writer_id.exists' => 'Selected writer does not exist',
            'thumb.image' => 'Thumbnail must be an image file',
            'thumb.max' => 'Thumbnail size must not exceed 5MB',
            'published_at.after' => 'Published date must be in the future for scheduled articles',
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

        // Decode content_blocks JSON strings into content arrays
        if ($this->has('content_blocks')) {
            $content = $this->input('content', []);
            $locales = array_keys(config('app.supported_locales', ['ar' => [], 'en' => []]));
            foreach ($locales as $locale) {
                $blocksJson = $this->input("content_blocks.{$locale}");
                if ($blocksJson) {
                    $decoded = json_decode($blocksJson, true);
                    if (is_array($decoded)) {
                        $content[$locale] = $decoded;
                    }
                }
            }
            $this->merge(['content' => $content]);
        }

        // Prepare is_ready array using supported locales from config
        if ($this->has('is_ready')) {
            $isReady = [];
            $locales = array_keys(config('app.supported_locales', ['ar' => [], 'en' => []]));
            foreach ($locales as $locale) {
                $isReady[$locale] = filter_var($this->input("is_ready.{$locale}", false), FILTER_VALIDATE_BOOLEAN);
            }
            $this->merge(['is_ready' => $isReady]);
        }

        // LAST: after content_blocks JSON has been decoded into `content`, so the
        // purifier sees the real block array rather than an opaque JSON string.
        $this->sanitizeArticleRichText();
    }
}
