<?php

declare(strict_types=1);

namespace App\Domain\Blog\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

/**
 * Stores an article thumbnail on the public disk together with sized WebP
 * copies, so public pages can serve an image matched to its render size
 * instead of the multi-megabyte original.
 *
 * Derived copies live next to the original as "{path}@{size}.webp". URL
 * resolution (Article::thumbUrl) falls back to the original when a derived
 * copy is missing, so legacy thumbs keep working untouched.
 */
class ArticleThumbService
{
    /** Max width per derived size, from the public-page audit:
     *  card = listing/related/home cards (~420px display, retina) ;
     *  hero = article page hero, spotlight, og:image. */
    public const SIZES = ['card' => 640, 'hero' => 1280];

    private const QUALITY = 80;

    /**
     * Store the uploaded thumb and its derived sizes; returns the original's path.
     */
    public function store(UploadedFile $file): string
    {
        $path = $file->store('articles/thumbs', 'public');

        $this->generateDerived($path);

        return $path;
    }

    /**
     * Regenerate the derived copies for an already-stored thumb (backfill).
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
                $encoded = $image->encode(new WebpEncoder(quality: self::QUALITY));
                $disk->put(self::derivedPath($path, $size), (string) $encoded);
            }
        } catch (\Throwable $e) {
            // A thumb without sized copies still renders via the original.
            report($e);
        }
    }

    /**
     * Delete a stored thumb and all of its derived copies.
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

    public static function derivedPath(string $path, string $size): string
    {
        return "{$path}@{$size}.webp";
    }
}
