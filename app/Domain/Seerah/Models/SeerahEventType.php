<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * SeerahEventType Model
 *
 * DB-driven taxonomy for Seerah timeline events, managed from the admin portal.
 *
 * The display name is a one-word label held per locale in the `locale` JSON
 * column rather than in a translations table — a deliberate exception for this
 * small lookup. Do not copy it to content tables.
 *
 * @property int $id
 * @property string $slug
 * @property array{icon?: string, color?: string, gradient?: string}|null $metadata
 * @property array<string, string> $locale
 * @property bool $is_active
 * @property-read string|null $icon
 * @property-read string|null $color
 * @property-read string|null $gradient
 */
class SeerahEventType extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'seerah_event_types';

    /**
     * @var array<string>
     */
    protected $fillable = [
        'slug',
        'metadata',
        'locale',
        'is_active',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
        'locale' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Localized display name. Mirrors HasLocalizedContent's signature so callers
     * read a type's name the same way they read any other translated field.
     */
    public function getTranslation(string $field, ?string $locale = null, bool $useFallbackLocale = true): ?string
    {
        if ($field !== 'name') {
            return null;
        }

        $names = $this->locale ?? [];
        $locale ??= app()->getLocale();

        if (filled($names[$locale] ?? null)) {
            return $names[$locale];
        }

        return $useFallbackLocale && filled($names['ar'] ?? null) ? $names['ar'] : null;
    }

    public function name(?string $locale = null): string
    {
        return $this->getTranslation('name', $locale) ?? $this->slug;
    }

    /**
     * Events that belong to this type.
     */
    public function events(): HasMany
    {
        return $this->hasMany(SeerahEvent::class, 'event_type');
    }

    protected function icon(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->metadata['icon'] ?? null);
    }

    protected function color(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->metadata['color'] ?? null);
    }

    protected function gradient(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->metadata['gradient'] ?? null);
    }

    /**
     * Scope: active types only.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: ordered by slug. Screens that list types by name sort the (small,
     * cached) collection in PHP, since the name lives inside JSON.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('slug');
    }
}
