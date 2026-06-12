<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Favorite (bookmark) model.
 *
 * A cross-cutting record linking a user to any bookmarkable content via a
 * polymorphic relation. Lives at the app-model level (not inside a domain
 * module) because bookmarks span every module.
 *
 * @property int $id
 * @property int $user_id
 * @property int $favorable_id
 * @property string $favorable_type
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Favorite extends Model
{
    use HasFactory;

    /**
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'favorable_id',
        'favorable_type',
    ];

    /**
     * The user who created the bookmark.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The bookmarked content (polymorphic).
     */
    public function favorable(): MorphTo
    {
        return $this->morphTo();
    }
}
