<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Models;

use App\Domain\Blog\Models\Article;
use App\Domain\Seerah\Enums\EventImportance;
use App\Domain\Seerah\Enums\EventType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * SeerahEvent Model
 *
 * @property int $id
 * @property string $slug
 * @property array $title
 * @property array $description
 * @property array|null $content
 * @property string|null $hijri_date
 * @property int|null $hijri_year
 * @property int|null $hijri_month
 * @property int|null $hijri_day
 * @property string|null $gregorian_date
 * @property int|null $gregorian_year
 * @property EventType $event_type
 * @property EventImportance $importance
 * @property string|null $location
 * @property float|null $latitude
 * @property float|null $longitude
 * @property string|null $featured_image
 * @property array|null $gallery
 * @property int|null $related_article_id
 * @property int $views_count
 * @property int $display_order
 * @property bool $is_featured
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class SeerahEvent extends Model
{
    use HasFactory, HasTranslations;

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
        'title',
        'description',
        'content',
        'hijri_date',
        'hijri_year',
        'hijri_month',
        'hijri_day',
        'gregorian_date',
        'gregorian_year',
        'event_type',
        'importance',
        'location',
        'latitude',
        'longitude',
        'featured_image',
        'gallery',
        'related_article_id',
        'views_count',
        'display_order',
        'is_featured',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'title' => 'array',
        'description' => 'array',
        'content' => 'array',
        'hijri_year' => 'integer',
        'hijri_month' => 'integer',
        'hijri_day' => 'integer',
        'gregorian_date' => 'date',
        'gregorian_year' => 'integer',
        'event_type' => EventType::class,
        'importance' => EventImportance::class,
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'gallery' => 'array',
        'views_count' => 'integer',
        'display_order' => 'integer',
        'is_featured' => 'boolean',
    ];

    /**
     * Translatable attributes
     *
     * @var array<string>
     */
    public array $translatable = ['title', 'description', 'content'];

    /**
     * Get the related article
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'related_article_id');
    }

    /**
     * Get participants in this event
     */
    public function participants(): HasMany
    {
        return $this->hasMany(SeerahParticipant::class, 'event_id');
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
     * Scope: Filter by event type
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByType($query, EventType|string $type)
    {
        $typeValue = $type instanceof EventType ? $type->value : $type;

        return $query->where('event_type', $typeValue);
    }

    /**
     * Scope: Filter by importance
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByImportance($query, EventImportance|string $importance)
    {
        $importanceValue = $importance instanceof EventImportance ? $importance->value : $importance;

        return $query->where('importance', $importanceValue);
    }

    /**
     * Scope: Filter by Hijri year
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByHijriYear($query, int $year)
    {
        return $query->where('hijri_year', $year);
    }

    /**
     * Scope: Filter by Gregorian year
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByGregorianYear($query, int $year)
    {
        return $query->where('gregorian_year', $year);
    }

    /**
     * Scope: Filter by location
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByLocation($query, string $location)
    {
        return $query->where('location', 'like', "%{$location}%");
    }

    /**
     * Scope: Featured events
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope: Order chronologically (by Hijri date)
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeChronological($query, string $direction = 'asc')
    {
        return $query->orderBy('hijri_year', $direction)
            ->orderBy('hijri_month', $direction)
            ->orderBy('hijri_day', $direction)
            ->orderBy('display_order', $direction);
    }

    /**
     * Scope: Events with location
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithLocation($query)
    {
        return $query->whereNotNull('latitude')
            ->whereNotNull('longitude');
    }
}
