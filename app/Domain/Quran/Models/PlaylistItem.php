<?php

declare(strict_types=1);

namespace App\Domain\Quran\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One chapter in a playlist and the chapter reciter chosen to play it.
 *
 * @property int $id
 * @property int $playlist_id
 * @property int $chapter_id
 * @property int|null $resource_id a quran_resources row of kind chapter_reciter
 */
class PlaylistItem extends Model
{
    protected $table = 'quran_playlist_items';

    protected $fillable = ['playlist_id', 'chapter_id', 'resource_id'];

    protected $casts = [
        'playlist_id' => 'integer',
        'chapter_id' => 'integer',
        'resource_id' => 'integer',
    ];

    public function playlist(): BelongsTo
    {
        return $this->belongsTo(Playlist::class, 'playlist_id');
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class, 'chapter_id');
    }

    /**
     * The chapter reciter (a QuranResource), not the person.
     */
    public function reciter(): BelongsTo
    {
        return $this->belongsTo(QuranResource::class, 'resource_id');
    }
}
