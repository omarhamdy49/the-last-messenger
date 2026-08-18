<?php

declare(strict_types=1);

namespace App\Domain\People\Models;

use App\Domain\People\Enums\Gender;
use App\Domain\People\ValueObjects\MuslimProfile;
use App\Domain\Shared\Contracts\PersonReference;
use App\Domain\Shared\Models\Location;
use App\Domain\Shared\Support\ArabicNormalizer;
use Database\Factories\MuslimFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

/**
 * The application-wide person identity.
 *
 * One row per historical figure regardless of how many capacities they appear
 * in — a companion who narrated hadith and graded them is ONE row with three
 * role assignments. This is the whole point of the rebuild: previously the same
 * person could exist as a Sahabi, a narrator, a scholar, and a genealogy node
 * with nothing connecting them.
 *
 * No Spatie HasMedia: `thumb` is a scalar path (Owner Decision 4).
 *
 * @property int $id
 * @property string $slug
 * @property string|null $thumb
 * @property string|null $name_ar
 * @property string|null $name_en
 * @property int|null $birth_year_hijri signed — negative is pre-Hijra
 * @property int|null $death_year_hijri
 * @property int|null $father_id
 * @property int|null $mother_id
 * @property bool $hide_from_tree
 * @property bool $is_published
 */
class Muslim extends Model
{
    use HasFactory, SoftDeletes;
    use HasTranslations {
        // Aliased because getTranslation() is overridden below to bridge the
        // existing Blade components onto the new column shape. `parent::` does
        // not reach a trait method, so the original is called through this.
        getTranslation as protected spatieGetTranslation;
    }

    protected $table = 'muslims';

    protected $fillable = [
        'slug',
        'thumb',
        'name_ar',
        'name_en',
        'kunya',
        'bio',
        'content',
        'birth_place',
        'birth_place_id',
        'gender',
        'birth_year_hijri',
        'death_year_hijri',
        'generation',
        'profile',
        'father_id',
        'mother_id',
        'tree_display_order',
        'hide_from_tree',
        'is_published',
    ];

    /** {ar,en} only — FR/TR/UR content is retired (Decision D). */
    public array $translatable = ['kunya', 'bio', 'content', 'birth_place'];

    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            // Cast to the value object, so `$muslim->profile` is never a raw
            // array and the field registry is the only way in or out.
            'profile' => MuslimProfile::class,
            'birth_year_hijri' => 'integer',
            'death_year_hijri' => 'integer',
            'tree_display_order' => 'integer',
            'hide_from_tree' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    /**
     * Keep the normalised search columns in step with the name fields on EVERY
     * write, not only at import. Import-only population is the failure mode
     * FR-035 exists to prevent: an editor corrects a name, and the person
     * silently stops being findable under it.
     */
    protected static function booted(): void
    {
        static::saving(function (self $muslim): void {
            if (! $muslim->isDirty(['name_ar', 'name_en', 'kunya'])) {
                return;
            }

            $normalizer = app(ArabicNormalizer::class);

            $muslim->search_name_ar = $normalizer->normalizeParts([
                $muslim->name_ar,
                $muslim->getTranslation('kunya', 'ar', false),
            ]);

            $muslim->search_name_en = $normalizer->normalizeParts([
                $muslim->name_en,
                $muslim->getTranslation('kunya', 'en', false),
            ]);
        });
    }

