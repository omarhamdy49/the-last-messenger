<?php

declare(strict_types=1);

namespace App\Domain\Quran\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Mushaf Word
 *
 * One word — or an ayah-end marker — positioned on a printed-mushaf line/page.
 * Imported from the quran.com layout data; powers line-for-line rendering on the
 * public reader. `text_uthmani` is real Unicode (rendered in the site's own
 * Uthmani font), not a glyph-coded font payload.
 *
 * @property int $id
 * @property int $verse_id
 * @property int $surah_number
 * @property int $verse_number
 * @property int $position
 * @property string $location
 * @property string $text_uthmani
 * @property string $char_type
 * @property int $line_number
 * @property int $page_number
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Word extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'quran_words';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'verse_id',
        'surah_number',
        'verse_number',
        'position',
        'location',
        'text_uthmani',
        'char_type',
        'line_number',
        'page_number',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'verse_id' => 'integer',
        'surah_number' => 'integer',
        'verse_number' => 'integer',
        'position' => 'integer',
        'line_number' => 'integer',
        'page_number' => 'integer',
    ];

    /**
     * Get the verse this word belongs to.
     */
    public function verse(): BelongsTo
    {
        return $this->belongsTo(Verse::class, 'verse_id');
    }

    /**
     * Whether this token is the ayah-end marker (rendered as our rosette, with
     * the verse number, rather than as text).
     */
    public function isAyahEnd(): bool
    {
        return $this->char_type === 'end';
    }

    /**
     * Scope: a single surah's words (denormalised number, no join).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBySurahNumber($query, int $surahNumber)
    {
        return $query->where('surah_number', $surahNumber);
    }

    /**
     * Scope: a single mushaf page's words.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByPage($query, int $page)
    {
        return $query->where('page_number', $page);
    }
}
