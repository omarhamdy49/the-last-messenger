<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Models;

use App\Domain\Seerah\Enums\EventEra;
use App\Domain\Seerah\Enums\SeerahEventStatus;
use App\Domain\Shared\Models\Location;
use App\Domain\Shared\Services\PublicImageService;
use App\Domain\Shared\Traits\HasInteractions;
use App\Domain\Shared\Traits\HasLocalizedContent;
use App\Utils\DateUtil;
use Carbon\Carbon;
use Database\Factories\SeerahEventFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * SeerahEvent Model
 *
 * @property int $id
 * @property string $slug
 * @property int $event_type
 * @property int|null $sm_id
 * @property int $order
 * @property string|null $thumb_url Public-disk path.
 * @property string|null $cover_url Public-disk path.
 * @property int|null $hijri_year Negative = before the Hijra.
 * @property int|null $hijri_month
 * @property int|null $hijri_day
 * @property Carbon|null $gregorian_date
 * @property int|null $location_id
 * @property SeerahEventStatus $status
 * @property EventEra|null $era
 * @property array<string, string>|null $statistics label => value
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read SeerahEventType|null $type
 * @property-read SeerahMilestone|null $milestone
 * @property-read Location|null $place
 */
class SeerahEvent extends Model
{
    /**
     * Editorial sequence is authoritative when dates are uncertain. Dates break
     * equal ranks; id makes navigation stable.
     */
    public const CHRONOLOGY_COLUMNS = ['order', 'hijri_year', 'hijri_month', 'hijri_day', 'id'];

