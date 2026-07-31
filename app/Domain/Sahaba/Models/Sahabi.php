<?php

declare(strict_types=1);

namespace App\Domain\Sahaba\Models;

use App\Domain\Genealogy\Enums\PersonType;
use App\Domain\Genealogy\Models\Relationship;
use App\Domain\Sahaba\Enums\CompanionStatus;
use App\Domain\Shared\Traits\HasInteractions;
use App\Domain\Shared\Traits\HasLocalizedContent;
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
 * @property string|null $external_id
 * @property string|null $source_url
 * @property string $slug
 * @property string $gender
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
 * @property int $reported_hadith_count
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
     * (KI-17). Never surface the SQL error to a caller.
     */
    public function getFirstMediaUrl(string $collectionName = 'default', string $conversionName = ''): string
    {
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
        'external_id',
        'source_url',
        'slug',
        'gender',
        'person_type',
        'is_sahabi',
        'generation',
        'is_verified',
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
        'tree_display_order',
        'hide_from_tree',
        'tribe',
        'clan',
        'conversion_date_hijri',
        'conversion_order',
        'roles',
        'reported_hadith_count',
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
        'reported_hadith_count' => 'integer',
        'person_type' => PersonType::class,
        'is_sahabi' => 'boolean',
        'generation' => 'integer',
        'is_verified' => 'boolean',
        'tree_display_order' => 'integer',
        'hide_from_tree' => 'boolean',
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
        return [
            'name',
            'full_name',
            'kunya',
            'laqab',
            'title',
            'biography',
            'birth_place',
            'death_place',
            'burial_place',
            'occupation',
            'notes',
            'virtues',
            'famous_narrations',
            'martyrdom',
        ];
    }

    /**
     * Get the foreign key name for translations.
     */
    public function getTranslationForeignKey(): string
    {
        return 'sahabi_id';
    }

    /**
     * Get the canonical father record.
     */
    public function father(): BelongsTo
    {
        return $this->belongsTo(self::class, 'father_id');
    }

    /**
     * Get the canonical mother record.
     */
    public function mother(): BelongsTo
    {
        return $this->belongsTo(self::class, 'mother_id');
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
     * Get children where this record is the father.
     */
    public function childrenAsFather(): HasMany
    {
        return $this->hasMany(self::class, 'father_id');
    }

    /**
     * Get children where this record is the mother.
     */
    public function childrenAsMother(): HasMany
    {
        return $this->hasMany(self::class, 'mother_id');
    }

    public function relationships(): HasMany
    {
        return $this->hasMany(Relationship::class, 'person_id');
    }

    public function relatedRelationships(): HasMany
    {
        return $this->hasMany(Relationship::class, 'related_person_id');
    }

    /**
     * Historical-rank categories held by this Sahabi (badr, ansar, muhajir, ...).
     * A Sahabi can hold several at once — e.g. a Muhajir who fought at Badr —
     * which a single-value `status` column could never represent.
     */
    public function categories(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(SahabiCategory::class, 'sahabi_category', 'sahabi_id', 'category_id')
            ->withTimestamps();
    }

    public function isProphet(): bool
    {
        return $this->person_type === PersonType::PROPHET;
    }

    public function isCompanion(): bool
    {
        return $this->is_sahabi;
    }

    public function getFullName(string $locale = 'ar'): string
    {
        return $this->getTranslation('full_name', $locale)
            ?: $this->getTranslation('name', $locale)
            ?: '';
    }

    public function getAgeAtDeath(): ?int
    {
        if ($this->age_at_death !== null) {
            return (int) $this->age_at_death;
        }

        if ($this->birth_date_gregorian && $this->death_date_gregorian) {
            return $this->birth_date_gregorian->diffInYears($this->death_date_gregorian);
        }

        return null;
    }

    public function scopeOnlySahaba($query)
    {
        return $query->where('is_sahabi', true);
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
     * Scope: Filter by historical-rank category (badr, ansar, muhajir, ...).
     * Replaces the old single-value `status` filter — a Sahabi can match
     * more than one category, so this is an existence check, not equality.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInCategory($query, CompanionStatus|string $category)
    {
        $key = $category instanceof CompanionStatus ? $category->value : $category;

        return $query->whereHas('categories', fn ($q) => $q->where('key', $key));
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
     * Scope: Top narrators by the source's reported hadith count.
     * This is an import-time claim (see `reported_hadith_count` docs), not a
     * live count from this database's own hadith graph — the rebuilt Hadith
     * schema stores no transmission chains, so no live count exists.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeTopNarrators($query, int $limit = 10)
    {
        return $query->where('reported_hadith_count', '>', 0)
            ->orderByDesc('reported_hadith_count')
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
     * Get profile image URL
     */
    public function getProfileImageUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('profile_image') ?: null;
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
