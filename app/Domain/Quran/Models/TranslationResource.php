<?php

declare(strict_types=1);

namespace App\Domain\Quran\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Translation Resource — a single translation work (Saheeh International,
 * Pickthall, Hamidullah, ...). A verse can hold one translation per resource,
 * enabling quran.com-style multi-edition translations per language. The Arabic
 * Quran text itself is modelled as the `ar-uthmani` resource.
 * quran_verse_translations.resource_id points here.
 *
 * @property int $id
 * @property string $slug
 * @property array<string, string> $name
 * @property string|null $author
 * @property string $language
 * @property string $direction
 * @property string|null $source
 * @property bool $is_default
 * @property bool $is_active
 * @property int $display_order
 */
class TranslationResource extends Model
{
    use HasFactory;

    protected $table = 'quran_translation_resources';

    /**
     * @var array<string>
     */
    protected $fillable = [
        'slug',
        'name',
        'author',
        'language',
        'direction',
        'source',
        'is_default',
        'is_active',
        'display_order',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'name' => 'array',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    /**
     * All verse translations that belong to this resource.
     */
    public function translations(): HasMany
    {
        return $this->hasMany(VerseTranslation::class, 'resource_id');
    }

    /**
     * Localized display name, falling back across the locale, Arabic, English, then slug.
     */
    public function getName(string $locale = 'ar'): string
    {
        return $this->name[$locale] ?? $this->name['ar'] ?? $this->name['en'] ?? $this->slug;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    public function scopeForLanguage(Builder $query, string $language): Builder
    {
        return $query->where('language', $language);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order')->orderBy('id');
    }
}
