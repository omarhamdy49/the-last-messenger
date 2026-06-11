<?php

declare(strict_types=1);

namespace App\Domain\Media\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * EpisodeWatchProgress Model
 *
 * Tracks per-user, per-episode watch position.
 * The (user_id, episode_id) pair is unique — upsert is safe.
 * user_id is always set from auth()->id() in the API layer (IDOR-safe).
 *
 * @property int $id
 * @property int $user_id
 * @property int $episode_id
 * @property int $seconds
 * @property bool $completed
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class EpisodeWatchProgress extends Model
{
    protected $table = 'episode_watch_progress';

    protected $fillable = [
        'user_id',
        'episode_id',
        'seconds',
        'completed',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'episode_id' => 'integer',
        'seconds' => 'integer',
        'completed' => 'boolean',
    ];

    public function episode(): BelongsTo
    {
        return $this->belongsTo(MediaEpisode::class, 'episode_id');
    }
}
