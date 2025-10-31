<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Storage;

/**
 * Image Upload Result DTO
 *
 * Data Transfer Object containing all information about an uploaded image.
 * Provides convenient methods to access different size variants and URLs.
 *
 * @property-read string $original Path to original uploaded image
 * @property-read string $thumbnail Path to 150x150 thumbnail
 * @property-read string $medium Path to 400x300 medium size
 * @property-read string $large Path to 1200x800 large size
 * @property-read int $width Original image width in pixels
 * @property-read int $height Original image height in pixels
 * @property-read string $mimeType MIME type of the original image
 * @property-read int $fileSize File size in bytes
 */
class ImageUploadResult
{
    /**
     * Create a new image upload result instance
     *
     * @param string $original Path to original image
     * @param string $thumbnail Path to thumbnail (150x150)
     * @param string $medium Path to medium size (400x300)
     * @param string $large Path to large size (1200x800)
     * @param int $width Original image width
     * @param int $height Original image height
     * @param string $mimeType MIME type (e.g., 'image/jpeg')
     * @param int $fileSize File size in bytes
     */
    public function __construct(
        public readonly string $original,
        public readonly string $thumbnail,
        public readonly string $medium,
        public readonly string $large,
        public readonly int $width,
        public readonly int $height,
        public readonly string $mimeType,
        public readonly int $fileSize
    ) {}

    /**
     * Get public URL for specific size variant
     *
     * @param string $size Size variant: 'original', 'thumbnail', 'medium', 'large'
     * @return string Full public URL
     */
    public function getUrl(string $size = 'original'): string
    {
        $path = match ($size) {
            'thumbnail' => $this->thumbnail,
            'medium' => $this->medium,
            'large' => $this->large,
            default => $this->original,
        };

        return Storage::disk('public')->url($path);
    }

    /**
     * Get thumbnail URL (150x150)
     *
     * @return string Full public URL to thumbnail
     */
    public function getThumbnailUrl(): string
    {
        return $this->getUrl('thumbnail');
    }

    /**
     * Get medium size URL (400x300)
     *
     * @return string Full public URL to medium size
     */
    public function getMediumUrl(): string
    {
        return $this->getUrl('medium');
    }

    /**
     * Get large size URL (1200x800)
     *
     * @return string Full public URL to large size
     */
    public function getLargeUrl(): string
    {
        return $this->getUrl('large');
    }

    /**
     * Get original image URL
     *
     * @return string Full public URL to original
     */
    public function getOriginalUrl(): string
    {
        return $this->getUrl('original');
    }

    /**
     * Get all paths as associative array
     *
     * @return array<string, string> Array with keys: original, thumbnail, medium, large
     */
    public function getPaths(): array
    {
        return [
            'original' => $this->original,
            'thumbnail' => $this->thumbnail,
            'medium' => $this->medium,
            'large' => $this->large,
        ];
    }

    /**
     * Get all URLs as associative array
     *
     * @return array<string, string> Array with keys: original, thumbnail, medium, large
     */
    public function getUrls(): array
    {
        return [
            'original' => $this->getOriginalUrl(),
            'thumbnail' => $this->getThumbnailUrl(),
            'medium' => $this->getMediumUrl(),
            'large' => $this->getLargeUrl(),
        ];
    }

    /**
     * Get complete result as associative array
     *
     * @return array<string, mixed> Complete upload result
     */
    public function getAll(): array
    {
        return [
            'paths' => $this->getPaths(),
            'urls' => $this->getUrls(),
            'width' => $this->width,
            'height' => $this->height,
            'mimeType' => $this->mimeType,
            'fileSize' => $this->fileSize,
            'fileSizeFormatted' => $this->getFormattedFileSize(),
        ];
    }

    /**
     * Get human-readable file size
     *
     * @return string Formatted file size (e.g., '1.5 MB')
     */
    public function getFormattedFileSize(): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = $this->fileSize;
        $index = 0;

        while ($bytes >= 1024 && $index < count($units) - 1) {
            $bytes /= 1024;
            $index++;
        }

        return round($bytes, 2) . ' ' . $units[$index];
    }

    /**
     * Convert result to JSON array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->getAll();
    }

    /**
     * Convert result to JSON string
     *
     * @return string JSON representation
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }
}
