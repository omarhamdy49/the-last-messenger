<?php

declare(strict_types=1);

namespace App\Domain\Seerah\DTOs;

use Illuminate\Http\UploadedFile;

/**
 * The validated admin payload for creating or updating a Seerah event.
 *
 * One DTO for both writes. A null property is ambiguous on its own — it can mean
 * "not submitted" or "submitted empty, clear it" — so $presentKeys lists the
 * input keys that were actually part of the validated payload, and the service
 * leaves an omitted field alone. That rule is what stops an untouched repeater
 * (sources, details, gallery, media) from blanking stored rows on save.
 */
final readonly class SeerahEventData
{
    /**
     * @param  array<string, string|null>  $title  Locale-keyed.
     * @param  array<string, string|null>  $description  Locale-keyed.
     * @param  array<string, string|null>  $content  Locale-keyed.
     * @param  array<string, string|null>  $disputeNote  Locale-keyed.
     * @param  array<string, string>  $statistics  label => value
     * @param  array<string, array<int, array{key: string, value: string}>>  $metadata  Locale-keyed rows.
     * @param  array<string, array<int, array<string, string>>>  $sources  Locale-keyed rows.
     * @param  array<int, int>  $gallery  Media-library ids, in display order.
     * @param  array<int|string, array<string, string|null>>  $galleryCaptions  media id => locale => caption
     * @param  array<int, int>  $mediaItems  Media-library ids.
     * @param  array<string, array{title: ?string, description: ?string}>  $seo  Locale-keyed.
     * @param  array<string, array<int, array{title: string, url: string}>>  $externalLinks  Locale-keyed rows.
     * @param  array<string, array<int, int>>  $links  LinkedContentType value => target ids, in order.
     * @param  array<int, string>  $presentKeys  Input keys present in the validated payload.
     */
    public function __construct(
        public ?string $slug,
        public array $title,
        public array $description,
        public array $content,
        public array $disputeNote,
        public ?int $eventType,
        public ?int $smId,
        public ?int $order,
        public ?int $hijriYear,
        public ?int $hijriMonth,
        public ?int $hijriDay,
        public ?string $gregorianDate,
        public ?int $locationId,
        public ?string $status,
        public ?string $era,
        public array $statistics,
        public array $metadata,
        public array $sources,
        public UploadedFile|int|null $thumb,
        public UploadedFile|int|null $cover,
        public bool $removeThumb,
        public bool $removeCover,
        public array $gallery,
        public array $galleryCaptions,
        public array $mediaItems,
        public array $seo,
        public array $externalLinks,
        public array $links,
        public array $presentKeys,
    ) {}

    public function hasInput(string $key): bool
    {
        return in_array($key, $this->presentKeys, true);
    }

    /**
     * @param  array<string, mixed>  $validated  Output of the Store/Update request's validated()
     */
    public static function fromArray(array $validated, ?UploadedFile $thumb = null, ?UploadedFile $cover = null): self
    {
        $int = static fn (string $key): ?int => isset($validated[$key]) && $validated[$key] !== '' ? (int) $validated[$key] : null;
        $ids = static fn (string $key): array => array_values(array_unique(array_filter(array_map('intval', (array) ($validated[$key] ?? [])))));

        return new self(
            slug: $validated['slug'] ?? null,
            title: $validated['title'] ?? [],
            description: $validated['description'] ?? [],
            content: $validated['content'] ?? [],
            disputeNote: $validated['dispute_note'] ?? [],
            eventType: $int('event_type'),
            smId: $int('sm_id'),
            order: $int('order'),
            hijriYear: $int('hijri_year'),
            hijriMonth: $int('hijri_month'),
            hijriDay: $int('hijri_day'),
            gregorianDate: $validated['gregorian_date'] ?? null,
            locationId: $int('location_id'),
            status: $validated['status'] ?? null,
            era: $validated['era'] ?? null,
            statistics: self::normaliseStatistics($validated['statistics'] ?? []),
            metadata: self::localeRows($validated['metadata'] ?? [], ['key', 'value'], 'key'),
            sources: self::localeRows($validated['sources'] ?? [], ['title', 'author', 'reference'], 'title'),
            // An upload wins over a media-library pick when both arrive.
            thumb: $thumb ?? $int('thumb_id'),
            cover: $cover ?? $int('cover_id'),
            removeThumb: filter_var($validated['remove_thumb'] ?? false, FILTER_VALIDATE_BOOLEAN),
            removeCover: filter_var($validated['remove_cover'] ?? false, FILTER_VALIDATE_BOOLEAN),
            gallery: $ids('gallery'),
            galleryCaptions: (array) ($validated['gallery_captions'] ?? []),
            mediaItems: $ids('media_items'),
            seo: self::seo($validated['seo'] ?? []),
            externalLinks: self::localeRows($validated['external_links'] ?? [], ['title', 'url'], 'url'),
            links: self::links($validated['links'] ?? []),
            presentKeys: array_keys($validated),
        );
    }

    /**
     * @return array<string, array{title: ?string, description: ?string}>
     */
    private static function seo(mixed $byLocale): array
    {
        $result = [];

        foreach (is_array($byLocale) ? $byLocale : [] as $locale => $fields) {
            $fields = is_array($fields) ? $fields : [];
            $result[(string) $locale] = [
                'title' => filled($fields['title'] ?? null) ? trim((string) $fields['title']) : null,
                'description' => filled($fields['description'] ?? null) ? trim((string) $fields['description']) : null,
            ];
        }

        return $result;
    }

    /**
     * Only the types that were submitted are kept, so a type the form did not
     * carry is left alone; a type submitted empty becomes [] (clear it).
     *
     * @return array<string, array<int, int>>
     */
    private static function links(mixed $byType): array
    {
        $result = [];

        foreach (is_array($byType) ? $byType : [] as $type => $ids) {
            $result[(string) $type] = array_values(array_unique(array_filter(array_map('intval', (array) $ids))));
        }

        return $result;
    }

    /**
     * Collapse the repeater rows into the label => value map the public event
     * page renders, dropping rows where either side was left blank.
     *
     * @return array<string, string>
     */
    private static function normaliseStatistics(mixed $rows): array
    {
        $stats = [];

        foreach (is_array($rows) ? $rows : [] as $row) {
            $label = trim((string) ($row['label'] ?? ''));
            $value = trim((string) ($row['value'] ?? ''));

            if ($label !== '' && $value !== '') {
                $stats[$label] = $value;
            }
        }

        return $stats;
    }

    /**
     * Keep only the named fields of each row and drop rows whose required field
     * is blank — the browser posts an empty template row whenever one is added
     * and left unfilled. A locale submitted empty stays in the map as [], which
     * is how "clear this list" is expressed.
     *
     * @param  array<int, string>  $fields
     * @return array<string, array<int, array<string, string>>>
     */
    private static function localeRows(mixed $byLocale, array $fields, string $required): array
    {
        $result = [];

        foreach (is_array($byLocale) ? $byLocale : [] as $locale => $rows) {
            $clean = [];

            foreach (is_array($rows) ? $rows : [] as $row) {
                $values = [];
                foreach ($fields as $field) {
                    $values[$field] = trim((string) (is_array($row) ? ($row[$field] ?? '') : ''));
                }

                if ($values[$required] !== '') {
                    $clean[] = $values;
                }
            }

            $result[(string) $locale] = $clean;
        }

        return $result;
    }
}
