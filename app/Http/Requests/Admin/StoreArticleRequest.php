<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Domain\Blog\Enums\ArticleStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Store Article Request (Admin)
 * Validates article creation data for admin portal
 *
 * Handles multi-language content validation with proper authorization,
 * SEO metadata, and image uploads for article creation.
 */
class StoreArticleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Only admin and editor roles can create articles
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasAnyRole(['admin', 'super_admin', 'editor']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Basic fields
            'slug' => ['sometimes', 'string', 'max:255', 'unique:articles,slug'],

            // Relationships
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'series_id' => ['nullable', 'integer', 'exists:series,id'],

            // Multi-language fields - Title (Required)
            'title' => ['required', 'array', 'min:1'],
            'title.ar' => ['required', 'string', 'max:500'],
            'title.en' => ['nullable', 'string', 'max:500'],
            'title.fr' => ['nullable', 'string', 'max:500'],
            'title.tr' => ['nullable', 'string', 'max:500'],
            'title.ur' => ['nullable', 'string', 'max:500'],

            // Multi-language fields - Excerpt (Optional)
            'excerpt' => ['nullable', 'array'],
            'excerpt.ar' => ['nullable', 'string', 'max:1000'],
            'excerpt.en' => ['nullable', 'string', 'max:1000'],
            'excerpt.fr' => ['nullable', 'string', 'max:1000'],
            'excerpt.tr' => ['nullable', 'string', 'max:1000'],
            'excerpt.ur' => ['nullable', 'string', 'max:1000'],

            // Multi-language fields - Content (Required)
            'content' => ['required', 'array', 'min:1'],
            'content.ar' => ['required', 'string', 'min:50'],
            'content.en' => ['nullable', 'string', 'min:50'],
            'content.fr' => ['nullable', 'string', 'min:50'],
            'content.tr' => ['nullable', 'string', 'min:50'],
            'content.ur' => ['nullable', 'string', 'min:50'],

            // Image upload (when uploading file directly)
            'featured_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:10240'],

            // Media library selection (when selecting from media picker)
            'featured_image_media_id' => ['nullable', 'exists:media,id'],

            // OG image
            'og_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'og_image_media_id' => ['nullable', 'exists:media,id'],

            // Gallery images (multiple files)
            'gallery' => ['nullable', 'array', 'max:10'],
            'gallery.*' => ['image', 'mimes:jpeg,jpg,png,webp,gif', 'max:10240'],

            // Gallery media IDs (when selecting from media picker)
            'gallery_media_ids' => ['nullable', 'array', 'max:10'],
            'gallery_media_ids.*' => ['exists:media,id'],

            // Status and flags
            'status' => ['nullable', 'string', Rule::in(ArticleStatus::values())],
            'featured' => ['nullable', 'boolean'],
            'is_pinned' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'], // Alias for backward compatibility

            // Publishing
            'published_at' => ['nullable', 'date', 'after_or_equal:now'],

            // Tags
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', 'exists:tags,id'],

            // SEO Metadata
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:160'],
            'seo_keywords' => ['nullable', 'string', 'max:500'],
            'og_image' => ['nullable', 'string', 'max:500'],

            // Legacy meta fields (backward compatibility)
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'meta_keywords' => ['nullable', 'string', 'max:500'],
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
            // Title validation messages
            'title.required' => 'The article title is required in at least one language.',
            'title.array' => 'The title must be provided as translations for different languages.',
            'title.ar.required' => 'The Arabic title is required.',
            'title.ar.max' => 'The Arabic title cannot exceed 500 characters.',
            'title.en.max' => 'The English title cannot exceed 500 characters.',
            'title.fr.max' => 'The French title cannot exceed 500 characters.',
            'title.tr.max' => 'The Turkish title cannot exceed 500 characters.',
            'title.ur.max' => 'The Urdu title cannot exceed 500 characters.',

            // Content validation messages
            'content.required' => 'The article content is required in at least one language.',
            'content.array' => 'The content must be provided as translations for different languages.',
            'content.ar.required' => 'The Arabic content is required.',
            'content.ar.min' => 'The Arabic content must be at least 50 characters.',
            'content.en.min' => 'The English content must be at least 50 characters.',
            'content.fr.min' => 'The French content must be at least 50 characters.',
            'content.tr.min' => 'The Turkish content must be at least 50 characters.',
            'content.ur.min' => 'The Urdu content must be at least 50 characters.',

            // Excerpt validation messages
            'excerpt.array' => 'The excerpt must be provided as translations for different languages.',
            'excerpt.ar.max' => 'The Arabic excerpt cannot exceed 1000 characters.',
            'excerpt.en.max' => 'The English excerpt cannot exceed 1000 characters.',
            'excerpt.fr.max' => 'The French excerpt cannot exceed 1000 characters.',
            'excerpt.tr.max' => 'The Turkish excerpt cannot exceed 1000 characters.',
            'excerpt.ur.max' => 'The Urdu excerpt cannot exceed 1000 characters.',

            // Relationship validation messages
            'category_id.exists' => 'The selected category does not exist.',
            'series_id.exists' => 'The selected series does not exist.',

            // Slug validation messages
            'slug.unique' => 'This slug is already taken. Please choose a different one.',
            'slug.max' => 'The slug cannot exceed 255 characters.',

            // Image validation messages
            'featured_image.image' => 'The featured image must be a valid image file.',
            'featured_image.mimes' => 'The featured image must be in JPEG, PNG, WebP, or GIF format.',
            'featured_image.max' => 'The featured image size cannot exceed 10MB.',
            'featured_image_media_id.exists' => 'The selected media does not exist.',

            // OG image validation messages
            'og_image.image' => 'The OG image must be a valid image file.',
            'og_image.mimes' => 'The OG image must be in JPEG, PNG, or WebP format.',
            'og_image.max' => 'The OG image size cannot exceed 5MB.',
            'og_image_media_id.exists' => 'The selected OG image does not exist.',

            // Gallery validation messages
            'gallery.array' => 'The gallery must be an array of images.',
            'gallery.max' => 'You cannot upload more than 10 gallery images.',
            'gallery.*.image' => 'All gallery items must be valid image files.',
            'gallery.*.mimes' => 'Gallery images must be in JPEG, PNG, WebP, or GIF format.',
            'gallery.*.max' => 'Each gallery image cannot exceed 10MB.',
            'gallery_media_ids.array' => 'The gallery media IDs must be an array.',
            'gallery_media_ids.max' => 'You cannot select more than 10 gallery images.',
            'gallery_media_ids.*.exists' => 'One or more selected gallery images do not exist.',

            // Status validation messages
            'status.in' => 'The selected status is invalid. Valid options are: draft, published, or archived.',

            // Tags validation messages
            'tags.array' => 'The tags must be provided as an array.',
            'tags.*.exists' => 'One or more selected tags do not exist.',

            // SEO validation messages
            'seo_title.max' => 'The SEO title cannot exceed 255 characters.',
            'seo_description.max' => 'The SEO description cannot exceed 160 characters for optimal search results.',
            'seo_keywords.max' => 'The SEO keywords cannot exceed 500 characters.',
            'og_image.max' => 'The Open Graph image URL cannot exceed 500 characters.',

            // Publishing validation messages
            'published_at.date' => 'The publication date must be a valid date.',
            'published_at.after_or_equal' => 'The publication date cannot be in the past.',

            // Boolean field validation messages
            'featured.boolean' => 'The featured flag must be true or false.',
            'is_pinned.boolean' => 'The pinned flag must be true or false.',
            'is_featured.boolean' => 'The featured flag must be true or false.',
        ];
    }

    /**
     * Get custom attribute names for error messages.
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
            'excerpt.ar' => 'Arabic excerpt',
            'excerpt.en' => 'English excerpt',
            'excerpt.fr' => 'French excerpt',
            'excerpt.tr' => 'Turkish excerpt',
            'excerpt.ur' => 'Urdu excerpt',
            'category_id' => 'category',
            'series_id' => 'series',
            'featured_image' => 'featured image',
            'seo_title' => 'SEO title',
            'seo_description' => 'SEO description',
            'seo_keywords' => 'SEO keywords',
            'og_image' => 'Open Graph image',
        ];
    }

    /**
     * Prepare data for validation
     *
     * Sanitizes input and sets default values
     */
    protected function prepareForValidation(): void
    {
        $data = [];

        // Set author to current user if not provided
        if (! $this->has('user_id')) {
            $data['user_id'] = $this->user()->id;
        }

        // Set default status if not provided
        if (! $this->has('status')) {
            $data['status'] = ArticleStatus::DRAFT->value;
        }

        // Handle backward compatibility for featured flag
        if ($this->has('is_featured') && ! $this->has('featured')) {
            $data['featured'] = $this->boolean('is_featured');
        }

        // Handle backward compatibility for meta fields -> seo fields
        if ($this->has('meta_title') && ! $this->has('seo_title')) {
            $data['seo_title'] = $this->input('meta_title');
        }
        if ($this->has('meta_description') && ! $this->has('seo_description')) {
            $data['seo_description'] = $this->input('meta_description');
        }
        if ($this->has('meta_keywords') && ! $this->has('seo_keywords')) {
            $data['seo_keywords'] = $this->input('meta_keywords');
        }

        // Trim whitespace from all string fields in multi-language arrays
        foreach (['title', 'excerpt', 'content'] as $field) {
            if ($this->has($field) && is_array($this->input($field))) {
                $sanitized = [];
                foreach ($this->input($field) as $lang => $value) {
                    if (is_string($value)) {
                        $sanitized[$lang] = trim($value);
                    }
                }
                if (! empty($sanitized)) {
                    $data[$field] = $sanitized;
                }
            }
        }

        // Trim SEO fields
        foreach (['seo_title', 'seo_description', 'seo_keywords', 'og_image'] as $field) {
            if ($this->has($field) && is_string($this->input($field))) {
                $data[$field] = trim($this->input($field));
            }
        }

        if (! empty($data)) {
            $this->merge($data);
        }
    }

    /**
     * Get validated data with proper type casting
     *
     * @return array
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        // Ensure boolean fields are properly cast
        if (isset($validated['featured'])) {
            $validated['featured'] = (bool) $validated['featured'];
        }
        if (isset($validated['is_pinned'])) {
            $validated['is_pinned'] = (bool) $validated['is_pinned'];
        }

        return $validated;
    }
}
