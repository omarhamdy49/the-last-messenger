<?php

declare(strict_types=1);

namespace App\Domain\Quran\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SurahInfo Model (Surah descriptions and content per language)
 *
 * @property int $id
 * @property int $surah_id
 * @property string $lang
 * @property string|null $desc
 * @property string|null $content
 * @property string|null $source
 * @property bool $active
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class SurahInfo extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'quran_surah_info';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'surah_id',
        'lang',
        'desc',
        'content',
        'source',
        'active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'surah_id' => 'integer',
        'active' => 'boolean',
    ];

    /**
     * Get the surah this info belongs to
     */
    public function surah(): BelongsTo
    {
        return $this->belongsTo(Surah::class, 'surah_id');
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
     * Scope: Filter by surah
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeBySurah($query, int $surahId)
    {
        return $query->where('surah_id', $surahId);
    }
}
