<?php

declare(strict_types=1);

namespace App\Domain\Blog\Models;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * User Interaction Model
 * Tracks user interactions with content (views, progress, completion)
 *
 * @property int $id
 * @property int $user_id
 * @property int $interactable_id
 * @property string $interactable_type
 * @property string $interaction_type
 * @property array|null $metadata
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class UserInteraction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'interactable_id',
        'interactable_type',
        'interaction_type',
        'metadata',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the interactable item (polymorphic)
     */
    public function interactable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope: Filter by interaction type
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('interaction_type', $type);
    }

    /**
     * Scope: Views only
     */
    public function scopeViews(Builder $query): Builder
    {
        return $query->where('interaction_type', 'view');
    }

    /**
     * Scope: Progress tracking
     */
    public function scopeProgress(Builder $query): Builder
    {
        return $query->where('interaction_type', 'progress');
    }

    /**
     * Scope: Completed items
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('interaction_type', 'complete');
    }
}
