<?php

declare(strict_types=1);

namespace App\Domain\Quran\Models;

use Database\Factories\Quran\ChapterInfoFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A chapter introduction from one chapter-info source (e.g. Maududi, Ibn Ashur).
 *
 * @property int $id
 * @property int $resource_id
 * @property int $chapter_id
 * @property int|null $qf_id
 * @property string|null $language_iso
 * @property string $text HTML
 * @property string|null $short_text
 * @property string|null $source
 * @property bool $is_locked
 */
class ChapterInfo extends Model
{
    /** @use HasFactory<ChapterInfoFactory> */
    use HasFactory;

    protected $table = 'quran_chapter_infos';

    protected $fillable = ['resource_id', 'chapter_id', 'qf_id', 'language_iso', 'text', 'short_text', 'source', 'is_locked'];

    protected $casts = [
        'resource_id' => 'integer',
        'chapter_id' => 'integer',
        'qf_id' => 'integer',
        'is_locked' => 'boolean',
    ];

    protected static function newFactory(): ChapterInfoFactory
    {
        return ChapterInfoFactory::new();
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(QuranResource::class, 'resource_id');
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class, 'chapter_id');
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
