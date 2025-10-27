<?php

declare(strict_types=1);

namespace App\Domain\Media\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

/**
 * MediaEpisode Model
 *
 * @property int $id
 * @property int $program_id
 * @property string $slug
 * @property int $episode_number
 * @property array $title
 * @property array|null $description
 * @property string|null $thumbnail
 * @property string $video_url
 * @property int $duration_minutes
 * @property int $views_count
 * @property \Carbon\Carbon|null $published_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class MediaEpisode extends Model
{
    use HasFactory, HasTranslations;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'media_episodes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'program_id',
        'slug',
        'episode_number',
        'title',
        'description',
        'thumbnail',
        'video_url',
        'duration_minutes',
        'views_count',
        'published_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'title' => 'array',
        'description' => 'array',
        'episode_number' => 'integer',
        'duration_minutes' => 'integer',
        'views_count' => 'integer',
        'published_at' => 'datetime',
    ];

    /**
     * Translatable attributes
     *
     * @var array<string>
     */
    public array $translatable = ['title', 'description'];

    /**
     * Get the program this episode belongs to
     *
     * @return BelongsTo
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(MediaProgram::class, 'program_id');
    }

    /**
     * Get the next episode
     *
     * @return MediaEpisode|null
     */
    public function nextEpisode(): ?MediaEpisode
    {
        return self::where('program_id', $this->program_id)
            ->where('episode_number', '>', $this->episode_number)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderBy('episode_number')
            ->first();
    }

    /**
     * Get the previous episode
     *
     * @return MediaEpisode|null
     */
    public function previousEpisode(): ?MediaEpisode
    {
        return self::where('program_id', $this->program_id)
            ->where('episode_number', '<', $this->episode_number)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderByDesc('episode_number')
            ->first();
    }

    /**
     * Check if episode is published
     *
     * @return bool
     */
    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->published_at->lte(now());
    }

    /**
     * Increment views count
     *
     * @param int $amount
     * @return void
     */
    public function incrementViews(int $amount = 1): void
    {
        $this->increment('views_count', $amount);
        $this->program->incrementViews($amount);
    }

    /**
     * Get duration in human-readable format
     *
     * @return string
     */
    public function getDurationFormatted(): string
    {
        $hours = floor($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;

        if ($hours > 0) {
            return sprintf('%dh %dm', $hours, $minutes);
        }

        return sprintf('%dm', $minutes);
    }

    /**
     * Extract video ID from URL (YouTube, Vimeo)
     *
     * @return string|null
     */
    public function getVideoId(): ?string
    {
        // YouTube
        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\?]+)/', $this->video_url, $matches)) {
            return $matches[1];
        }

        // Vimeo
        if (preg_match('/vimeo\.com\/(\d+)/', $this->video_url, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Get video platform (youtube, vimeo, or direct)
     *
     * @return string
     */
    public function getVideoPlatform(): string
    {
        if (str_contains($this->video_url, 'youtube.com') || str_contains($this->video_url, 'youtu.be')) {
            return 'youtube';
        }

        if (str_contains($this->video_url, 'vimeo.com')) {
            return 'vimeo';
        }

        return 'direct';
    }

    /**
     * Scope: Published episodes
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Scope: By program
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $programId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByProgram($query, int $programId)
    {
        return $query->where('program_id', $programId);
    }

    /**
     * Scope: Order by episode number
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $direction
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderByEpisode($query, string $direction = 'asc')
    {
        return $query->orderBy('episode_number', $direction);
    }

    /**
     * Scope: Most viewed
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeMostViewed($query, int $limit = 10)
    {
        return $query->orderByDesc('views_count')->limit($limit);
    }
}

