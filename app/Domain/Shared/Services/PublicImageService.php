<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

/**
 * Resolves an image that arrived either as a direct upload or as a media-library
 * pick into a path on the **public** disk, plus sized WebP copies.
 *
 * Modules that keep their image in a plain path column (series.thumbnail,
 * reciters.profile_image, seerah_events.featured_image / gallery) cannot store a
 * media-library path directly: library originals live on the private `local`
 * disk and are encrypted at rest, so Storage::url() on them yields a file the
 * browser can never read. The library id therefore has to be materialised into
 * a plaintext copy and re-stored publicly — the same route
 * Admin\Blog\ArticleController already takes for article thumbs.
 *
 * Direct uploads are registered in the library first so they inherit its
 * optimization and MediaSizePreset variants, and so every image an editor
 * uploads is findable in the picker afterwards.
 */
class PublicImageService
{
    /** Max width per derived copy; mirrors the enabled MediaSizePreset widths. */
    public const SIZES = ['card' => 640, 'hero' => 1280];

    private const QUALITY = 80;

    public function __construct(
        private readonly MediaService $mediaService,
    ) {}

    /**
     * Store an upload or a library pick under $directory on the public disk.
     *
     * @param  UploadedFile|int|string|null  $source  Upload, or a media-library id.
     * @param  string  $directory  e.g. 'series/thumbnails'
     * @param  string  $collection  Library collection name for direct uploads.
     * @return string|null Public-disk path, or null when there was nothing to store.
     */
    public function store(UploadedFile|int|string|null $source, string $directory, string $collection = 'images'): ?string
    {
        if ($source === null || $source === '' || $source === 0) {
            return null;
        }

        $file = $source instanceof UploadedFile
            ? $this->materialiseUpload($source, $collection)
            : $this->materialiseLibraryPick((int) $source);

        if ($file === null) {
            return null;
        }

        $path = $file->store($directory, 'public');

        if ($path === false) {
            return null;
        }

        $this->generateDerived($path);

        return $path;
    }

    /**
     * Regenerate the derived copies for an already-stored image (backfill).
     */
    public function generateDerived(string $path): void
    {
        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            return;
        }

        try {
            $manager = new ImageManager(new Driver);

            foreach (self::SIZES as $size => $maxWidth) {
                $image = $manager->decodePath($disk->path($path))->orient()->scaleDown($maxWidth);
                $disk->put(self::derivedPath($path, $size), (string) $image->encode(new WebpEncoder(quality: self::QUALITY)));
            }
        } catch (\Throwable $e) {
            // An image without sized copies still renders via the original.
            report($e);
        }
    }

    /**
     * Delete a stored image and all of its derived copies.
     */
    public function delete(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        $disk = Storage::disk('public');
        $disk->delete($path);

        foreach (array_keys(self::SIZES) as $size) {
            $disk->delete(self::derivedPath($path, $size));
        }
    }

    /**
     * Public URL for a render context ('card' ~640w, 'hero' ~1280w), falling
     * back to the original when no derived copy exists (legacy rows).
     */
    public static function url(?string $path, string $size = 'card'): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        $derived = self::derivedPath($path, $size);

        return Storage::disk('public')->exists($derived)
            ? asset('storage/'.$derived)
            : asset('storage/'.$path);
    }

    public static function derivedPath(string $path, string $size): string
    {
        return "{$path}@{$size}.webp";
    }

    /**
     * Register the upload in the media library (optimization + size presets)
     * and hand back a plaintext copy of what the library stored. Falls back to
     * the raw upload if the library round-trip fails, so a library outage can
     * never lose the editor's file.
     */
    private function materialiseUpload(UploadedFile $file, string $collection): ?UploadedFile
    {
        try {
            $media = $this->mediaService->upload($file, ['collection_name' => $collection]);
            $copy = $this->mediaService->temporaryOriginalCopyById($media->id);

            if ($copy !== null) {
                return new UploadedFile($copy->path, $copy->filename, null, null, true);
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return $file;
    }

    /**
     * Decrypt a library original into a temp plaintext copy we can re-store publicly.
     */
    private function materialiseLibraryPick(int $mediaId): ?UploadedFile
    {
        if (! Media::whereKey($mediaId)->exists()) {
            return null;
        }

        $copy = $this->mediaService->temporaryOriginalCopyById($mediaId);

        if ($copy === null) {
            return null;
        }

        return new UploadedFile($copy->path, $copy->filename, null, null, true);
    }
}
