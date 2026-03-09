<?php

declare(strict_types=1);

namespace App\Domain\Quran\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Surah Model (Quran Chapter)
 *
 * @property int $id
 * @property string $revelation_place
 * @property int $revelation_order
 * @property bool $bismillah_pre
 * @property string $name_ar
 * @property string $name_en
 * @property string|null $transliteration
 * @property int $verses_count
 * @property array|null $pages
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Surah extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'quran_surahs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'revelation_place',
        'revelation_order',
        'bismillah_pre',
        'name_ar',
        'name_en',
        'transliteration',
        'verses_count',
        'pages',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'revelation_order' => 'integer',
        'bismillah_pre' => 'boolean',
        'verses_count' => 'integer',
        'pages' => 'array',
    ];

    /**
     * Get all verses in this surah
     */
    public function verses(): HasMany
    {
        return $this->hasMany(Verse::class, 'surah_id')->orderBy('verse_number');
    }

    /**
     * Get surah info translations
     */
    public function info(): HasMany
    {
        return $this->hasMany(SurahInfo::class, 'surah_id');
    }

    /**
     * Get surah info for specific language
     */
    public function getInfo(string $lang = 'ar'): ?SurahInfo
    {
        return $this->info()->where('lang', $lang)->where('active', true)->first();
    }

    /**
     * Check if surah is Makkan
     */
    public function isMakkan(): bool
    {
        return strtolower($this->revelation_place) === 'makkah';
    }

    /**
     * Check if surah is Madinan
     */
    public function isMadinan(): bool
    {
        return strtolower($this->revelation_place) === 'madinah';
    }

    /**
     * Get surah number (1-114) as an alias for revelation_order.
     * Accessor so SurahResource and other callers can use $surah->number.
     */
    public function getNumberAttribute(): int
    {
        return $this->revelation_order;
    }

    /**
     * Get display name
     */
    public function getDisplayName(string $language = 'ar'): string
    {
        return $language === 'ar' ? $this->name_ar : $this->name_en;
    }

    /**
     * Scope: Filter by revelation place
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByRevelationPlace($query, string $place)
    {
        return $query->where('revelation_place', $place);
    }

    /**
     * Scope: Order by revelation order
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByRevelationOrder($query)
    {
        return $query->orderBy('revelation_order');
    }

    /**
     * Scope: Makkan surahs
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeMakkan($query)
    {
        return $query->where('revelation_place', 'makkah');
    }

    /**
     * Scope: Madinan surahs
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeMadinan($query)
    {
        return $query->where('revelation_place', 'madinah');
    }
}
