<?php

declare(strict_types=1);

namespace App\Domain\Quran\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlaylistItem extends Model
{
    protected $table = 'quran_playlist_items';

    public $timestamps = false;

    protected $fillable = ['playlist_id', 'surah_id', 'reciter_id'];

    protected $casts = ['surah_id' => 'integer', 'reciter_id' => 'integer'];

    public function playlist(): BelongsTo
    {
        return $this->belongsTo(Playlist::class, 'playlist_id');
    }

    public function surah(): BelongsTo
    {
        return $this->belongsTo(Surah::class, 'surah_id');
    }

    /**
     * The recording (not the person). Sources are eager-loaded on Reciter by
     * default and never needed here.
     */
    public function reciter(): BelongsTo
    {
        return $this->belongsTo(Reciter::class, 'reciter_id')->without('sources');
    }
}
