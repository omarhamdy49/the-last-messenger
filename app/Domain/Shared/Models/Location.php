<?php

declare(strict_types=1);

namespace App\Domain\Shared\Models;

use App\Domain\Shared\Enums\LocationType;
use App\Domain\Shared\Exceptions\BusinessRuleViolationException;
use App\Domain\Shared\Helpers\VersionedCacheKey;
use App\Domain\Shared\Services\LocationUsageRegistry;
use App\Domain\Shared\Traits\HasLocalizedContent;
use Carbon\Carbon;
use Database\Factories\LocationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A canonical place in the encyclopedia's shared vocabulary.
 *
 * ------------------------------------------------------------------------
 * THIS MODEL MUST NOT REFERENCE ANY DOMAIN MODULE.
 *
 * `Shared` is a leaf (constitution IV): it imports no domain module, and that
 * is "the invariant that actually protects module isolation". So there is
 * deliberately NO `hasMany(SeerahEvent)`, `hasMany(Muslim)` or
 * `hasMany(Surah)` here, even though all three tables carry a foreign key to
 * this one. Consumers point inward; this model never points back.
 *
 * Counting what references a location goes through
 * {@see LocationUsageRegistry}, which holds
 * [table, column] pairs registered by each consuming module's provider.
 * See specs/003-locations-module/research.md R-06.
 * ------------------------------------------------------------------------
 *
 * @property int $id
 * @property string $slug
 * @property LocationType $type
 * @property int|null $parent_id
 * @property int $depth Nesting depth (0 = root), derived from parent_id on save
 * @property string|null $latitude
 * @property string|null $longitude
 * @property array<int, string>|null $aliases
 * @property bool $is_active
 * @property int $display_order
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection<int, LocationTranslation> $translations
 * @property-read Location|null $parent
 * @property-read Collection<int, Location> $children
 */
class Location extends Model
{
    use HasFactory, HasLocalizedContent;

    /**
     * Hard ceiling on the parent walk. The seeded tree is 4 levels deep
     * (الجزيرة العربية → الحجاز → مكة المكرمة → المسجد الحرام); this exists so a
     * pre-existing bad row cannot hang a request while the cycle check runs.
     */
    private const MAX_DEPTH = 16;

    /** @var array<int, string> */
    protected $fillable = [
        'slug',
        'type',
        'parent_id',
        'latitude',
        'longitude',
        'aliases',
        'is_active',
        'display_order',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => LocationType::class,
            'aliases' => 'array',
            'is_active' => 'boolean',
            'depth' => 'integer',
            'display_order' => 'integer',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (self $location): void {
            $location->guardAgainstCycle();
            $location->depth = static::computeDepth($location->parent_id);
        });

        // Invalidate the cached vocabulary on ANY write, not only writes that
        // went through LocationRepository. The seeder, the factory and tinker
        // all bypass the repository, and a stale vocabulary silently resolves
        // content against places that no longer exist — which is worse than a
        // cache miss because it looks like it worked.
        //
        // Versioned key, never Cache::flush(): flushing wipes other modules'
        // caches and the rate limiter.
        $invalidate = static fn (): null => tap(null, static fn () => VersionedCacheKey::bump('location'));

        static::saved($invalidate);
        static::deleted($invalidate);
    }

    // ---------------------------------------------------------------- hierarchy

    /**
     * Reject a parent that would make the tree cyclic.
     *
     * Two cases: the row is its own parent, or the proposed parent is one of
     * this row's own descendants. The second is the one that matters — it is
     * invisible from the row being edited, and it is what makes `depth`
     * uncomputable and every ancestor walk non-terminating.
     *
     * @throws BusinessRuleViolationException
     */
    public function guardAgainstCycle(): void
    {
        if ($this->parent_id === null) {
            return;
        }

        if ($this->exists && $this->parent_id === $this->getKey()) {
            throw BusinessRuleViolationException::forRule(
                'location.self_parent',
                __('locations.errors.self_parent'),
                ['location_id' => $this->getKey()]
            );
        }

        if (! $this->exists) {
            return;
        }

        // Walk up from the proposed parent. Meeting ourselves means the parent
        // sits somewhere below us, which would close the loop.
        $currentId = $this->parent_id;
        $hops = 0;

        while ($currentId !== null && $hops < self::MAX_DEPTH) {
            if ($currentId === $this->getKey()) {
                throw BusinessRuleViolationException::forRule(
                    'location.cycle',
                    __('locations.errors.cycle'),
                    ['location_id' => $this->getKey(), 'parent_id' => $this->parent_id]
                );
            }

            $currentId = static::query()
                ->whereKey($currentId)
                ->value('parent_id');

            $hops++;
        }

        if ($hops >= self::MAX_DEPTH) {
            throw BusinessRuleViolationException::forRule(
                'location.cycle',
                __('locations.errors.cycle'),
                ['location_id' => $this->getKey(), 'reason' => 'max_depth_exceeded']
            );
        }
    }

    /**
     * Walk up the parent chain to compute depth. 0 for a root.
     *
     * Bounded by MAX_DEPTH so a pre-existing cyclic row cannot hang the request
     * even though guardAgainstCycle() runs first.
     */
    protected static function computeDepth(?int $parentId): int
    {
        $depth = 0;
        $currentId = $parentId;

        while ($currentId !== null && $depth < self::MAX_DEPTH) {
            $depth++;
            $currentId = static::query()->whereKey($currentId)->value('parent_id');
        }

        return $depth;
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('display_order');
    }

    // ------------------------------------------------------------- translations

    public function getTranslationModelClass(): string
    {
        return LocationTranslation::class;
    }

    /** @return array<int, string> */
    public function getTranslatableFields(): array
    {
        return ['name', 'description'];
    }

    // ------------------------------------------------------------------- reads

    /**
     * The place name in the given locale, falling back to Arabic.
     *
     * Arabic is both the default and the fallback locale, so a place with only
     * an Arabic name renders in Arabic everywhere rather than rendering blank.
     */
    public function name(?string $locale = null): ?string
    {
        return $this->getTranslation('name', $locale)
            ?? $this->getTranslation('name', 'ar');
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /**
     * Alternate spellings, always an array.
     *
     * @return array<int, string>
     */
    public function aliasList(): array
    {
        return array_values(array_filter(
            $this->aliases ?? [],
            static fn ($alias): bool => is_string($alias) && trim($alias) !== ''
        ));
    }

    // ------------------------------------------------------------------ scopes

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeOfType(Builder $query, LocationType|string $type): Builder
    {
        return $query->where('type', $type instanceof LocationType ? $type->value : $type);
    }

    public function scopeWithCoordinates(Builder $query): Builder
    {
        return $query->whereNotNull('latitude')->whereNotNull('longitude');
    }

    protected static function newFactory(): LocationFactory
    {
        return LocationFactory::new();
    }
}
