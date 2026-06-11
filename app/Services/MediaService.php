<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Media;
use App\Models\MediaSizePreset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class MediaService
{
    protected const ENCRYPTED_MEDIA_MAGIC = 'MLIB1';

    protected const ENCRYPTED_CHUNK_BYTES = 1048576;

    protected ImageManager $imageManager;

    public function __construct()
    {
        $this->imageManager = new ImageManager(new Driver);
    }

    public function upload(UploadedFile $file, array $options = []): Media
    {
        $collectionName = $this->sanitizeCollectionName($options['collection_name'] ?? 'general');
        $disk = $options['disk'] ?? 'local';
        $generateSizes = (bool) ($options['generate_sizes'] ?? true);
        $optimize = (bool) ($options['optimize'] ?? true);
        $displayTitle = $this->extractDisplayTitle($file, $options);
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin');
        $baseDirectory = $this->buildBaseDirectory($collectionName);
        $secureFilename = $this->generateProtectedFilename($displayTitle, $extension);
        $storagePath = "{$baseDirectory}/original/{$secureFilename}";
        $mimeType = $file->getMimeType() ?: $file->getClientMimeType() ?: 'application/octet-stream';
        $workingCopy = $this->copyToTemporaryPath($file->getRealPath(), $extension);

        try {
            if (str_starts_with($mimeType, 'image/') && $optimize) {
                $this->optimizeImageFile($workingCopy);
            }

            $this->encryptFileToStorage($workingCopy, $disk, $storagePath);

            $dimensions = $this->getImageDimensions($workingCopy, $mimeType);
            $media = Media::create([
                'filename' => basename($storagePath),
                'original_filename' => $file->getClientOriginalName(),
                'path' => $storagePath,
                'mime_type' => $mimeType,
                'size' => filesize($workingCopy) ?: ($file->getSize() ?: 0),
                'width' => $dimensions['width'] ?? null,
                'height' => $dimensions['height'] ?? null,
                'disk' => $disk,
                'alt_text' => $options['alt_text'] ?? null,
                'caption' => $options['caption'] ?? null,
                'metadata' => $this->buildMetadata(
                    displayTitle: $displayTitle,
                    storedPath: $storagePath,
                    originalName: $file->getClientOriginalName(),
                    checksum: hash_file('sha256', $workingCopy) ?: null,
                    generateSizes: $generateSizes,
                    extra: $options['metadata'] ?? []
                ),
                'type' => $this->resolveMediaType($mimeType),
                'collection_name' => $collectionName,
                'tags' => $this->sanitizeTags($options['tags'] ?? []),
            ]);

            if ($media->isImage()) {
                $this->regenerateVariantsFromSource(
                    $media,
                    $workingCopy,
                    $generateSizes
                );
            }

            return $media->fresh();
        } finally {
            $this->deleteTemporaryFile($workingCopy);
        }
    }

    public function bulkUpload(array $files, array $options = []): Collection
    {
        return collect($files)
            ->filter(fn ($file) => $file instanceof UploadedFile)
            ->map(fn (UploadedFile $file) => $this->upload($file, $options));
    }

    public function updateMetadata(Media $media, array $attributes): Media
    {
        $metadata = $media->metadata ?? [];

        if (array_key_exists('title', $attributes)) {
            $metadata['display_title'] = trim((string) $attributes['title']) ?: $this->fallbackTitleFromOriginalName($media->original_filename);
        }

        if (array_key_exists('metadata', $attributes) && is_array($attributes['metadata'])) {
            $metadata = array_replace_recursive($metadata, $attributes['metadata']);
        }

        $media->fill(Arr::except($attributes, ['title', 'metadata']));
        $media->metadata = $metadata;
        $media->tags = $this->sanitizeTags($attributes['tags'] ?? $media->tags ?? []);
        $media->save();

        return $media->fresh();
    }

    public function crop(Media $media, array $cropData): Media
    {
        if (! $media->isImage()) {
            throw new RuntimeException('Only image files can be cropped.');
        }

        $workingCopy = $this->temporaryPath(
            $this->extensionFromMedia($media)
        );

        try {
            $this->decryptStoredFileToPath($media->disk, $media->path, $workingCopy, $this->isEncryptedMedia($media));
            $metadata = $media->metadata ?? [];
            $metadata['crop'] = [
                'x' => (float) $cropData['x'],
                'y' => (float) $cropData['y'],
                'width' => (float) $cropData['width'],
                'height' => (float) $cropData['height'],
                'cropped_at' => now()->toISOString(),
            ];

            $media->forceFill([
                'metadata' => $metadata,
            ])->save();

            return $this->regenerateVariantsFromSource(
                $media,
                $workingCopy,
                true
            );
        } finally {
            $this->deleteTemporaryFile($workingCopy);
        }
    }

    public function regenerateVariants(Media $media, bool $generateSizes = true): Media
    {
        if (! $media->isImage()) {
            $metadata = $media->metadata ?? [];
            $this->deleteGeneratedVariantsFromMetadata($media->disk, $metadata['variants'] ?? []);
            $metadata['generate_sizes'] = false;
            $metadata['variants'] = [];
            $media->forceFill(['metadata' => $metadata])->save();

            return $media->fresh();
        }

        $workingCopy = $this->temporaryPath(
            $this->extensionFromMedia($media)
        );

        try {
            $this->decryptStoredFileToPath($media->disk, $media->path, $workingCopy, $this->isEncryptedMedia($media));

            return $this->regenerateVariantsFromSource($media, $workingCopy, $generateSizes);
        } finally {
            $this->deleteTemporaryFile($workingCopy);
        }
    }

    public function optimizeImage(Media $media, array $options = []): bool
    {
        if (! $media->isImage()) {
            return false;
        }

        $workingCopy = $this->temporaryPath(
            $this->extensionFromMedia($media)
        );

        try {
            $this->decryptStoredFileToPath($media->disk, $media->path, $workingCopy, $this->isEncryptedMedia($media));
            $this->optimizeImageFile($workingCopy, $options);
            $this->encryptFileToStorage($workingCopy, $media->disk, $media->path);

            [$width, $height] = getimagesize($workingCopy) ?: [null, null];
            $metadata = $media->metadata ?? [];
            $metadata['optimized_at'] = now()->toISOString();

            $media->forceFill([
                'size' => filesize($workingCopy) ?: $media->size,
                'width' => $width,
                'height' => $height,
                'metadata' => $metadata,
            ])->save();

            if (($options['regenerate_variants'] ?? true) === true) {
                $this->regenerateVariantsFromSource(
                    $media,
                    $workingCopy,
                    (bool) data_get($media->metadata, 'generate_sizes', true)
                );
            }

            return true;
        } catch (Throwable $exception) {
            logger()->error('Image optimization failed.', [
                'media_id' => $media->id,
                'error' => $exception->getMessage(),
            ]);

            return false;
        } finally {
            $this->deleteTemporaryFile($workingCopy);
        }
    }

    public function createThumbnail(Media $media, int $width = 300, int $height = 300): ?string
    {
        if (! $media->isImage()) {
            return null;
        }

        $variantName = "custom_{$width}x{$height}";
        $variantPath = $this->buildVariantPath($media, $variantName);
        $originalPath = $this->temporaryPath($this->extensionFromMedia($media));
        $variantTempPath = $this->temporaryPath($this->extensionFromMedia($media));

        try {
            $this->decryptStoredFileToPath($media->disk, $media->path, $originalPath, $this->isEncryptedMedia($media));

            $image = $this->prepareSourceImage($media, $originalPath);
            $image->cover($width, $height)->save($variantTempPath, quality: 85);

            $this->encryptFileToStorage($variantTempPath, $media->disk, $variantPath);

            return $variantPath;
        } finally {
            $this->deleteTemporaryFile($originalPath);
            $this->deleteTemporaryFile($variantTempPath);
        }
    }

    public function stream(Media $media, ?string $variant = null): BinaryFileResponse
    {
        $variantData = $variant ? data_get($media->metadata, "variants.{$variant}") : null;
        $path = $variantData['path'] ?? $media->path;
        $mimeType = $variantData['mime_type'] ?? $media->mime_type;
        $fullPath = $this->fullPath($media->disk, $path);

        abort_unless(is_file($fullPath), 404, 'Media file not found.');

        $servedPath = $this->isEncryptedMedia($media, $variantData)
            ? $this->decryptToTemporaryPath($media->disk, $path, $this->extensionFromMedia($media))
            : $fullPath;

        $response = response()->file($servedPath, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'private, max-age=3600',
            'Content-Disposition' => 'inline; filename="'.$this->downloadName($media, $variant).'"',
        ]);

        if ($servedPath !== $fullPath) {
            $response->deleteFileAfterSend(true);
        }

        return $response;
    }

    public function download(Media $media): BinaryFileResponse
    {
        $fullPath = $this->fullPath($media->disk, $media->path);
        abort_unless(is_file($fullPath), 404, 'Media file not found.');

        $servedPath = $this->isEncryptedMedia($media)
            ? $this->decryptToTemporaryPath($media->disk, $media->path, $this->extensionFromMedia($media))
            : $fullPath;

        $response = response()->download($servedPath, $this->downloadName($media));

        if ($servedPath !== $fullPath) {
            $response->deleteFileAfterSend(true);
        }

        return $response;
    }

    /**
     * Resolve a library media id to a disposable plaintext temp copy, or
     * null when the id is unknown. Keeps consumers (controllers in other
     * modules) decoupled from the Media model.
     */
    public function temporaryOriginalCopyById(int $mediaId): ?\App\Services\Dtos\TemporaryMediaCopy
    {
        $media = Media::query()->find($mediaId);

        if ($media === null) {
            return null;
        }

        return new \App\Services\Dtos\TemporaryMediaCopy(
            path: $this->temporaryOriginalCopy($media),
            filename: $media->original_filename ?: basename($media->path),
        );
    }

    /**
     * Materialize the original file as a disposable plaintext temp copy,
     * decrypting when stored encrypted-at-rest. Use when re-attaching a
     * library item elsewhere (e.g. a model's Spatie media collection) —
     * the caller (or the consumer moving the file) owns the temp file.
     */
    public function temporaryOriginalCopy(Media $media): string
    {
        $fullPath = $this->fullPath($media->disk, $media->path);
        abort_unless(is_file($fullPath), 404, 'Media file not found.');

        if ($this->isEncryptedMedia($media)) {
            return $this->decryptToTemporaryPath($media->disk, $media->path, $this->extensionFromMedia($media));
        }

        $temporaryPath = $this->temporaryPath($this->extensionFromMedia($media));
        if (! copy($fullPath, $temporaryPath)) {
            $this->deleteTemporaryFile($temporaryPath);

            throw new RuntimeException('Unable to copy media file to temporary path.');
        }

        return $temporaryPath;
    }

    protected function regenerateVariantsFromSource(Media $media, string $sourcePath, bool $generateSizes): Media
    {
        $metadata = $media->metadata ?? [];
        $this->deleteGeneratedVariantsFromMetadata($media->disk, $metadata['variants'] ?? []);

        $metadata['generate_sizes'] = $generateSizes;
        $metadata['variants'] = [];

        if ($generateSizes && $media->isImage()) {
            $metadata['variants'] = $this->generateImageVariants($media, $sourcePath);
        }

        $media->forceFill(['metadata' => $metadata])->save();

        return $media->fresh();
    }

    protected function generateImageVariants(Media $media, string $sourcePath): array
    {
        $presets = MediaSizePreset::query()->enabled()->ordered()->get();
        if ($presets->isEmpty()) {
            return [];
        }

        if (! is_file($sourcePath)) {
            return [];
        }

        $variants = [];
        $sourceImage = $this->prepareSourceImage($media, $sourcePath);

        foreach ($presets as $preset) {
            $variantPath = $this->buildVariantPath($media, $preset->name);
            $variantTempPath = $this->temporaryPath(
                $this->extensionFromMedia($media)
            );
            $variantImage = clone $sourceImage;

            match ($preset->method) {
                'crop' => $variantImage->cover($preset->width, $preset->height),
                'resize' => $variantImage->resize($preset->width, $preset->height),
                default => $variantImage->pad($preset->width, $preset->height),
            };

            try {
                $variantImage->save($variantTempPath, quality: $preset->quality);
                $this->encryptFileToStorage($variantTempPath, $media->disk, $variantPath);
                [$width, $height] = getimagesize($variantTempPath) ?: [$preset->width, $preset->height];

                $variants[$preset->name] = [
                    'path' => $variantPath,
                    'width' => $width,
                    'height' => $height,
                    'size' => filesize($variantTempPath) ?: 0,
                    'mime_type' => $media->mime_type,
                    'method' => $preset->method,
                    'quality' => $preset->quality,
                    'encrypted' => true,
                    'generated_at' => now()->toISOString(),
                ];
            } finally {
                $this->deleteTemporaryFile($variantTempPath);
            }
        }

        return $variants;
    }

    protected function prepareSourceImage(Media $media, string $sourcePath)
    {
        $sourceImage = $this->imageManager->read($sourcePath)->orient();
        $crop = data_get($media->metadata, 'crop');

        if (! is_array($crop)) {
            return $sourceImage;
        }

        return $this->applyCropToImage($sourceImage, $crop);
    }

    protected function applyCropToImage($image, array $crop)
    {
        $sourceWidth = $image->width();
        $sourceHeight = $image->height();

        $x = max(0, (int) floor((float) ($crop['x'] ?? 0)));
        $y = max(0, (int) floor((float) ($crop['y'] ?? 0)));
        $maxWidth = max(1, $sourceWidth - $x);
        $maxHeight = max(1, $sourceHeight - $y);
        $cropWidth = max(1, min((int) round((float) ($crop['width'] ?? $sourceWidth)), $maxWidth));
        $cropHeight = max(1, min((int) round((float) ($crop['height'] ?? $sourceHeight)), $maxHeight));

        $image->crop($cropWidth, $cropHeight, $x, $y);

        return $image;
    }

    protected function buildVariantPath(Media $media, string $variantName): string
    {
        $pathInfo = pathinfo($media->path);
        $directory = $pathInfo['dirname'];

        if (Str::endsWith($directory, '/original')) {
            $directory = Str::beforeLast($directory, '/original');
        }

        return $directory.'/variants/'.$variantName.'/'.$pathInfo['basename'];
    }

    protected function fullPath(string $disk, string $path): string
    {
        return Storage::disk($disk)->path($path);
    }

    protected function buildBaseDirectory(string $collectionName): string
    {
        $date = now();

        return sprintf(
            'media-library/%s/%s/%s/%s',
            $date->format('Y'),
            $date->format('m'),
            $date->format('d'),
            $collectionName
        );
    }

    protected function sanitizeCollectionName(string $collectionName): string
    {
        return Str::slug($collectionName) ?: 'general';
    }

    protected function extractDisplayTitle(UploadedFile $file, array $options): string
    {
        $title = trim((string) ($options['title'] ?? ''));

        if ($title !== '') {
            return $title;
        }

        return $this->fallbackTitleFromOriginalName($file->getClientOriginalName());
    }

    protected function fallbackTitleFromOriginalName(string $originalName): string
    {
        return trim(pathinfo($originalName, PATHINFO_FILENAME)) ?: 'media-file';
    }

    protected function generateProtectedFilename(string $displayTitle, string $extension): string
    {
        $cipherText = Crypt::encryptString($displayTitle.'|'.Str::uuid()->toString().'|'.Str::random(32));

        return hash('sha256', $cipherText).'.'.$extension;
    }

    protected function resolveMediaType(string $mimeType): string
    {
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

    protected function getImageDimensions(string $path, string $mimeType): array
    {
        if (! str_starts_with($mimeType, 'image/')) {
            return [];
        }

        if (! is_file($path)) {
            return [];
        }

        $dimensions = getimagesize($path);

        return [
            'width' => $dimensions[0] ?? null,
            'height' => $dimensions[1] ?? null,
        ];
    }

    protected function buildMetadata(
        string $displayTitle,
        string $storedPath,
        string $originalName,
        ?string $checksum,
        bool $generateSizes,
        array $extra = []
    ): array {
        return array_replace_recursive($extra, [
            'display_title' => $displayTitle,
            'checksum_sha256' => $checksum,
            'generate_sizes' => $generateSizes,
            'storage' => [
                'private' => true,
                'directory' => dirname($storedPath),
                'encrypted_at_rest' => true,
                'encryption' => [
                    'format' => 'media-library-secretstream-v1',
                    'algorithm' => 'xchacha20poly1305',
                ],
            ],
            'filename' => [
                'stored' => basename($storedPath),
                'real_title' => $displayTitle,
                'client_original_name' => $originalName,
            ],
            'variants' => [],
        ]);
    }

    protected function deleteGeneratedVariantsFromMetadata(string $disk, array $variants): void
    {
        foreach ($variants as $variant) {
            if (! is_array($variant) || empty($variant['path'])) {
                continue;
            }

            Storage::disk($disk)->delete($variant['path']);
        }
    }

    protected function sanitizeTags(array $tags): array
    {
        return collect($tags)
            ->map(fn ($tag) => trim((string) $tag))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function optimizeImageFile(string $path, array $options = []): void
    {
        $image = $this->imageManager->read($path)->orient();
        $maxWidth = (int) ($options['max_width'] ?? 2200);
        $maxHeight = (int) ($options['max_height'] ?? 2200);
        $quality = (int) ($options['quality'] ?? 85);

        $image->scaleDown($maxWidth, $maxHeight);
        $image->save($path, quality: $quality);
    }

    protected function isEncryptedMedia(Media $media, ?array $variantData = null): bool
    {
        if ($variantData !== null && array_key_exists('encrypted', $variantData)) {
            return (bool) $variantData['encrypted'];
        }

        return (bool) data_get($media->metadata, 'storage.encrypted_at_rest', false);
    }

    protected function encryptFileToStorage(string $sourcePath, string $disk, string $destinationPath): void
    {
        $input = fopen($sourcePath, 'rb');
        if ($input === false) {
            throw new RuntimeException('Unable to open source media for encryption.');
        }

        $fullDestinationPath = $this->fullPath($disk, $destinationPath);
        Storage::disk($disk)->makeDirectory(dirname($destinationPath));

        $output = fopen($fullDestinationPath, 'wb');
        if ($output === false) {
            fclose($input);

            throw new RuntimeException('Unable to create encrypted media file.');
        }

        try {
            [$state, $header] = sodium_crypto_secretstream_xchacha20poly1305_init_push(
                $this->encryptionKey()
            );

            fwrite($output, self::ENCRYPTED_MEDIA_MAGIC);
            fwrite($output, $header);

            while (! feof($input)) {
                $chunk = fread($input, self::ENCRYPTED_CHUNK_BYTES);
                if ($chunk === false) {
                    throw new RuntimeException('Unable to read media file during encryption.');
                }

                $tag = feof($input)
                    ? SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL
                    : SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_MESSAGE;

                $cipherChunk = sodium_crypto_secretstream_xchacha20poly1305_push(
                    $state,
                    $chunk,
                    '',
                    $tag
                );

                fwrite($output, pack('N', strlen($cipherChunk)));
                fwrite($output, $cipherChunk);

                if ($tag === SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL) {
                    break;
                }
            }
        } finally {
            fclose($input);
            fclose($output);
        }
    }

    protected function decryptToTemporaryPath(string $disk, string $sourcePath, string $extension): string
    {
        $temporaryPath = $this->temporaryPath($extension);

        try {
            $this->decryptStoredFileToPath($disk, $sourcePath, $temporaryPath, true);

            return $temporaryPath;
        } catch (Throwable $exception) {
            $this->deleteTemporaryFile($temporaryPath);

            throw $exception;
        }
    }

    protected function decryptStoredFileToPath(string $disk, string $sourcePath, string $destinationPath, bool $encrypted): void
    {
        $fullSourcePath = $this->fullPath($disk, $sourcePath);
        if (! is_file($fullSourcePath)) {
            throw new RuntimeException('Stored media file not found.');
        }

        if (! $encrypted) {
            if (! copy($fullSourcePath, $destinationPath)) {
                throw new RuntimeException('Unable to copy legacy media file.');
            }

            return;
        }

        $input = fopen($fullSourcePath, 'rb');
        if ($input === false) {
            throw new RuntimeException('Unable to open encrypted media file.');
        }

        $output = fopen($destinationPath, 'wb');
        if ($output === false) {
            fclose($input);

            throw new RuntimeException('Unable to create temporary decrypted media file.');
        }

        try {
            $magic = fread($input, strlen(self::ENCRYPTED_MEDIA_MAGIC));
            if ($magic !== self::ENCRYPTED_MEDIA_MAGIC) {
                throw new RuntimeException('Encrypted media header is invalid.');
            }

            $header = fread($input, SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES);
            if ($header === false || strlen($header) !== SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES) {
                throw new RuntimeException('Encrypted media header is incomplete.');
            }

            $state = sodium_crypto_secretstream_xchacha20poly1305_init_pull(
                $header,
                $this->encryptionKey()
            );

            while (! feof($input)) {
                $lengthBytes = fread($input, 4);
                if ($lengthBytes === false) {
                    throw new RuntimeException('Unable to read encrypted media chunk length.');
                }

                if ($lengthBytes === '') {
                    break;
                }

                if (strlen($lengthBytes) !== 4) {
                    throw new RuntimeException('Encrypted media chunk length is invalid.');
                }

                $chunkLength = unpack('Nlength', $lengthBytes)['length'] ?? 0;
                if ($chunkLength <= 0) {
                    throw new RuntimeException('Encrypted media chunk length is invalid.');
                }

                $cipherChunk = '';
                while (strlen($cipherChunk) < $chunkLength) {
                    $part = fread($input, $chunkLength - strlen($cipherChunk));
                    if ($part === false || $part === '') {
                        throw new RuntimeException('Encrypted media chunk is incomplete.');
                    }

                    $cipherChunk .= $part;
                }

                $decrypted = sodium_crypto_secretstream_xchacha20poly1305_pull(
                    $state,
                    $cipherChunk
                );

                if ($decrypted === false) {
                    throw new RuntimeException('Encrypted media authentication failed.');
                }

                [$plainChunk, $tag] = $decrypted;
                fwrite($output, $plainChunk);

                if ($tag === SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL) {
                    break;
                }
            }
        } finally {
            fclose($input);
            fclose($output);
        }
    }

    protected function encryptionKey(): string
    {
        $appKey = (string) config('app.key');
        if (str_starts_with($appKey, 'base64:')) {
            $decoded = base64_decode(Str::after($appKey, 'base64:'), true);
            $appKey = $decoded !== false ? $decoded : Str::after($appKey, 'base64:');
        }

        return sodium_crypto_generichash(
            $appKey,
            '',
            SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_KEYBYTES
        );
    }

    protected function temporaryPath(string $extension = 'tmp'): string
    {
        $basePath = tempnam(sys_get_temp_dir(), 'media-');
        if ($basePath === false) {
            throw new RuntimeException('Unable to create temporary media file.');
        }

        $extension = trim($extension, '.');
        if ($extension === '') {
            return $basePath;
        }

        $extendedPath = $basePath.'.'.$extension;
        if (! rename($basePath, $extendedPath)) {
            @unlink($basePath);

            throw new RuntimeException('Unable to prepare temporary media file.');
        }

        return $extendedPath;
    }

    protected function copyToTemporaryPath(string $sourcePath, string $extension): string
    {
        $temporaryPath = $this->temporaryPath($extension);
        if (! copy($sourcePath, $temporaryPath)) {
            $this->deleteTemporaryFile($temporaryPath);

            throw new RuntimeException('Unable to prepare uploaded media for processing.');
        }

        return $temporaryPath;
    }

    protected function deleteTemporaryFile(?string $path): void
    {
        if ($path && is_file($path)) {
            @unlink($path);
        }
    }

    protected function extensionFromMedia(Media $media): string
    {
        $extension = strtolower(pathinfo($media->original_filename, PATHINFO_EXTENSION));

        if ($extension !== '') {
            return $extension;
        }

        return strtolower(pathinfo($media->path, PATHINFO_EXTENSION) ?: 'bin');
    }

    protected function downloadName(Media $media, ?string $variant = null): string
    {
        $title = trim((string) data_get($media->metadata, 'display_title', $this->fallbackTitleFromOriginalName($media->original_filename)));
        $slug = Str::slug($title) ?: 'media-'.$media->id;
        $extension = strtolower(pathinfo($media->original_filename, PATHINFO_EXTENSION) ?: pathinfo($media->path, PATHINFO_EXTENSION));

        return $variant ? "{$slug}-{$variant}.{$extension}" : "{$slug}.{$extension}";
    }
}
