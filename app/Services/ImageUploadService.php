<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ImageCompressionException;
use App\Exceptions\ImageStorageException;
use App\Exceptions\ImageValidationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/**
 * Image Upload Service
 *
 * Handles image uploads with validation, optimization, and thumbnail generation.
 * Supports JPEG, PNG, WebP, and GIF formats with automatic compression and resizing.
 *
 * Features:
 * - Multi-size thumbnail generation (thumbnail, medium, large)
 * - Automatic image optimization and compression
 * - File validation (type, size, dimensions)
 * - UUID-based unique filenames
 * - Date-based directory organization (YYYY/MM/DD)
 * - Old image cleanup
 * - WebP conversion support
 *
 * Requirements:
 * - PHP GD extension (or Imagick)
 * - Intervention Image 3.x
 *
 * Installation:
 * composer require intervention/image
 *
 * Usage Example:
 * ```php
 * $service = new ImageUploadService();
 * $result = $service->uploadArticleImage($request->file('image'));
 * echo $result->getThumbnailUrl();
 * ```
 */
class ImageUploadService
{
    /**
     * Allowed MIME types for image uploads
     */
    public const IMAGE_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
    ];

    /**
     * Allowed file extensions
     */
    public const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    /**
     * Maximum file size in bytes (2MB)
     */
    public const MAX_FILE_SIZE = 2097152; // 2MB

    /**
     * Minimum file size in bytes (10KB)
     */
    public const MIN_FILE_SIZE = 10240; // 10KB

    /**
     * Minimum image dimensions
     */
    public const MIN_DIMENSIONS = 200;

    /**
     * Maximum image dimensions
     */
    public const MAX_DIMENSIONS = 4000;

    /**
     * Thumbnail size configurations
     *
     * @var array<string, array{width: int, height: int, mode: string}>
     */
    public const SIZES = [
        'thumbnail' => ['width' => 150, 'height' => 150, 'mode' => 'crop'],
        'medium' => ['width' => 400, 'height' => 300, 'mode' => 'fit'],
        'large' => ['width' => 1200, 'height' => 800, 'mode' => 'fit'],
    ];

    /**
     * JPEG compression quality (1-100)
     */
    public const COMPRESSION_QUALITY = 85;

    /**
     * Storage disk name
     */
    public const STORAGE_DISK = 'public';

    /**
     * Base storage path for articles
     */
    public const STORAGE_PATH = 'articles';

    /**
     * Image manager instance
     */
    protected ImageManager $imageManager;

    /**
     * Create new ImageUploadService instance
     */
    public function __construct()
    {
        // Initialize Intervention Image with GD driver
        // Note: You can switch to Imagick driver if available
        $this->imageManager = new ImageManager(new Driver());
    }

    /**
     * Upload and process article image
     *
     * Main method for uploading images with automatic validation,
     * optimization, and thumbnail generation.
     *
     * @param UploadedFile $file The uploaded file from request
     * @param string|null $oldImagePath Optional path to old image to delete
     * @param string|null $prefix Optional filename prefix (default: 'article')
     * @return ImageUploadResult Upload result with all paths and metadata
     *
     * @throws ImageValidationException If validation fails
     * @throws ImageCompressionException If image processing fails
     * @throws ImageStorageException If file storage fails
     */
    public function uploadArticleImage(
        UploadedFile $file,
        ?string $oldImagePath = null,
        ?string $prefix = 'article'
    ): ImageUploadResult {
        // Step 1: Validate the uploaded file
        $this->validateImage($file);

        try {
            // Step 2: Generate unique filename and path
            $filename = $this->generateUniqueFilename($file, $prefix ?? 'article');
            $datePath = $this->getDateBasedPath();
            $fullPath = "{$datePath}/{$filename}";

            // Step 3: Store original image
            $storedPath = $this->storeOriginalImage($file, $fullPath);

            // Step 4: Get image dimensions and metadata
            $dimensions = $this->getImageDimensions($storedPath);
            $mimeType = $file->getMimeType() ?? 'image/jpeg';
            $fileSize = $file->getSize() ?? 0;

            // Step 5: Generate thumbnails
            $thumbnails = $this->generateThumbnails($storedPath);

            // Step 6: Optimize original image
            $this->optimizeImage($storedPath);

            // Step 7: Delete old image if provided
            if ($oldImagePath) {
                $this->deleteImage($oldImagePath);
            }

            // Step 8: Create and return result
            return new ImageUploadResult(
                original: $storedPath,
                thumbnail: $thumbnails['thumbnail'],
                medium: $thumbnails['medium'],
                large: $thumbnails['large'],
                width: $dimensions['width'],
                height: $dimensions['height'],
                mimeType: $mimeType,
                fileSize: $fileSize
            );
        } catch (ImageValidationException|ImageCompressionException|ImageStorageException $e) {
            // Re-throw our custom exceptions
            throw $e;
        } catch (\Throwable $e) {
            // Log unexpected errors and wrap in storage exception
            Log::error('Image upload failed', [
                'file' => $file->getClientOriginalName(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new ImageStorageException(
                "Failed to upload image: {$e->getMessage()}",
                $e
            );
        }
    }

    /**
     * Validate uploaded image file
     *
     * Validates file type, size, and dimensions according to defined constraints.
     *
     * @param UploadedFile $file The file to validate
     * @return void
     *
     * @throws ImageValidationException If validation fails
     */
    public function validateImage(UploadedFile $file): void
    {
        // Validate file was uploaded successfully
        if (! $file->isValid()) {
            throw new ImageValidationException(
                'File upload failed. Please try again.'
            );
        }

        // Validate MIME type
        $mimeType = $file->getMimeType();
        if (! in_array($mimeType, self::IMAGE_MIME_TYPES, true)) {
            throw new ImageValidationException(
                'Invalid image file type. Only JPEG, PNG, WebP, and GIF are allowed.'
            );
        }

        // Validate file extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new ImageValidationException(
                "Invalid file extension '.{$extension}'. Only " . implode(', ', self::ALLOWED_EXTENSIONS) . ' are allowed.'
            );
        }

        // Validate file size
        $fileSize = $file->getSize();
        if ($fileSize > self::MAX_FILE_SIZE) {
            $maxSizeMB = round(self::MAX_FILE_SIZE / 1048576, 1);
            throw new ImageValidationException(
                "File size exceeds {$maxSizeMB}MB limit. Please upload a smaller image."
            );
        }

        if ($fileSize < self::MIN_FILE_SIZE) {
            $minSizeKB = round(self::MIN_FILE_SIZE / 1024, 1);
            throw new ImageValidationException(
                "File size is too small. Minimum size is {$minSizeKB}KB."
            );
        }

        // Validate image dimensions
        try {
            $imageInfo = getimagesize($file->getRealPath());
            if ($imageInfo === false) {
                throw new ImageValidationException(
                    'Unable to read image file. The file may be corrupted.'
                );
            }

            [$width, $height] = $imageInfo;

            if ($width < self::MIN_DIMENSIONS || $height < self::MIN_DIMENSIONS) {
                throw new ImageValidationException(
                    "Image dimensions must be at least {$width}x{$height} pixels. Current size: {$width}x{$height}px."
                );
            }

            if ($width > self::MAX_DIMENSIONS || $height > self::MAX_DIMENSIONS) {
                throw new ImageValidationException(
                    "Image dimensions exceed maximum {$width}x{$height} pixels. Current size: {$width}x{$height}px."
                );
            }
        } catch (ImageValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new ImageValidationException(
                'Failed to validate image: ' . $e->getMessage(),
                $e
            );
        }
    }

    /**
     * Generate thumbnails in multiple sizes
     *
     * Creates thumbnail, medium, and large size variants of the image.
     *
     * @param string $imagePath Path to original image
     * @return array<string, string> Paths to generated thumbnails
     *
     * @throws ImageCompressionException If thumbnail generation fails
     */
    public function generateThumbnails(string $imagePath): array
    {
        $thumbnails = [];
        $disk = Storage::disk(self::STORAGE_DISK);
        $fullPath = $disk->path($imagePath);

        try {
            $image = $this->imageManager->read($fullPath);

            foreach (self::SIZES as $sizeName => $config) {
                // Create resized image
                $resized = clone $image;

                if ($config['mode'] === 'crop') {
                    // Crop to exact dimensions (for square thumbnails)
                    $resized->cover($config['width'], $config['height']);
                } else {
                    // Fit within dimensions maintaining aspect ratio
                    $resized->scale(
                        width: $config['width'],
                        height: $config['height']
                    );
                }

                // Generate thumbnail filename
                $thumbnailPath = $this->getThumbnailPath($imagePath, $sizeName);
                $thumbnailFullPath = $disk->path($thumbnailPath);

                // Ensure directory exists
                $this->ensureDirectoryExists(dirname($thumbnailFullPath));

                // Save thumbnail with compression
                $quality = $this->getCompressionQuality($thumbnailPath);
                $resized->save($thumbnailFullPath, quality: $quality);

                $thumbnails[$sizeName] = $thumbnailPath;
            }

            return $thumbnails;
        } catch (\Throwable $e) {
            Log::error('Thumbnail generation failed', [
                'image' => $imagePath,
                'error' => $e->getMessage(),
            ]);

            throw new ImageCompressionException(
                "Failed to generate thumbnails: {$e->getMessage()}",
                $e
            );
        }
    }

    /**
     * Optimize and compress image
     *
     * Applies compression to reduce file size while maintaining quality.
     *
     * @param string $imagePath Path to image to optimize
     * @return void
     *
     * @throws ImageCompressionException If optimization fails
     */
    public function optimizeImage(string $imagePath): void
    {
        $disk = Storage::disk(self::STORAGE_DISK);
        $fullPath = $disk->path($imagePath);

        try {
            $image = $this->imageManager->read($fullPath);
            $quality = $this->getCompressionQuality($imagePath);

            // Save with compression
            $image->save($fullPath, quality: $quality);
        } catch (\Throwable $e) {
            Log::error('Image optimization failed', [
                'image' => $imagePath,
                'error' => $e->getMessage(),
            ]);

            // Don't throw exception for optimization failure
            // Image is already uploaded, optimization is optional
        }
    }

    /**
     * Delete image and all its thumbnails
     *
     * @param string $imagePath Path to image to delete
     * @return bool True if deleted successfully
     */
    public function deleteImage(string $imagePath): bool
    {
        $disk = Storage::disk(self::STORAGE_DISK);
        $deleted = true;

        try {
            // Delete original
            if ($disk->exists($imagePath)) {
                $disk->delete($imagePath);
            }

            // Delete all thumbnail variants
            foreach (array_keys(self::SIZES) as $sizeName) {
                $thumbnailPath = $this->getThumbnailPath($imagePath, $sizeName);
                if ($disk->exists($thumbnailPath)) {
                    $disk->delete($thumbnailPath);
                }
            }
        } catch (\Throwable $e) {
            Log::error('Image deletion failed', [
                'image' => $imagePath,
                'error' => $e->getMessage(),
            ]);
            $deleted = false;
        }

        return $deleted;
    }

    /**
     * Get public URL for image
     *
     * @param string $path Relative path to image
     * @return string Full public URL
     */
    public function getImageUrl(string $path): string
    {
        return Storage::disk(self::STORAGE_DISK)->url($path);
    }

    /**
     * Get public path for image
     *
     * @param string $path Relative path to image
     * @return string Full file system path
     */
    public function getPublicPath(string $path): string
    {
        return Storage::disk(self::STORAGE_DISK)->path($path);
    }

    /**
     * Get image dimensions
     *
     * @param string $path Relative path to image
     * @return array{width: int, height: int} Image dimensions
     *
     * @throws ImageValidationException If unable to read image
     */
    public function getImageDimensions(string $path): array
    {
        $fullPath = $this->getPublicPath($path);

        try {
            $imageInfo = getimagesize($fullPath);
            if ($imageInfo === false) {
                throw new ImageValidationException('Unable to read image dimensions');
            }

            return [
                'width' => $imageInfo[0],
                'height' => $imageInfo[1],
            ];
        } catch (\Throwable $e) {
            throw new ImageValidationException(
                "Failed to get image dimensions: {$e->getMessage()}",
                $e
            );
        }
    }

    /**
     * Store original uploaded image
     *
     * @param UploadedFile $file The uploaded file
     * @param string $path Destination path
     * @return string Stored file path
     *
     * @throws ImageStorageException If storage fails
     */
    protected function storeOriginalImage(UploadedFile $file, string $path): string
    {
        try {
            $directory = dirname($path);
            $filename = basename($path);

            // Ensure directory exists
            $this->ensureDirectoryExists(
                Storage::disk(self::STORAGE_DISK)->path($directory)
            );

            // Store file
            $storedPath = $file->storeAs(
                $directory,
                $filename,
                self::STORAGE_DISK
            );

            if (! $storedPath) {
                throw new ImageStorageException('Failed to store image file');
            }

            return $storedPath;
        } catch (ImageStorageException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new ImageStorageException(
                "Failed to store image: {$e->getMessage()}",
                $e
            );
        }
    }

    /**
     * Generate unique filename for uploaded image
     *
     * @param UploadedFile $file The uploaded file
     * @param string $prefix Filename prefix
     * @return string Unique filename with extension
     */
    protected function generateUniqueFilename(UploadedFile $file, string $prefix = 'article'): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $uuid = Str::uuid()->toString();

        return "{$prefix}-{$uuid}.{$extension}";
    }

    /**
     * Get date-based storage path
     *
     * Generates path in format: articles/YYYY/MM/DD
     *
     * @return string Date-based path
     */
    protected function getDateBasedPath(): string
    {
        $date = now();

        return self::STORAGE_PATH . '/' .
            $date->format('Y') . '/' .
            $date->format('m') . '/' .
            $date->format('d');
    }

    /**
     * Get thumbnail path for specific size
     *
     * @param string $originalPath Path to original image
     * @param string $sizeName Size variant name (thumbnail, medium, large)
     * @return string Path to thumbnail
     */
    protected function getThumbnailPath(string $originalPath, string $sizeName): string
    {
        $pathInfo = pathinfo($originalPath);
        $directory = $pathInfo['dirname'];
        $filename = $pathInfo['filename'];
        $extension = $pathInfo['extension'];

        return "{$directory}/{$filename}-{$sizeName}.{$extension}";
    }

    /**
     * Get compression quality based on file type
     *
     * @param string $path Image path
     * @return int Compression quality (1-100)
     */
    protected function getCompressionQuality(string $path): int
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'jpg', 'jpeg' => self::COMPRESSION_QUALITY,
            'png' => 90, // PNG compression is different, higher quality
            'webp' => self::COMPRESSION_QUALITY,
            default => 100, // No compression for GIF and others
        };
    }

    /**
     * Ensure directory exists
     *
     * @param string $directory Full directory path
     * @return void
     *
     * @throws ImageStorageException If directory creation fails
     */
    protected function ensureDirectoryExists(string $directory): void
    {
        if (! is_dir($directory)) {
            if (! mkdir($directory, 0755, true) && ! is_dir($directory)) {
                throw new ImageStorageException(
                    "Failed to create directory: {$directory}"
                );
            }
        }
    }
}
