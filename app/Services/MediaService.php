<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaService
{
    /**
     * Upload and optimize an image
     */
    public function upload(UploadedFile $file, array $options = []): Media
    {
        $collectionName = $options['collection_name'] ?? 'default';
        $disk = $options['disk'] ?? 'public';
        $optimize = $options['optimize'] ?? true;

        // Generate unique filename
        $filename = $this->generateFilename($file);
        $path = $this->generatePath($filename, $collectionName);

        // Store the file
        $storedPath = $file->storeAs(
            dirname($path),
            basename($path),
            $disk
        );

        // Get file dimensions if it's an image
        $dimensions = $this->getImageDimensions($file);

        // Create media record
        $media = Media::create([
            'filename' => $filename,
            'original_filename' => $file->getClientOriginalName(),
            'path' => $storedPath,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'width' => $dimensions['width'] ?? null,
            'height' => $dimensions['height'] ?? null,
            'disk' => $disk,
            'type' => $this->getMediaType($file),
            'collection_name' => $collectionName,
            'alt_text' => $options['alt_text'] ?? null,
            'caption' => $options['caption'] ?? null,
            'metadata' => $options['metadata'] ?? [],
        ]);

        // Optimize image if requested
        if ($optimize && $media->isImage()) {
            $this->optimizeImage($media);
        }

        return $media;
    }

    /**
     * Optimize an image using ImageMagick
     */
    public function optimizeImage(Media $media, array $options = []): bool
    {
        if (!$media->isImage()) {
            return false;
        }

        $fullPath = $media->full_path;

        if (!file_exists($fullPath)) {
            return false;
        }

        // Default optimization settings
        $quality = $options['quality'] ?? 85;
        $maxWidth = $options['max_width'] ?? 2000;
        $maxHeight = $options['max_height'] ?? 2000;

        try {
            // Check if ImageMagick is available
            if (!$this->isImageMagickAvailable()) {
                throw new \Exception('ImageMagick is not available');
            }

            $originalSize = filesize($fullPath);
            $commands = [];

            // Resize if dimensions exceed max
            if ($media->width > $maxWidth || $media->height > $maxHeight) {
                $commands[] = "-resize {$maxWidth}x{$maxHeight}>";
            }

            // Set quality
            $commands[] = "-quality {$quality}";

            // Strip metadata
            $commands[] = "-strip";

            // Auto-orient based on EXIF
            $commands[] = "-auto-orient";

            // Sharpen slightly
            $commands[] = "-unsharp 0.25x0.25+8+0.065";

            // Convert to sRGB color space
            $commands[] = "-colorspace sRGB";

            // Execute ImageMagick command
            $command = sprintf(
                'convert %s %s %s',
                escapeshellarg($fullPath),
                implode(' ', $commands),
                escapeshellarg($fullPath)
            );

            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                throw new \Exception('ImageMagick command failed');
            }

            // Update media metadata
            $newSize = filesize($fullPath);
            $dimensions = getimagesize($fullPath);

            $media->update([
                'size' => $newSize,
                'width' => $dimensions[0] ?? $media->width,
                'height' => $dimensions[1] ?? $media->height,
                'metadata' => array_merge($media->metadata ?? [], [
                    'optimized' => true,
                    'original_size' => $originalSize,
                    'optimized_size' => $newSize,
                    'size_reduction' => round((($originalSize - $newSize) / $originalSize) * 100, 2) . '%',
                    'optimized_at' => now()->toISOString(),
                ]),
            ]);

            return true;
        } catch (\Exception $e) {
            // Log error but don't fail the upload
            logger()->error('Image optimization failed: ' . $e->getMessage(), [
                'media_id' => $media->id,
                'path' => $fullPath,
            ]);

            return false;
        }
    }

    /**
     * Generate a unique filename
     */
    protected function generateFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        return Str::random(40) . '.' . $extension;
    }

    /**
     * Generate storage path
     */
    protected function generatePath(string $filename, string $collectionName): string
    {
        $date = now();
        return sprintf(
            'media/%s/%s/%s/%s',
            $collectionName,
            $date->format('Y'),
            $date->format('m'),
            $filename
        );
    }

    /**
     * Get image dimensions
     */
    protected function getImageDimensions(UploadedFile $file): array
    {
        if (!str_starts_with($file->getMimeType(), 'image/')) {
            return [];
        }

        try {
            $dimensions = getimagesize($file->getRealPath());
            return [
                'width' => $dimensions[0] ?? null,
                'height' => $dimensions[1] ?? null,
            ];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Determine media type from file
     */
    protected function getMediaType(UploadedFile $file): string
    {
        $mimeType = $file->getMimeType();

        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }

        if (str_starts_with($mimeType, 'video/')) {
            return 'video';
        }

        if (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        }

        return 'document';
    }

    /**
     * Check if ImageMagick is available
     */
    protected function isImageMagickAvailable(): bool
    {
        exec('which convert', $output, $returnCode);
        return $returnCode === 0;
    }

    /**
     * Create thumbnail from an image
     */
    public function createThumbnail(Media $media, int $width = 300, int $height = 300): ?string
    {
        if (!$media->isImage()) {
            return null;
        }

        $fullPath = $media->full_path;

        if (!file_exists($fullPath)) {
            return null;
        }

        try {
            // Generate thumbnail path
            $thumbnailFilename = 'thumb_' . $media->filename;
            $thumbnailPath = str_replace($media->filename, $thumbnailFilename, $media->path);
            $thumbnailFullPath = Storage::disk($media->disk)->path($thumbnailPath);

            // Create thumbnail directory if it doesn't exist
            $thumbnailDir = dirname($thumbnailFullPath);
            if (!file_exists($thumbnailDir)) {
                mkdir($thumbnailDir, 0755, true);
            }

            // Create thumbnail using ImageMagick
            $command = sprintf(
                'convert %s -thumbnail %dx%d^ -gravity center -extent %dx%d -quality 85 %s',
                escapeshellarg($fullPath),
                $width,
                $height,
                $width,
                $height,
                escapeshellarg($thumbnailFullPath)
            );

            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                throw new \Exception('Thumbnail creation failed');
            }

            return $thumbnailPath;
        } catch (\Exception $e) {
            logger()->error('Thumbnail creation failed: ' . $e->getMessage(), [
                'media_id' => $media->id,
            ]);

            return null;
        }
    }
}
