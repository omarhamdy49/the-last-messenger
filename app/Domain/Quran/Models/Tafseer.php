<?php

declare(strict_types=1);

namespace App\Domain\Quran\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tafseer Model (Quran Interpretation)
 *
 * @property int $id
 * @property int $verse_id
 * @property string $tafseer_name
 * @property string $language
 * @property string $text
 * @property string|null $footnotes
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Tafseer extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'quran_tafseer';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'verse_id',
        'tafseer_name',
        'language',
        'text',
        'footnotes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'verse_id' => 'integer',
    ];

    /**
     * Get the verse this tafseer belongs to
     *
     * @return BelongsTo
     */
    public function verse(): BelongsTo
    {
        return $this->belongsTo(Verse::class, 'verse_id');
    }

    /**
     * Scope: Filter by tafseer name
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $name
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByTafseerName($query, string $name)
    {
        return $query->where('tafseer_name', $name);
    }

    /**
     * Scope: Filter by language
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $language
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByLanguage($query, string $language)
    {
        return $query->where('language', $language);
    }
}

