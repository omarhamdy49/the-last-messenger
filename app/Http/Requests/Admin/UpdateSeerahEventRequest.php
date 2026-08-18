<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Domain\Seerah\Enums\EventEra;
use App\Domain\Seerah\Enums\EventPhase;
use App\Domain\Seerah\Enums\ParticipantRole;
use App\Http\Requests\Admin\Content\Concerns\SanitizesRichText;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * Update Seerah Event Request (Admin)
 */
class UpdateSeerahEventRequest extends FormRequest
{
    use SanitizesRichText;

    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'super_admin']);
    }

    /**
     * `content` is authored in the Quill editor, so it arrives as HTML and is
     * purified against the shared allowlist before validation and persistence.
     */
    protected function prepareForValidation(): void
    {
        $this->sanitizeRichTextField('content');
        $this->pruneEmptyMediaIds();
    }

    /**
     * Drop the empty media-library ids the form always posts.
     *
     * Each gallery slot renders a hidden `gallery_ids[]` input whether or not an
     * image was picked, so an untouched form submits eight empty strings and every
     * one of them fails the `integer` rule — silently rejecting the whole save.
     */
    private function pruneEmptyMediaIds(): void
    {
        $payload = $this->all();

        $payload['gallery_ids'] = array_values(array_filter(
            (array) ($payload['gallery_ids'] ?? []),
            static fn ($id): bool => $id !== null && $id !== '',
        ));

        if (($payload['featured_image_id'] ?? '') === '') {
            unset($payload['featured_image_id']);
        }

        $this->replace($payload);
    }

    public function rules(): array
    {
        $routeParameter = $this->route('seerah_event') ?? $this->route('id');
        $eventId = is_object($routeParameter) ? $routeParameter->id : $routeParameter;

        return [
            'slug' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('seerah_events', 'slug')->ignore($eventId)],
            'title' => ['sometimes', 'required', 'array'],
            'title.ar' => ['required_with:title', 'string', 'max:500'],
            'title.en' => ['nullable', 'string', 'max:500'],
            'title.fr' => ['nullable', 'string', 'max:500'],
            'title.tr' => ['nullable', 'string', 'max:500'],
            'title.ur' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'array'],
            'description.ar' => ['nullable', 'string'],
            'description.en' => ['nullable', 'string'],
            'description.fr' => ['nullable', 'string'],
            'description.tr' => ['nullable', 'string'],
            'description.ur' => ['nullable', 'string'],
            'content' => ['nullable', 'array'],
            'content.ar' => ['nullable', 'string'],
            'content.en' => ['nullable', 'string'],
            'content.fr' => ['nullable', 'string'],
            'content.tr' => ['nullable', 'string'],
            'content.ur' => ['nullable', 'string'],
            'dispute_note' => ['nullable', 'array'],
            'dispute_note.ar' => ['nullable', 'string', 'max:1000'],
            'dispute_note.en' => ['nullable', 'string', 'max:1000'],
            'dispute_note.fr' => ['nullable', 'string', 'max:1000'],
            'dispute_note.tr' => ['nullable', 'string', 'max:1000'],
            'dispute_note.ur' => ['nullable', 'string', 'max:1000'],
            'hijri_date' => ['nullable', 'string', 'max:100'],
            'hijri_year' => ['nullable', 'integer'],
            'hijri_month' => ['nullable', 'integer', 'between:1,12'],
            'hijri_day' => ['nullable', 'integer', 'between:1,30'],
            'gregorian_date' => ['nullable', 'date'],
            'gregorian_year' => ['nullable', 'integer'],
            'seerah_event_type_id' => ['nullable', 'integer', 'exists:seerah_event_types,id'],
            'seerah_milestone_id' => ['nullable', 'integer', 'exists:seerah_milestones,id'],
            'importance' => ['nullable', 'string', 'in:high,medium,low'],
            'status' => ['nullable', 'string', 'in:draft,published'],
            'location' => ['nullable', 'string', 'max:255'],
            // The canonical place. Additive: `location` above keeps the
            // historian's descriptive note.
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'related_article_id' => ['nullable', 'integer', 'exists:articles,id'],
            'featured_image' => ['nullable', 'file', 'image', 'max:10240'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'is_featured' => ['nullable', 'boolean'],
            'era' => ['nullable', new Enum(EventEra::class)],
            'phase' => ['nullable', new Enum(EventPhase::class)],
            'is_pivotal' => ['nullable', 'boolean'],

            // Statistics: free-form label => value pairs rendered as cards on the
            // public event page. Only scalars are displayed, so only scalars pass.
            'statistics' => ['nullable', 'array', 'max:12'],
            'statistics.*.label' => ['nullable', 'string', 'max:120'],
            'statistics.*.value' => ['nullable', 'string', 'max:120'],

            'featured_image_id' => ['nullable', 'integer', 'exists:media,id'],
            'gallery_ids' => ['nullable', 'array', 'max:20'],
            'gallery_ids.*' => ['integer', 'exists:media,id'],
            'gallery' => ['nullable', 'array', 'max:20'],
            'gallery.*' => ['nullable', 'file', 'image', 'max:10240'],
            'existing_gallery' => ['nullable', 'array', 'max:20'],
            'existing_gallery.*' => ['string', 'max:2048'],

            // الدروس والعبر + المصادر والمراجع (JSON arrays on the ar locale row).
            'lessons' => ['nullable', 'array', 'max:20'],
            'lessons.*.title' => ['nullable', 'string', 'max:255'],
            'lessons.*.description' => ['nullable', 'string', 'max:2000'],
            'sources' => ['nullable', 'array', 'max:20'],
            'sources.*.title' => ['nullable', 'string', 'max:255'],
            'sources.*.author' => ['nullable', 'string', 'max:255'],
            'sources.*.reference' => ['nullable', 'string', 'max:255'],

            // Inner timeline (the expandable sub-points on the event page).
            'timeline_items' => ['nullable', 'array', 'max:30'],
            'timeline_items.*.title' => ['nullable', 'array'],
            'timeline_items.*.title.ar' => ['required_with:timeline_items.*.description.ar', 'nullable', 'string', 'max:500'],
            'timeline_items.*.title.*' => ['nullable', 'string', 'max:500'],
            'timeline_items.*.description' => ['nullable', 'array'],
            'timeline_items.*.description.*' => ['nullable', 'string'],

            // Characters / participants.
            'participants' => ['nullable', 'array', 'max:40'],
            'participants.*.name' => ['nullable', 'array'],
            'participants.*.name.ar' => ['nullable', 'string', 'max:255'],
            'participants.*.name.*' => ['nullable', 'string', 'max:255'],
            'participants.*.description' => ['nullable', 'array'],
            'participants.*.description.*' => ['nullable', 'string', 'max:1000'],
            'participants.*.role' => ['nullable', new Enum(ParticipantRole::class)],
            'participants.*.participant_id' => ['nullable', 'integer', 'exists:muslims,id'],
        ];
    }
}