    use HasFactory, HasInteractions, HasLocalizedContent;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'seerah_events';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'slug',
        'event_type',
        'sm_id',
        'order',
        'thumb_url',
        'cover_url',
        'hijri_year',
        'hijri_month',
        'hijri_day',
        'gregorian_date',
        'location_id',
        'status',
        'era',
        'statistics',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'event_type' => 'integer',
        'sm_id' => 'integer',
        'order' => 'integer',
        'hijri_year' => 'integer',
        'hijri_month' => 'integer',
        'hijri_day' => 'integer',
        'gregorian_date' => 'date',
        'location_id' => 'integer',
        'status' => SeerahEventStatus::class,
        'era' => EventEra::class,
        'statistics' => 'array',
    ];

    /**
     * Resolve the factory for this model (custom Domain namespace).
     */
    protected static function newFactory(): SeerahEventFactory
    {
        return SeerahEventFactory::new();
    }

    /**
     * Get the translation model class.
     */
    public function getTranslationModelClass(): string
    {
        return SeerahEventTranslation::class;
    }

    /**
     * Get the list of translatable field names.
     */
    public function getTranslatableFields(): array
    {
        return ['title', 'description', 'content', 'dispute_note'];
    }

    /**
     * Get the foreign key name for translations.
     */
    public function getTranslationForeignKey(): string
    {
        return 'event_id';
    }

    /**
     * Localized key/value details as [{key, value}].
     *
     * @return array<int, array{key: string, value: string}>
     */
    public function metadataFor(?string $locale = null): array
    {
        return $this->localizedArray('metadata', $locale);
    }

    /**
     * Localized sources (المصادر والمراجع) as [{title, author, reference}].
     *
     * @return array<int, array<string, string>>
     */
    public function sourcesFor(?string $locale = null): array
    {
        return $this->localizedArray('sources', $locale);
    }

    /**
     * Localized external links as [{title, url}].
     *
     * @return array<int, array{title: string, url: string}>
     */
    public function externalLinksFor(?string $locale = null): array
    {
        return $this->localizedArray('external_links', $locale);
    }

    /**
     * SEO title/description for exactly this locale (no Arabic fallback — a
     * page in English must not advertise itself with Arabic meta).
     *
     * @return array{title: ?string, description: ?string}
     */
    public function seoFor(?string $locale = null): array
    {
        $locale ??= app()->getLocale();
        $seo = $this->translations->firstWhere('locale', $locale)?->seo_metadata ?? [];

        return [
            'title' => filled($seo['title'] ?? null) ? $seo['title'] : null,
            'description' => filled($seo['description'] ?? null) ? $seo['description'] : null,
        ];
    }

    /**
     * Not translatable fields: HasLocalizedContent::getTranslation() is typed
     * `?string` and would fatal returning an array, so these read straight off
     * the locale row with a fallback to Arabic.
     *
     * @return array<int, array<string, string>>
     */
    private function localizedArray(string $field, ?string $locale = null): array
    {
        $locale ??= app()->getLocale();

        foreach ([$locale, 'ar'] as $candidate) {
            $row = $this->translations->firstWhere('locale', $candidate);
            $value = $row?->{$field};

            if (is_array($value) && $value !== []) {
                return $value;
            }
        }

        return [];
    }

    /**
     * Get the event type (DB-driven taxonomy)
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(SeerahEventType::class, 'event_type');
    }

    /**
     * Get the milestone (الحقبة) this event belongs to.
     */
    public function milestone(): BelongsTo
    {
        return $this->belongsTo(SeerahMilestone::class, 'sm_id');
    }

    /**
     * The canonical place this event happened at — the only place field.
     */
    public function place(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    /**
     * Gallery images, in the editor's order.
     */
    public function gallery(): HasMany
    {
        return $this->hasMany(SeerahEventGalleryItem::class, 'event_id')->orderBy('order');
    }

    /**
     * Attached media-library items (ids only — resolved via MediaLibraryLookup).
     */
    public function mediaLinks(): HasMany
    {
        return $this->hasMany(SeerahEventMedia::class, 'event_id');
    }

    /**
     * Links to content in other modules (ids only — resolved via ContentReferenceLookup).
     */
    public function links(): HasMany
    {
        return $this->hasMany(SeerahEventLink::class, 'event_id')->orderBy('type')->orderBy('order');
    }

    /**
     * Linked target ids grouped by type, in editor order.
     *
     * @return array<string, array<int, int>>
     */
    public function linkIdsByType(): array
    {
        return $this->links
            ->groupBy(fn (SeerahEventLink $link): string => $link->type->value)
            ->map(fn ($links): array => $links->sortBy('order')->pluck('target_id')->map(fn ($id): int => (int) $id)->values()->all())
            ->all();
    }

    /**
     * Map coordinates, taken from the linked place.
     *
     * @return array{latitude: float, longitude: float}|null
     */
    public function resolvedCoordinates(): ?array
    {
        $place = $this->relationLoaded('place') ? $this->place : null;

        if (! $place?->hasCoordinates()) {
            return null;
        }

        return [
            'latitude' => (float) $place->latitude,
            'longitude' => (float) $place->longitude,
        ];
    }

    /**
     * Whether a map can be rendered for this event at all.
     */
    public function hasResolvableCoordinates(): bool
    {
        return $this->resolvedCoordinates() !== null;
    }

    /**
     * "17 رمضان 2 هـ" composed from whichever Hijri parts are known; null
     * without a year. Never invents a day or month that was not recorded.
     */
    public function formattedHijriDate(?string $locale = null): ?string
    {
        if ($this->hijri_year === null) {
            return null;
        }

        $locale ??= app()->getLocale();
        $months = $locale === 'ar' ? DateUtil::getHijriMonthNames() : DateUtil::getHijriMonthNamesEnglish();

        $parts = [];

        if ($this->hijri_month !== null && isset($months[$this->hijri_month])) {
            if ($this->hijri_day !== null) {
                $parts[] = (string) $this->hijri_day;
            }
            $parts[] = $months[$this->hijri_month];
        }

        $parts[] = SeerahMilestone::hijriYearLabel($this->hijri_year);

        return implode(' ', $parts);
    }

    /**
     * Get formatted Gregorian date
     */
    public function getFormattedGregorianDate(): ?string
    {
        return $this->gregorian_date?->format('F d, Y');
    }

    /**
     * Image for cards and list rows: the thumbnail, else the cover.
     */
    public function cardImageUrl(): ?string
    {
        return PublicImageService::url($this->thumb_url ?? $this->cover_url, 'card');
    }

    /**
     * Image for the event page itself: the cover, else the thumbnail.
     */
    public function pageImageUrl(): ?string
    {
        return PublicImageService::url($this->cover_url ?? $this->thumb_url, 'hero');
    }

    /**
     * Scope: filter events by milestone id.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeByMilestone($query, int $milestoneId)
    {
        return $query->where('sm_id', $milestoneId);
    }

    /**
     * Scope: Filter by era
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeByEra($query, EventEra|string $era)
    {
        return $query->where('era', $era instanceof EventEra ? $era->value : $era);
    }

    /**
     * Scope: Filter by event type.
     *
     * Accepts a type id (int) or a type slug (string, resolved via the relation).
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeByType($query, int|string $type)
    {
        if (is_int($type) || ctype_digit((string) $type)) {
            return $query->where('event_type', (int) $type);
        }

        return $query->whereHas('type', fn ($q) => $q->where('slug', $type));
    }

    /**
     * Scope: Published events only (visible to the public)
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopePublished($query)
    {
        return $query->where('status', SeerahEventStatus::PUBLISHED->value);
    }

    /**
     * Scope: Draft events only
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeDraft($query)
    {
        return $query->where('status', SeerahEventStatus::DRAFT->value);
    }

    /**
     * Scope: Filter by Hijri year
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeByHijriYear($query, int $year)
    {
        return $query->where('hijri_year', $year);
    }

    /**
     * Scope: Filter by Gregorian year — a range, so the date index stays usable.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeByGregorianYear($query, int $year)
    {
        return $query->whereBetween('gregorian_date', [sprintf('%04d-01-01', $year), sprintf('%04d-12-31', $year)]);
    }

    /**
     * Scope: Order by the editorial chronology without requiring an exact date.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeChronological($query, string $direction = 'asc')
    {
        foreach (self::CHRONOLOGY_COLUMNS as $column) {
            $query->orderBy($column, $direction);
        }

        return $query;
    }

    /**
     * Scope: Events whose linked place has coordinates (for the map).
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeWithLocation($query)
    {
        return $query->whereHas('place', fn ($q) => $q->whereNotNull('latitude')->whereNotNull('longitude'));
    }
}
