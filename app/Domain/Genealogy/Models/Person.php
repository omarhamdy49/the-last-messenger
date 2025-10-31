<?php

declare(strict_types=1);

namespace App\Domain\Genealogy\Models;

use App\Domain\Genealogy\Enums\PersonType;
use App\Domain\Sahaba\Models\Sahabi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

/**
 * Person Model
 * Represents individuals in the genealogical tree
 */
class Person extends Model
{
    use HasTranslations, SoftDeletes;

    protected $table = 'people';

    protected $fillable = [
        'name',
        'slug',
        'title',
        'biography',
        'person_type',
        'gender',
        'birth_date_hijri',
        'birth_date_gregorian',
        'birth_place',
        'death_date_hijri',
        'death_date_gregorian',
        'death_place',
        'burial_place',
        'father_id',
        'mother_id',
        'sahaba_id',
        'tribe',
        'occupation',
        'is_verified',
        'notes',
        'is_featured',
        'kunya',
        'laqab',
        'full_name',
        'profile_image',
        'gallery',
        'generation',
    ];

    public $translatable = [
        'name',
        'title',
        'biography',
        'birth_place',
        'death_place',
        'burial_place',
        'occupation',
        'notes',
    ];

    protected $casts = [
        'person_type' => PersonType::class,
        'birth_date_hijri' => 'date',
        'birth_date_gregorian' => 'date',
        'death_date_hijri' => 'date',
        'death_date_gregorian' => 'date',
        'is_verified' => 'boolean',
        'name' => 'array',
        'title' => 'array',
        'biography' => 'array',
        'birth_place' => 'array',
        'death_place' => 'array',
        'burial_place' => 'array',
        'occupation' => 'array',
        'notes' => 'array',
    ];

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
        return $this->belongsTo(Sahabi::class, 'sahaba_id');
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
}
