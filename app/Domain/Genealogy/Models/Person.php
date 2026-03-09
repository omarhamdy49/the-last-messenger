<?php

declare(strict_types=1);

namespace App\Domain\Genealogy\Models;

use App\Domain\Genealogy\Enums\PersonType;
use App\Domain\Shared\Traits\HasLocalizedContent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Person Model
 * Represents individuals in the genealogical tree
 */
class Person extends Model implements HasMedia
{
    use HasLocalizedContent, InteractsWithMedia, SoftDeletes;

    protected $table = 'people';

    protected $fillable = [
        'slug',
        'person_type',
        'gender',
        'birth_date_hijri',
        'birth_date_gregorian',
        'birth_date_hijri',
        'birth_date_gregorian',
        'father_id',
        'mother_id',
        'sahaba_id',
        'tribe',
        'is_verified',
        'is_featured',
        'kunya',
        'laqab',
        'full_name',
        'profile_image',
        'gallery',
        'generation',
    ];

    protected $casts = [
        'person_type' => PersonType::class,
        'birth_date_hijri' => 'date',
        'birth_date_gregorian' => 'date',
        'death_date_hijri' => 'date',
        'death_date_gregorian' => 'date',
        'is_verified' => 'boolean',
    ];

    /**
     * Get the FQCN of the translation model.
     */
    public function getTranslationModelClass(): string
    {
        return PersonTranslation::class;
    }

    /**
     * Get the list of translatable field names.
     */
    public function getTranslatableFields(): array
    {
        return ['name', 'title', 'biography', 'birth_place', 'death_place', 'burial_place', 'occupation', 'notes'];
    }

    /**
     * Get the father
     */
    public function father(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'father_id');
    }

    /**
     * Get the mother
     */
    public function mother(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'mother_id');
    }

    /**
     * Get the related Sahabi if person is a companion
     */
    public function sahabi(): BelongsTo
    {
        return $this->belongsTo('App\Domain\Sahaba\Models\Sahabi', 'sahaba_id');
    }

    /**
     * Get children (where this person is the father)
     */
    public function childrenAsFather(): HasMany
    {
        return $this->hasMany(Person::class, 'father_id');
    }

    /**
     * Get children (where this person is the mother)
     */
    public function childrenAsMother(): HasMany
    {
        return $this->hasMany(Person::class, 'mother_id');
    }

    /**
     * Get all relationships where this person is the primary person
     */
    public function relationships(): HasMany
    {
        return $this->hasMany(Relationship::class, 'person_id');
    }

    /**
     * Get all relationships where this person is the related person
     */
    public function relatedRelationships(): HasMany
    {
        return $this->hasMany(Relationship::class, 'related_person_id');
    }

    /**
     * Check if person is a prophet
     */
    public function isProphet(): bool
    {
        return $this->person_type === PersonType::PROPHET;
    }

    /**
     * Check if person is a companion
     */
    public function isCompanion(): bool
    {
        return in_array($this->person_type, [
            PersonType::COMPANION_MALE,
            PersonType::COMPANION_FEMALE,
        ]);
    }

    /**
     * Get full name in specified language
     */
    public function getFullName(string $locale = 'ar'): string
    {
        $name = $this->getTranslation('name', $locale) ?? '';
        $title = $this->getTranslation('title', $locale) ?? '';

        return trim($title.' '.$name);
    }

    /**
     * Get age at death (if both birth and death dates exist)
     */
    public function getAgeAtDeath(): ?int
    {
        if ($this->birth_date_gregorian && $this->death_date_gregorian) {
            return $this->birth_date_gregorian->diffInYears($this->death_date_gregorian);
        }

        return null;
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
            ->maxFilesize(10 * 1024 * 1024) // 10MB
            ->useDisk('public');
    }

    /**
     * Register media conversions
     */
    public function registerMediaConversions(?\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        // Avatar conversion (100x100 crop-center for genealogy tree nodes)
        $this->addMediaConversion('avatar')
            ->width(100)
            ->height(100)
            ->crop('crop-center')
            ->sharpen(10)
            ->optimize()
            ->performOnCollections('profile_image')
            ->nonQueued();

        // Display conversion (400x400 contain for profile page)
        $this->addMediaConversion('display')
            ->width(400)
            ->height(400)
            ->fit('contain')
            ->sharpen(10)
            ->optimize()
            ->performOnCollections('profile_image')
            ->nonQueued();
    }

    /**
     * Get profile image URL (accessor with backward compatibility)
     *
     * @return string|null
     */
    public function getProfileImageUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('profile_image') ?: $this->profile_image;
    }

    /**
     * Get avatar URL (small profile image for tree visualization)
     *
     * @return string|null
     */
    public function getAvatarUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('profile_image', 'avatar') ?: null;
    }
}
