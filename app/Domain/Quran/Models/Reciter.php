<?php

declare(strict_types=1);

namespace App\Domain\Quran\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;

/**
 * Reciter Model (Quran Reciter/Qari)
 *
 * @property int $id
 * @property string $slug
 * @property array $name
 * @property array|null $description
 * @property string|null $style
 * @property string|null $country
 * @property string|null $profile_image
 * @property bool $is_featured
 * @property int $display_order
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Reciter extends Model implements HasMedia
{
    use HasFactory, HasTranslations, InteractsWithMedia;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'quran_reciters';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'slug',
        'name',
        'description',
        'style',
        'country',
        'profile_image',
        'is_featured',
        'display_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'name' => 'array',
        'description' => 'array',
        'is_featured' => 'boolean',
        'display_order' => 'integer',
    ];

    /**
     * Translatable attributes
     *
     * @var array<string>
     */
    public array $translatable = [
        'name',
        'description',
    ];

    /**
     * Get display name
     */
    public function getDisplayName(string $language = 'ar'): string
    {
        return $this->name[$language] ?? $this->name['ar'] ?? '';
    }

    /**
     * Scope: Featured reciters
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope: Order by display order
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order');
    }

    /**
     * Register media collections
     */
    public function registerMediaCollections(): void
    {
        // Profile image collection (single file)
        $this->addMediaCollection('profile_image')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'])
            ->maxFilesize(10 * 1024 * 1024) // 10MB
            ->useDisk('public');
    }

    /**
     * Register media conversions
     */
    public function registerMediaConversions(?\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        // Avatar conversion (100x100 crop-center for small profile pics)
        $this->addMediaConversion('avatar')
            ->width(100)
            ->height(100)
            ->crop('crop-center')
            ->sharpen(10)
            ->optimize()
            ->performOnCollections('profile_image')
            ->nonQueued();

        // Display conversion (400x400 contain for profile page)
        $this->addMediaConversion('display')
            ->width(400)
            ->height(400)
            ->fit('contain')
            ->sharpen(10)
            ->optimize()
            ->performOnCollections('profile_image')
            ->nonQueued();
    }

    /**
     * Get profile image URL (accessor with backward compatibility)
     *
     * @return string|null
     */
    public function getProfileImageUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('profile_image') ?: $this->profile_image;
    }

    /**
     * Get avatar URL (small profile image)
     *
     * @return string|null
     */
    public function getAvatarUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('profile_image', 'avatar') ?: null;
    }
}
