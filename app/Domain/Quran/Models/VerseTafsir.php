<?php

declare(strict_types=1);

namespace App\Domain\Quran\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * VerseTafsir Model (Quran Verse Tafsir/Interpretation per language)
 *
 * @property int $id
 * @property int $verse_id
 * @property string $verse_key
 * @property string $lang
 * @property string $text
 * @property bool $active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class VerseTafsir extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'quran_verse_tafsirs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'verse_id',
        'verse_key',
        'lang',
        'text',
        'active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'verse_id' => 'integer',
        'active' => 'boolean',
    ];

    /**
     * Get the verse this tafsir belongs to
     */
    public function verse(): BelongsTo
    {
        return $this->belongsTo(Verse::class, 'verse_id');
    }

    /**
     * Scope: Filter by language
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByLang($query, string $lang)
    {
        return $query->where('lang', $lang);
    }

    /**
     * Scope: Active only
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope: Filter by verse key
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByVerseKey($query, string $verseKey)
    {
        return $query->where('verse_key', $verseKey);
    }

    /**
     * Scope: Arabic tafsirs
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeArabic($query)
    {
        return $query->where('lang', 'ar');
    }

    /**
     * Scope: English tafsirs
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEnglish($query)
    {
        return $query->where('lang', 'en');
    }

    /**
     * Scope: Search in tafsir text
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where('text', 'LIKE', "%{$term}%");
    }
}
