<?php

declare(strict_types=1);

namespace App\Domain\Sahaba\Models;

use App\Domain\Sahaba\Enums\CompanionStatus;
use App\Domain\Shared\Traits\HasInteractions;
use App\Domain\Shared\Traits\HasLocalizedContent;
use App\Models\Article;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Sahabi Model (Companion of Prophet Muhammad ﷺ)
 *
 * @property int $id
 * @property string $slug
 * @property string $gender
 * @property CompanionStatus $status
 * @property string|null $birth_date_hijri
 * @property string|null $death_date_hijri
 * @property \Carbon\Carbon|null $birth_date_gregorian
 * @property \Carbon\Carbon|null $death_date_gregorian
 * @property int|null $age_at_death
 * @property string|null $birth_place
 * @property string|null $death_place
 * @property string|null $burial_place
 * @property int|null $father_id
 * @property int|null $mother_id
 * @property string|null $tribe
 * @property string|null $clan
 * @property string|null $conversion_date_hijri
 * @property int|null $conversion_order
 * @property array|null $roles
 * @property int $hadith_count
 * @property string|null $profile_image
 * @property array|null $gallery
 * @property int|null $related_article_id
 * @property int $views_count
 * @property bool $is_featured
 * @property int $importance_rank
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Sahabi extends Model implements HasMedia
{
    use HasFactory, HasInteractions, HasLocalizedContent, InteractsWithMedia, SoftDeletes {
        getFirstMediaUrl as protected spatieGetFirstMediaUrl;
    }

    /**
     * The custom `media` table uses mediable_* columns, so Spatie queries throw
     * (KI-17). Serve the profile_image column first and never surface the SQL error.
     */
    public function getFirstMediaUrl(string $collectionName = 'default', string $conversionName = ''): string
    {
        if ($collectionName === 'profile_image' && ! empty($this->profile_image)) {
            if (str_starts_with($this->profile_image, 'http://') || str_starts_with($this->profile_image, 'https://')) {
                return $this->profile_image;
            }

            return \Illuminate\Support\Facades\Storage::disk('public')->url($this->profile_image);
        }

        try {
            return $this->spatieGetFirstMediaUrl($collectionName, $conversionName);
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sahaba';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'slug',
        'gender',
        'status',
        'birth_date_hijri',
        'death_date_hijri',
        'birth_date_gregorian',
        'death_date_gregorian',
        'age_at_death',
        'birth_place',
        'death_place',
        'burial_place',
        'father_id',
        'mother_id',
        'tribe',
        'clan',
        'conversion_date_hijri',
        'conversion_order',
        'roles',
        'hadith_count',
        'profile_image',
        'gallery',
        'related_article_id',
        'views_count',
        'is_featured',
        'importance_rank',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'birth_date_gregorian' => 'date',
        'death_date_gregorian' => 'date',
        'age_at_death' => 'integer',
        'conversion_order' => 'integer',
        'roles' => 'array',
        'hadith_count' => 'integer',
        'gallery' => 'array',
        'status' => CompanionStatus::class,
        'views_count' => 'integer',
        'is_featured' => 'boolean',
        'importance_rank' => 'integer',
    ];

    /**
     * Get the translation model class.
     */
    public function getTranslationModelClass(): string
    {
        return SahabiTranslation::class;
    }

    /**
     * Get the list of translatable field names.
     */
    public function getTranslatableFields(): array
    {
        return ['name', 'full_name', 'kunya', 'laqab', 'biography', 'virtues', 'famous_narrations', 'martyrdom'];
    }

    /**
     * Get the foreign key name for translations.
     */
    public function getTranslationForeignKey(): string
    {
        return 'sahabi_id';
    }

    /**
     * Get the related article
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'related_article_id');
    }

    /**
     * Get father (from people table)
     */
    public function father(): BelongsTo
    {
        return $this->belongsTo('App\Domain\Genealogy\Models\Person', 'father_id');
    }

    /**
     * Get mother (from people table)
     */
    public function mother(): BelongsTo
    {
        return $this->belongsTo('App\Domain\Genealogy\Models\Person', 'mother_id');
    }

    /**
     * Get all battle participation records for this companion.
     */
    public function battles(): HasMany
    {
        return $this->hasMany(SahabiBattle::class);
    }

    /**
     * Get the personal timeline entries for this companion.
     */
    public function timelineItems(): HasMany
    {
        return $this->hasMany(SahabiTimelineItem::class)->orderBy('position');
    }

    /**
     * Get people related to this companion
     */
    public function people(): HasMany
    {
        return $this->hasMany('App\Domain\Genealogy\Models\Person', 'sahaba_id');
    }

    /**
     * Increment views count
     */
    public function incrementViews(int $amount = 1): void
    {
        $this->increment('views_count', $amount);
    }

    /**
     * Check if companion participated in a battle.
     *
     * Uses the pivot relation. When the battles relation is already
     * eager-loaded this is O(n) over the cached collection; otherwise
     * it issues a single EXISTS-style count query.
     */
    public function participatedInBattle(string $battleName): bool
    {
        if ($this->relationLoaded('battles')) {
            return $this->battles
                ->contains('battle_name', $battleName);
        }

        return $this->battles()
            ->where('battle_name', $battleName)
            ->exists();
    }

    /**
     * Get display name (combines name and laqab)
     */
    public function getDisplayName(string $language = 'ar'): string
    {
        $name = $this->getTranslation('name', $language) ?? '';
        $laqab = $this->getTranslation('laqab', $language);

        if ($laqab) {
            return "{$name} {$laqab}";
        }

        return $name;
    }

    /**
     * Scope: Filter by status
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStatus($query, CompanionStatus|string $status)
    {
        $statusValue = $status instanceof CompanionStatus ? $status->value : $status;

        return $query->where('status', $statusValue);
    }

    /**
     * Scope: Filter by gender
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByGender($query, string $gender)
    {
        return $query->where('gender', $gender);
    }

    /**
     * Scope: Filter by tribe
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByTribe($query, string $tribe)
    {
        return $query->where('tribe', 'like', "%{$tribe}%");
    }

    /**
     * Scope: Participated in battle
     *
     * Uses a JOIN against sahabi_battles so the query is backed by the
     * composite index (battle_name, sahabi_id) rather than a full-table
     * JSON scan. The join is against sahaba.id to avoid a subquery.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeParticipatedIn($query, string $battle)
    {
        return $query
            ->join('sahabi_battles', 'sahaba.id', '=', 'sahabi_battles.sahabi_id')
            ->where('sahabi_battles.battle_name', $battle)
            ->select('sahaba.*');
    }

    /**
     * Scope: Featured companions
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope: Order by importance
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByImportance($query, string $direction = 'desc')
    {
        return $query->orderBy('importance_rank', $direction);
    }

    /**
     * Scope: Early Muslims (by conversion order)
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEarlyMuslims($query, int $limit = 10)
    {
        return $query->whereNotNull('conversion_order')
            ->orderBy('conversion_order')
            ->limit($limit);
    }

    /**
     * Scope: Most hadith narrators
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeTopNarrators($query, int $limit = 10)
    {
        return $query->where('hadith_count', '>', 0)
            ->orderByDesc('hadith_count')
            ->limit($limit);
    }

    /**
     * Register media collections
     */
    public function registerMediaCollections(): void
    {
        // Profile image collection (single file)
        $this->addMediaCollection('profile_image')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'])
            ->useDisk('public');

        // Gallery collection (multiple files) - Historical images, artifacts, etc.
        $this->addMediaCollection('gallery')
            ->acceptsMimeTypes(['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'])
            ->useDisk('public');
    }

    /**
     * Register media conversions
     */
    public function registerMediaConversions(?\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        // Avatar conversion (100x100 crop-center for small profile pics)
        $this->addMediaConversion('avatar')
            ->fit(\Spatie\Image\Enums\Fit::Crop, 100, 100)
            ->sharpen(10)
            ->optimize()
            ->performOnCollections('profile_image')
            ->nonQueued();

        // Thumbnail conversion (150x150 crop-center)
        $this->addMediaConversion('thumbnail')
            ->fit(\Spatie\Image\Enums\Fit::Crop, 150, 150)
            ->sharpen(10)
            ->optimize()
            ->performOnCollections('profile_image', 'gallery')
            ->nonQueued();

        // Display conversion (600x800 contain for profile page)
        $this->addMediaConversion('display')
            ->fit(\Spatie\Image\Enums\Fit::Contain, 600, 800)
            ->sharpen(10)
            ->optimize()
            ->performOnCollections('profile_image', 'gallery')
            ->nonQueued();
    }

    /**
     * Get profile image URL (accessor with backward compatibility)
     */
    public function getProfileImageUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('profile_image') ?: $this->profile_image;
    }

    /**
     * Get avatar URL (small profile image)
     */
    public function getAvatarUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('profile_image', 'avatar') ?: null;
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
            'display' => $media->getUrl('display'),
            'alt' => $media->getCustomProperty('alt_text'),
            'caption' => $media->getCustomProperty('caption'),
        ])->toArray();
    }
}
