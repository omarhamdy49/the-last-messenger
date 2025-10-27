<?php

declare(strict_types=1);

namespace App\Domain\Blog\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Favorite Model
 * Polymorphic relationship for user favorites/bookmarks
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
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'favorable_id',
        'favorable_type',
    ];

    /**
     * Get the user who favorited
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the favorable item (polymorphic)
     *
     * @return MorphTo
     */
    public function favorable(): MorphTo
    {
        return $this->morphTo();
    }
}

