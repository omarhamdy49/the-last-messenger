<?php

declare(strict_types=1);

namespace App\Domain\Quran\Models;

use Database\Factories\Quran\TafsirFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One verse's commentary in one tafsir resource.
 *
 * @property int $id
 * @property int $resource_id
 * @property int $verse_id
 * @property int|null $qf_id
 * @property string $text HTML
 * @property bool $is_locked
 */
class Tafsir extends Model
{
    /** @use HasFactory<TafsirFactory> */
    use HasFactory;

    protected $table = 'quran_tafsirs';

    protected $fillable = ['resource_id', 'verse_id', 'qf_id', 'text', 'is_locked'];

    protected $casts = [
        'resource_id' => 'integer',
        'verse_id' => 'integer',
        'qf_id' => 'integer',
        'is_locked' => 'boolean',
    ];

    protected static function newFactory(): TafsirFactory
    {
        return TafsirFactory::new();
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(QuranResource::class, 'resource_id');
    }

    public function verse(): BelongsTo
    {
        return $this->belongsTo(Verse::class, 'verse_id');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeUnlocked(Builder $query): Builder
    {
        return $query->where('is_locked', false);
    }
}
