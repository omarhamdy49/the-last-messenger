<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Domain\Seerah\Enums\EventEra;
use App\Domain\Seerah\Enums\SeerahEventStatus;
use App\Http\Requests\Admin\Content\Concerns\SanitizesRichText;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Store Seerah Event Request (Admin)
 *
 * The repeatable sections (statistics, metadata, sources, gallery, media_items)
 * follow one rule: an absent key means "not on this form, leave it alone"; a key
 * posted empty means "clear it". The form posts an empty hidden input for a list
 * the editor emptied, which arrives here as null.
 */
class StoreSeerahEventRequest extends FormRequest
{
    use SanitizesRichText;

    /**
     * Fields that no longer exist. Rejected loudly rather than silently dropped,
     * so a stale client or bookmarklet finds out.
     */
    private const REMOVED_FIELDS = [
        'hijri_date', 'gregorian_year', 'seerah_event_type_id', 'seerah_milestone_id',
        'display_order', 'importance', 'phase', 'is_pivotal', 'is_featured', 'latitude',
        'longitude', 'location', 'related_article_id', 'featured_image', 'featured_image_id',
        'gallery_ids', 'existing_gallery', 'lessons', 'timeline_items', 'participants',
    ];

    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'super_admin']);
    }

    /**
     * `content` and `description` are authored in the Quill editor, so they
     * arrive as HTML and are purified before validation and persistence.
     */
    protected function prepareForValidation(): void
    {
        $this->sanitizeRichTextField('content');
        $this->sanitizeRichTextField('description');
    }

    public function rules(): array
    {
        $locales = ['ar', 'en', 'fr', 'tr', 'ur'];
        $perLocale = static fn (string $field, array $rules): array => array_combine(
            array_map(static fn (string $locale): string => "{$field}.{$locale}", $locales),
            array_fill(0, count($locales), $rules),
        );

        return [
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9\-]+$/', 'unique:seerah_events,slug'],
            'event_type' => ['required', 'integer', Rule::exists('seerah_event_types', 'id')->where('is_active', true)],
            'sm_id' => ['nullable', 'integer', 'exists:seerah_milestones,id'],
            'order' => ['required', 'integer', 'min:0'],
            'status' => ['required', Rule::enum(SeerahEventStatus::class)],
            'era' => ['nullable', Rule::enum(EventEra::class)],

            'title' => ['required', 'array'],
            ...$perLocale('title', ['nullable', 'string', 'max:500']),
            'title.ar' => ['required', 'string', 'max:500'], // after the spread, which would overwrite it
            'description' => ['nullable', 'array'],
            ...$perLocale('description', ['nullable', 'string']),
            'content' => ['nullable', 'array'],
            ...$perLocale('content', ['nullable', 'string']),
            'dispute_note' => ['nullable', 'array'],
            ...$perLocale('dispute_note', ['nullable', 'string', 'max:1000']),

            'hijri_year' => ['nullable', 'integer', 'between:-100,100'],
            'hijri_month' => ['nullable', 'integer', 'between:1,12'],
            'hijri_day' => ['nullable', 'integer', 'between:1,30'],
            'gregorian_date' => ['nullable', 'date'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],

            'thumb' => ['nullable', 'file', 'image', 'max:10240'],
            'thumb_id' => ['nullable', 'integer', 'exists:media,id'],
            'remove_thumb' => ['nullable', 'boolean'],
            'cover' => ['nullable', 'file', 'image', 'max:10240'],
            'cover_id' => ['nullable', 'integer', 'exists:media,id'],
            'remove_cover' => ['nullable', 'boolean'],

            // Free-form label => value cards on the public event page.
            'statistics' => ['nullable', 'array', 'max:12'],
            'statistics.*.label' => ['nullable', 'string', 'max:120'],
            'statistics.*.value' => ['nullable', 'string', 'max:120'],

            // Per-locale repeatables on the translation row.
            'metadata' => ['nullable', 'array'],
            'metadata.*' => ['nullable', 'array', 'max:20'],
            'metadata.*.*.key' => ['nullable', 'string', 'max:255'],
            'metadata.*.*.value' => ['nullable', 'string', 'max:1000'],
            'sources' => ['nullable', 'array'],
            'sources.*' => ['nullable', 'array', 'max:20'],
            'sources.*.*.title' => ['nullable', 'string', 'max:255'],
            'sources.*.*.author' => ['nullable', 'string', 'max:255'],
            'sources.*.*.reference' => ['nullable', 'string', 'max:255'],

            // Media-library ids, in display order — same contract as the article form.
            'gallery' => ['nullable', 'array', 'max:24'],
            'gallery.*' => ['integer', 'distinct', 'exists:media,id'],
            'gallery_captions' => ['nullable', 'array'],
            'gallery_captions.*' => ['nullable', 'array'],
            'gallery_captions.*.*' => ['nullable', 'string', 'max:255'],
            'media_items' => ['nullable', 'array', 'max:24'],
            'media_items.*' => ['integer', 'distinct', 'exists:media,id'],

            ...array_fill_keys(self::REMOVED_FIELDS, ['prohibited']),
        ];
    }

    public function messages(): array
    {
        return [
            'title.ar.required' => __('admin.field_required'),
            'slug.unique' => __('admin.slug_taken'),
            'slug.regex' => __('admin.slug_format'),
        ];
    }
}
