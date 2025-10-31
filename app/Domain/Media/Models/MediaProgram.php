<?php

declare(strict_types=1);

namespace App\Domain\Media\Models;

use App\Domain\Media\Enums\ProgramStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Domain\Shared\Traits\HasInteractions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
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
class MediaProgram extends Model implements HasMedia
{
    use HasFactory, HasTranslations, InteractsWithMedia, HasInteractions;

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
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(MediaCategory::class, 'category_id');
    }

    /**
     * Get all episodes in this program
     */
    public function episodes(): HasMany
    {
        return $this->hasMany(MediaEpisode::class, 'program_id');
    }

    /**
     * Get published episodes
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
     */
    public function latestEpisode(): ?MediaEpisode
    {
        return $this->publishedEpisodes()
            ->orderByDesc('episode_number')
            ->first();
    }

    /**
     * Increment total views
     */
    public function incrementViews(int $amount = 1): void
    {
        $this->increment('total_views', $amount);
    }

    /**
     * Update episode count
     */
    public function updateEpisodeCount(): void
    {
        $count = $this->episodes()->count();
        $this->update(['total_episodes' => $count]);
    }

    /**
     * Scope: Filter by status
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
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
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope: Popular programs
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePopular($query, int $limit = 10)
    {
        return $query->orderByDesc('total_views')->limit($limit);
    }

    /**
     * Scope: Recent programs
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecent($query, int $limit = 10)
    {
        return $query->orderByDesc('created_at')->limit($limit);
    }

    /**
     * Register media collections
     */
    public function registerMediaCollections(): void
    {
        // Thumbnail collection (single file)
        $this->addMediaCollection('thumbnail')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'])
            ->maxFilesize(10 * 1024 * 1024) // 10MB
            ->useDisk('public')
            ->registerMediaConversions(function () {
                $this->addMediaConversion('thumb')
                    ->width(150)
                    ->height(150)
                    ->sharpen(10)
                    ->nonQueued();

                $this->addMediaConversion('medium')
                    ->width(600)
                    ->height(400)
                    ->sharpen(10)
                    ->nonQueued();

                $this->addMediaConversion('large')
                    ->width(1200)
                    ->height(800)
                    ->sharpen(10)
                    ->nonQueued();
            });

        // Banner collection (single file)
        $this->addMediaCollection('banner')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'])
            ->maxFilesize(10 * 1024 * 1024) // 10MB
            ->useDisk('public')
            ->registerMediaConversions(function () {
                $this->addMediaConversion('thumb')
                    ->width(150)
                    ->height(150)
                    ->sharpen(10)
                    ->nonQueued();

                $this->addMediaConversion('medium')
                    ->width(600)
                    ->height(400)
                    ->sharpen(10)
                    ->nonQueued();

                $this->addMediaConversion('large')
                    ->width(1200)
                    ->height(800)
                    ->sharpen(10)
                    ->nonQueued();
            });
    }
}
