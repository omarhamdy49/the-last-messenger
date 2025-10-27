<?php

declare(strict_types=1);

namespace App\Domain\Media\Models;

use App\Domain\Media\Enums\ProgramStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * MediaProgram Model
 *
 * @property int $id
 * @property string $slug
 * @property int $category_id
 * @property array $title
 * @property array|null $description
 * @property string|null $thumbnail
 * @property string|null $banner_image
 * @property int $total_episodes
 * @property int $total_views
 * @property ProgramStatus $status
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class MediaProgram extends Model
{
    use HasFactory, HasTranslations;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'media_programs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'slug',
        'category_id',
        'title',
        'description',
        'thumbnail',
        'banner_image',
        'total_episodes',
        'total_views',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'title' => 'array',
        'description' => 'array',
        'total_episodes' => 'integer',
        'total_views' => 'integer',
        'status' => ProgramStatus::class,
    ];

    /**
     * Translatable attributes
     *
     * @var array<string>
     */
    public array $translatable = ['title', 'description'];

    /**
     * Get the category this program belongs to
     *
     * @return BelongsTo
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(MediaCategory::class, 'category_id');
    }

    /**
     * Get all episodes in this program
     *
     * @return HasMany
     */
    public function episodes(): HasMany
    {
        return $this->hasMany(MediaEpisode::class, 'program_id');
    }

    /**
     * Get published episodes
     *
     * @return HasMany
     */
    public function publishedEpisodes(): HasMany
    {
        return $this->episodes()
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderBy('episode_number');
    }

    /**
     * Get the latest episode
     *
     * @return MediaEpisode|null
     */
    public function latestEpisode(): ?MediaEpisode
    {
        return $this->publishedEpisodes()
            ->orderByDesc('episode_number')
            ->first();
    }

    /**
     * Increment total views
     *
     * @param int $amount
     * @return void
     */
    public function incrementViews(int $amount = 1): void
    {
        $this->increment('total_views', $amount);
    }

    /**
     * Update episode count
     *
     * @return void
     */
    public function updateEpisodeCount(): void
    {
        $count = $this->episodes()->count();
        $this->update(['total_episodes' => $count]);
    }

    /**
     * Scope: Filter by status
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param ProgramStatus|string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStatus($query, ProgramStatus|string $status)
    {
        $statusValue = $status instanceof ProgramStatus ? $status->value : $status;
        return $query->where('status', $statusValue);
    }

    /**
     * Scope: Filter by category
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $categoryId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope: Popular programs
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePopular($query, int $limit = 10)
    {
        return $query->orderByDesc('total_views')->limit($limit);
    }

    /**
     * Scope: Recent programs
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecent($query, int $limit = 10)
    {
        return $query->orderByDesc('created_at')->limit($limit);
    }
}

