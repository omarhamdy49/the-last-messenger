<?php

declare(strict_types=1);

namespace App\Domain\Quran\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
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
 * @property Carbon $created_at
 * @property Carbon $updated_at
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
        'resource_id',
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
        'resource_id' => 'integer',
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
     * Get the tafsir resource (source work) this tafsir belongs to.
     */
    public function resource(): BelongsTo
    {
        return $this->belongsTo(TafsirResource::class, 'resource_id');
    }

    /**
     * Scope: filter by tafsir resource (source).
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeByResource($query, int $resourceId)
    {
        return $query->where('resource_id', $resourceId);
    }

    /**
     * Scope: Filter by language
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeByLang($query, string $lang)
    {
        return $query->where('lang', $lang);
    }

    /**
     * Scope: Active only
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope: Filter by verse key
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeByVerseKey($query, string $verseKey)
    {
        return $query->where('verse_key', $verseKey);
    }

    /**
     * Scope: Arabic tafsirs
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeArabic($query)
    {
        return $query->where('lang', 'ar');
    }

    /**
     * Scope: English tafsirs
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeEnglish($query)
    {
        return $query->where('lang', 'en');
    }

    // scopeSearch() was removed: it had zero callers anywhere in app/ or tests/
    // (every ->search() call in the codebase is on a repository or service, never on
    // this model's builder), and it was LIKE '%...%' over a longText column with no index behind it.
    // Searching this data goes through the repository, which uses
    // FullTextSearchHelper / a constrained whereHas instead.
}