    // ---------------------------------------------------------------------
    // Relations
    // ---------------------------------------------------------------------

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(IslamicRole::class, 'muslims_roles', 'muslim_id', 'role_id')
            ->withPivot(['is_featured', 'sort_order'])
            ->withTimestamps();
    }

    public function father(): BelongsTo
    {
        return $this->belongsTo(self::class, 'father_id');
    }

    /**
     * The canonical birthplace.
     *
     * The `birth_place` JSON column is kept alongside this until nothing reads
     * it. It is English-only across all 6,677 populated rows and carries the
     * spelling drift this reference resolves — Medinah/Medina/Madinah are one
     * place stored three ways.
     */
    public function birthPlace(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'birth_place_id');
    }

    public function mother(): BelongsTo
    {
        return $this->belongsTo(self::class, 'mother_id');
    }

    /** Children through the father link. */
    public function childrenByFather(): HasMany
    {
        return $this->hasMany(self::class, 'father_id');
    }

    /** Children through the mother link. */
    public function childrenByMother(): HasMany
    {
        return $this->hasMany(self::class, 'mother_id');
    }

    /** Relationships stored with this person on the near side. */
    public function relationships(): HasMany
    {
        return $this->hasMany(MuslimRelationship::class, 'muslim_id');
    }

    /** Relationships stored with this person on the far side. */
    public function inverseRelationships(): HasMany
    {
        return $this->hasMany(MuslimRelationship::class, 'related_muslim_id');
    }

    /** External catalogue records that establish this person's provenance. */
    public function sources(): HasMany
    {
        return $this->hasMany(MuslimSource::class, 'muslim_id');
    }

    // ---------------------------------------------------------------------
    // Scopes
    // ---------------------------------------------------------------------

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeWithRole(Builder $query, string $roleSlug): Builder
    {
        return $query->whereHas('roles', fn (Builder $q) => $q->where('slug', $roleSlug));
    }

    // ---------------------------------------------------------------------
    // Presentation
    // ---------------------------------------------------------------------

    /**
     * Falls back to the other language rather than to an empty string: source
     * records legitimately carry only one name, and a missing Arabic name must
     * not render blank.
     */
    public function name(?string $locale = null): string
    {
        return self::preferredName($this->name_ar, $this->name_en, $locale);
    }

    /**
     * The same fallback applied to a raw name pair.
     *
     * Exists because the genealogy tree is built by a recursive CTE that
     * returns plain rows, never models — without this the fallback would have
     * to be written a second time at the render site and could drift.
     */
    public static function preferredName(?string $nameAr, ?string $nameEn, ?string $locale = null): string
    {
        return ($locale ?? app()->getLocale()) === 'ar'
            ? ($nameAr ?? $nameEn ?? '')
            : ($nameEn ?? $nameAr ?? '');
    }

    /** How other domain modules receive this person (constitution IV). */
    public function toPersonReference(): PersonReference
    {
        return new PersonReference(
            id: $this->id,
            slug: $this->slug,
            nameAr: $this->name_ar,
            nameEn: $this->name_en,
        );
    }

    /** Domain models live outside app/Models, so the factory is named explicitly. */
    protected static function newFactory(): MuslimFactory
    {
        return MuslimFactory::new();
    }

    /**
     * Compatibility shim for the existing Blade components.
     *
     * The public views were written against the old Sahabi model, which stored
     * `name` and `laqab` in a translations table. On `muslims` the name is two
     * flat columns (`name_ar` / `name_en`) because MySQL FULLTEXT cannot index
     * inside a JSON column, and `laqab` was not carried forward.
     *
     * Rather than rewrite the designed views, this maps the old translation
     * keys onto the new shape. Keys that no longer have a source return an
     * empty string, which every view already treats as "omit the section".
     */
    public function getTranslation(string $key, string $locale, bool $useFallbackLocale = true): mixed
    {
        if ($key === 'name') {
            return $locale === 'ar'
                ? ($this->name_ar ?? ($useFallbackLocale ? $this->name_en : null) ?? '')
                : ($this->name_en ?? ($useFallbackLocale ? $this->name_ar : null) ?? '');
        }

        // Retired with the Sahaba module; no source column exists.
        if (in_array($key, ['laqab', 'full_name', 'virtues', 'famous_narrations', 'martyrdom'], true)) {
            return '';
        }

        if ($key === 'biography') {
            $key = 'bio';
        }

        return $this->spatieGetTranslation($key, $locale, $useFallbackLocale);
    }

    /**
     * Media shim. `Muslim` does not implement Spatie HasMedia — Owner Decision
     * 4 replaced the companion gallery with a single scalar `thumb`. The
     * existing views still call getFirstMediaUrl(), so it resolves to that
     * path (or an empty string, which every view treats as "use the initial").
     */
    public function getFirstMediaUrl(string $collectionName = 'default', string $conversionName = ''): string
    {
        return $this->thumb ?? '';
    }

    public function hasMedia(string $collectionName = 'default'): bool
    {
        return $this->thumb !== null && $this->thumb !== '';
    }

    /**
     * Property-style name access for the existing views. Without this Eloquent
     * treats `$muslim->name` as a relationship call, because `name()` is a
     * method. The accessor wins over relation resolution.
     */
    public function getNameAttribute(): string
    {
        return $this->name(app()->getLocale());
    }

    public function getFullNameAttribute(): string
    {
        return $this->name(app()->getLocale());
    }

    public function getBiographyAttribute(): ?string
    {
        return $this->getTranslation('bio', app()->getLocale()) ?: null;
    }

    public function getLaqabAttribute(): ?string
    {
        // Retired with the Sahaba module; no source column.
        return null;
    }

    /**
     * Hijri year accessors named for the old string columns the views read.
     * The new columns are signed integers — negative means pre-Hijra.
     */
    public function getBirthDateHijriAttribute(): ?string
    {
        return $this->birth_year_hijri === null ? null : (string) $this->birth_year_hijri;
    }

    public function getDeathDateHijriAttribute(): ?string
    {
        return $this->death_year_hijri === null ? null : (string) $this->death_year_hijri;
    }

    /**
     * Fields the old Sahabi table carried that `muslims` deliberately does not.
     * They resolve to null so the existing views simply omit those sections
     * instead of erroring (Owner Decisions 2, 4 and 6).
     */
    public function __get($key)
    {
        if (in_array($key, [
            'tribe', 'clan', 'death_place', 'burial_place', 'conversion_date_hijri',
            'conversion_order', 'age_at_death', 'is_verified', 'reported_hadith_count',
            'birth_date_gregorian', 'death_date_gregorian', 'person_type', 'status',
        ], true) && ! array_key_exists($key, $this->attributes)) {
            return null;
        }

        return parent::__get($key);
    }
}
