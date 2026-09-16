<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Models;

use App\Domain\Seerah\Enums\EventEra;
use App\Domain\Seerah\Enums\EventImportance;
use App\Domain\Seerah\Enums\EventPhase;
use App\Domain\Seerah\Enums\SeerahEventStatus;
use App\Domain\Shared\Models\Location;
use App\Domain\Shared\Traits\HasInteractions;
use App\Domain\Shared\Traits\HasLocalizedContent;
use App\Models\Article;
use Carbon\Carbon;
use Database\Factories\SeerahEventFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * SeerahEvent Model
 *
 * @property int $id
 * @property string $slug
 * @property string|null $hijri_date
 * @property int|null $hijri_year
 * @property int|null $hijri_month
 * @property int|null $hijri_day
 * @property string|null $gregorian_date
 * @property int|null $gregorian_year
 * @property int|null $seerah_event_type_id
 * @property EventImportance $importance
 * @property SeerahEventStatus $status
 * @property EventEra|null $era
 * @property EventPhase|null $phase
 * @property array|null $statistics
 * @property bool $is_pivotal
 * @property-read SeerahEventType|null $type
 * @property string|null $location
 * @property float|null $latitude
 * @property float|null $longitude
 * @property string|null $featured_image
 * @property array|null $gallery
 * @property int|null $related_article_id
 * @property int $views_count
 * @property int $display_order
 * @property bool $is_featured
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class SeerahEvent extends Model implements HasMedia
{
    /**
     * Editorial sequence is authoritative when dates are uncertain. Dates break
     * equal ranks (including the legacy default 0); id makes navigation stable.
     */
    public const CHRONOLOGY_COLUMNS = ['display_order', 'hijri_year', 'hijri_month', 'hijri_day', 'id'];

    use HasFactory, HasInteractions, HasLocalizedContent, InteractsWithMedia {
        getFirstMediaUrl as protected spatieGetFirstMediaUrl;
    }

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
        'hijri_date',
        'hijri_year',
        'hijri_month',
        'hijri_day',
        'gregorian_date',
        'gregorian_year',
        'seerah_event_type_id',
        'seerah_milestone_id',
        'importance',
        'status',
        'era',
        'phase',
        'location',
        'location_id',
        'latitude',
        'longitude',
        'featured_image',
        'gallery',
        'statistics',
        'related_article_id',
        'views_count',
        'display_order',
        'is_featured',
        'is_pivotal',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'hijri_year' => 'integer',
        'hijri_month' => 'integer',
        'hijri_day' => 'integer',
        'gregorian_date' => 'date',
        'gregorian_year' => 'integer',
        'seerah_event_type_id' => 'integer',
        'seerah_milestone_id' => 'integer',
        'location_id' => 'integer',
        'importance' => EventImportance::class,
        'status' => SeerahEventStatus::class,
        'era' => EventEra::class,
        'phase' => EventPhase::class,
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'gallery' => 'array',
        'statistics' => 'array',
        'views_count' => 'integer',
        'display_order' => 'integer',
        'is_featured' => 'boolean',
        'is_pivotal' => 'boolean',
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
     * Localized lessons (الدروس والعبر) as [{title, description}].
     *
     * Not a translatable field: HasLocalizedContent::getTranslation() is typed
     * `?string` and would fatal returning an array, so these read straight off
     * the locale row with a fallback to Arabic.
     *
     * @return array<int, array<string, string>>
     */
    public function lessonsFor(?string $locale = null): array
    {
        return $this->localizedArray('lessons', $locale);
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
     * Get the foreign key name for translations.
     */
    public function getTranslationForeignKey(): string
    {
        return 'seerah_event_id';
    }

    /**
     * Get the related article
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'related_article_id');
    }

    /**
     * Get the event type (DB-driven taxonomy)
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(SeerahEventType::class, 'seerah_event_type_id');
    }

    /**
     * Get the milestone (الحقبة) this event belongs to.
     */
    public function milestone(): BelongsTo
    {
        return $this->belongsTo(SeerahMilestone::class, 'seerah_milestone_id');
    }

    /**
     * The canonical place this event happened at.
     *
     * NAMED `place()`, NOT `location()`. `seerah_events.location` is an existing
     * string column holding the descriptive note, so an Eloquent relation called
     * `location` would be shadowed by it — `$event->location` returns the column
     * and never reaches the relation. Renaming the relation is a one-word change;
     * renaming the column would churn ~30 call sites for nothing.
     *
     * The free-text `location` column is NOT replaced by this — it holds
     * editorial prose ("حمراء الأسد على نحو 8 أميال جنوب المدينة") that no place
     * record can reconstruct. The two are read together: this supplies
     * coordinates and a localized name, `location` supplies the description.
     */
    public function place(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    /**
     * Coordinates for the map, in precedence order.
     *
     * The event's own coordinates win when it has them — an event may happen at
     * a specific spot within a place. Otherwise the place's coordinates are
     * used, which is what takes the map from 2 of 578 events to most of the 279
     * that name a place.
     *
     * @return array{latitude: float, longitude: float}|null
     */
    public function resolvedCoordinates(): ?array
    {
        if ($this->latitude !== null && $this->longitude !== null) {
            return [
                'latitude' => (float) $this->latitude,
                'longitude' => (float) $this->longitude,
            ];
        }

        $place = $this->relationLoaded('place') ? $this->place : null;

        if ($place?->hasCoordinates()) {
            return [
                'latitude' => (float) $place->latitude,
                'longitude' => (float) $place->longitude,
            ];
        }

        return null;
    }

    /**
     * Whether a map can be rendered for this event at all.
     */
    public function hasResolvableCoordinates(): bool
    {
        return $this->resolvedCoordinates() !== null;
    }

    /**
     * Scope: filter events by milestone id.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeByMilestone($query, int $milestoneId)
    {
        return $query->where('seerah_milestone_id', $milestoneId);
    }

    /**
     * Get participants in this event
     */
    public function participants(): HasMany
    {
        return $this->hasMany(SeerahParticipant::class, 'event_id');
    }

    /**
     * Get the inner timeline items of this event
     */
    public function timelineItems(): HasMany
    {
        return $this->hasMany(SeerahEventTimelineItem::class, 'seerah_event_id');
    }

    /**
     * Scope: Filter by era
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeByEra($query, EventEra|string $era)
    {
        $eraValue = $era instanceof EventEra ? $era->value : $era;

        return $query->where('era', $eraValue);
    }

    /**
     * Scope: Filter by phase
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeByPhase($query, EventPhase|string $phase)
    {
        $phaseValue = $phase instanceof EventPhase ? $phase->value : $phase;

        return $query->where('phase', $phaseValue);
    }

    /**
     * Scope: Pivotal events ("حدث مفصلي")
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopePivotal($query)
    {
        return $query->where('is_pivotal', true);
    }

    /**
     * Check if event has location data
     */
    public function hasLocation(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /**
     * Get formatted Hijri date
     */
    public function getFormattedHijriDate(): ?string
    {
        if (! $this->hijri_date) {
            return null;
        }

        return $this->hijri_date;
    }

    /**
     * Get formatted Gregorian date
     */
    public function getFormattedGregorianDate(): ?string
    {
        if (! $this->gregorian_date) {
            return null;
        }

        return $this->gregorian_date->format('F d, Y');
    }

    /**
     * Increment views count
     */
    public function incrementViews(int $amount = 1): void
    {
        $this->increment('views_count', $amount);
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
            return $query->where('seerah_event_type_id', (int) $type);
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
     * Scope: Filter by importance
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeByImportance($query, EventImportance|string $importance)
    {
        $importanceValue = $importance instanceof EventImportance ? $importance->value : $importance;

        return $query->where('importance', $importanceValue);
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
     * Scope: Filter by Gregorian year
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeByGregorianYear($query, int $year)
    {
        return $query->where('gregorian_year', $year);
    }

    /**
     * Scope: Filter by location
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeByLocation($query, string $location)
    {
        return $query->where('location', 'like', "%{$location}%");
    }

    /**
     * Scope: Featured events
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
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
     * Scope: Events with location
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeWithLocation($query)
    {
        return $query->whereNotNull('latitude')
            ->whereNotNull('longitude');
    }

    /**
     * Register media collections
     */
    public function registerMediaCollections(): void
    {
        // Featured image collection (single file)
        $this->addMediaCollection('featured_image')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'])
            ->useDisk('public');

        // Gallery collection (multiple files) - Historical photos, maps, artifacts
        $this->addMediaCollection('gallery')
            ->acceptsMimeTypes(['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'])
            ->useDisk('public');
    }

    /**
     * Register media conversions
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        // Thumbnail conversion (150x150 crop-center)
        $this->addMediaConversion('thumbnail')
            ->fit(Fit::Crop, 150, 150)
            ->sharpen(10)
            ->optimize()
            ->performOnCollections('featured_image', 'gallery')
            ->nonQueued();

        // Medium conversion (800x600 contain)
        $this->addMediaConversion('medium')
            ->fit(Fit::Contain, 800, 600)
            ->sharpen(10)
            ->optimize()
            ->performOnCollections('featured_image', 'gallery')
            ->nonQueued();

        // Large conversion (1200x800 contain)
        $this->addMediaConversion('large')
            ->fit(Fit::Contain, 1200, 800)
            ->sharpen(10)
            ->optimize()
            ->performOnCollections('featured_image', 'gallery')
            ->nonQueued();
    }

    /**
     * Get featured image URL (accessor with backward compatibility)
     */
    public function getFeaturedImageUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('featured_image') ?: $this->featured_image;
    }

    /**
     * Get the first media URL for a collection.
     *
     * The admin Seerah flow stores featured images in the dedicated
     * `featured_image` column, so fall back to that value before querying
     * attached Spatie media records.
     */
    public function getFirstMediaUrl(string $collectionName = 'default', string $conversionName = ''): string
    {
        if ($collectionName === 'featured_image' && ! empty($this->featured_image)) {
            if (Str::startsWith($this->featured_image, ['http://', 'https://'])) {
                return $this->featured_image;
            }

            return Storage::disk('public')->url($this->featured_image);
        }

        try {
            return $this->spatieGetFirstMediaUrl($collectionName, $conversionName);
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * The admin Seerah flow manages files outside Spatie's `media` table.
     */
    public function shouldDeletePreservingMedia(): bool
    {
        return true;
    }

    /**
     * Get featured image thumbnail URL
     */
    public function getFeaturedImageThumbnailAttribute(): ?string
    {
        return $this->getFirstMediaUrl('featured_image', 'thumbnail') ?: null;
    }

    /**
     * Get gallery images with conversions
     */
    public function getGalleryImagesAttribute(): array
    {
        return $this->getMedia('gallery')->map(fn ($media) => [
            'id' => $media->id,
            'url' => $media->getUrl(),
            'thumbnail' => $media->getUrl('thumbnail'),
            'medium' => $media->getUrl('medium'),
            'large' => $media->getUrl('large'),
            'alt' => $media->getCustomProperty('alt_text'),
            'caption' => $media->getCustomProperty('caption'),
        ])->toArray();
    }
}
