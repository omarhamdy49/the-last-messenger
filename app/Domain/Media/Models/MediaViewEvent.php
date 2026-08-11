<?php

declare(strict_types=1);

namespace App\Domain\Media\Models;

use App\Domain\Media\Enums\MediaViewableType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * MediaViewEvent Model
 *
 * One deduplicated view of a program, episode, or live channel (guests
 * included via visitor_hash). Insert-only: written by RecordMediaViewJob,
 * aggregated by media:compute-trending, pruned after 90 days.
 *
 * @property int $id
 * @property MediaViewableType $viewable_type
 * @property int $viewable_id
 * @property int|null $user_id
 * @property string $visitor_hash
 * @property Carbon $created_at
 */
class MediaViewEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'viewable_type',
        'viewable_id',
        'user_id',
        'visitor_hash',
        'created_at',
    ];

    protected $casts = [
        'viewable_type' => MediaViewableType::class,
        'viewable_id' => 'integer',
        'user_id' => 'integer',
        'created_at' => 'datetime',
    ];
}
