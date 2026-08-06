<?php

declare(strict_types=1);

namespace App\Domain\People\Models;

use App\Domain\People\Enums\Gender;
use App\Domain\Shared\Contracts\PersonReference;
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
    use HasFactory, HasTranslations, SoftDeletes;

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
        'gender',
        'birth_year_hijri',
        'death_year_hijri',
        'generation',
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
        return ($locale ?? app()->getLocale()) === 'ar'
            ? ($this->name_ar ?? $this->name_en ?? '')
            : ($this->name_en ?? $this->name_ar ?? '');
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
}
