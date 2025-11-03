<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Spatie\Translatable\HasTranslations;

/**
 * ArticleGallery Model
 *
 * Represents gallery images associated with an article in The Last Messenger platform.
 * Supports multi-language captions and alt text (AR, EN, FR, TR, UR) for accessibility.
 *
 * @property int $id
 * @property int $article_id Article ID
 * @property string $image_path Path to the image file
 * @property array|null $caption Multi-language caption (JSON: {ar, en, fr, tr, ur})
 * @property array|null $alt_text Multi-language alt text (JSON: {ar, en, fr, tr, ur})
 * @property int $order Display order
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Article $article Parent article
 * @property-read string $image_url Full URL to the image
 */
class ArticleGallery extends Model
{
    use HasFactory;
    use HasTranslations;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'article_gallery';

    /**
     * Supported languages for content
     */
    public const SUPPORTED_LANGUAGES = ['ar', 'en', 'fr', 'tr', 'ur'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'article_id',
        'image_path',
        'caption',
        'alt_text',
        'order',
    ];

    /**
     * Translatable attributes using Spatie Laravel Translatable
     *
     * @var array<string>
     */
    public $translatable = [
        'caption',
        'alt_text',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'article_id' => 'integer',
            'order' => 'integer',
        ];
    }

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<string>
     */
    protected $appends = [
        'image_url',
    ];

    // ========================================================================
    // RELATIONSHIPS
    // ========================================================================

    /**
     * Get the article that owns this gallery image
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    // ========================================================================
    // ACCESSORS
    // ========================================================================

    /**
     * Get the full URL for the image
     *
     * @return string
     */
    public function getImageUrlAttribute(): string
    {
        // If the path is already a full URL (external image)
        if (filter_var($this->image_path, FILTER_VALIDATE_URL)) {
            return $this->image_path;
        }

        // If using cloud storage (S3, etc.)
        if (config('filesystems.default') !== 'public') {
            return Storage::url($this->image_path);
        }

        // For local storage, prepend the base URL
        return asset('storage/' . $this->image_path);
    }

    // ========================================================================
    // HELPER METHODS
    // ========================================================================

    /**
     * Get caption with fallback to default language
     *
     * @param string $locale Language code
     * @param string $fallbackLocale Fallback language code (default: 'ar')
     * @return string|null
     */
    public function getCaptionForLocale(string $locale = 'ar', string $fallbackLocale = 'ar'): ?string
    {
        $caption = $this->getTranslation('caption', $locale, false);

        if (empty($caption) && $locale !== $fallbackLocale) {
            $caption = $this->getTranslation('caption', $fallbackLocale, false);
        }

        return $caption;
    }

    /**
     * Get alt text with fallback to default language
     *
     * @param string $locale Language code
     * @param string $fallbackLocale Fallback language code (default: 'ar')
     * @return string|null
     */
    public function getAltTextForLocale(string $locale = 'ar', string $fallbackLocale = 'ar'): ?string
    {
        $altText = $this->getTranslation('alt_text', $locale, false);

        if (empty($altText) && $locale !== $fallbackLocale) {
            $altText = $this->getTranslation('alt_text', $fallbackLocale, false);
        }

        return $altText;
    }

    /**
     * Check if the image exists in storage
     *
     * @return bool
     */
    public function imageExists(): bool
    {
        // If it's an external URL, assume it exists
        if (filter_var($this->image_path, FILTER_VALIDATE_URL)) {
            return true;
        }

        return Storage::exists($this->image_path);
    }

    /**
     * Delete the image file from storage
     *
     * @return bool
     */
    public function deleteImageFile(): bool
    {
        // Don't delete external URLs
        if (filter_var($this->image_path, FILTER_VALIDATE_URL)) {
            return false;
        }

        if ($this->imageExists()) {
            return Storage::delete($this->image_path);
        }

        return false;
    }
}
