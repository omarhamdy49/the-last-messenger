<?php

declare(strict_types=1);

namespace App\Domain\Quran\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tafsir Resource — a single tafsir work (Al-Muyassar, Ibn Kathir, ...).
 * A verse can have one tafsir per resource, enabling quran.com-style multi-source.
 *
 * @property int $id
 * @property string $slug
 * @property array<string, string> $name
 * @property string|null $author
 * @property string $language
 * @property string|null $source
 * @property bool $is_active
 * @property int $display_order
 */
class TafsirResource extends Model
{
    use HasFactory;

    protected $table = 'quran_tafsir_resources';

    /**
     * @var array<string>
     */
    protected $fillable = [
        'slug',
        'name',
        'author',
        'language',
        'source',
        'is_active',
        'display_order',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'name' => 'array',
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    /**
     * All verse tafsirs that belong to this resource.
     */
    public function tafsirs(): HasMany
    {
        return $this->hasMany(VerseTafsir::class, 'resource_id');
    }

    /**
     * Localized display name, falling back across Arabic, English, then slug.
     */
    public function getName(string $locale = 'ar'): string
    {
        return $this->name[$locale] ?? $this->name['ar'] ?? $this->name['en'] ?? $this->slug;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order')->orderBy('id');
    }
}
