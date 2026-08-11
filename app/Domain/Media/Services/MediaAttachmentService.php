<?php

declare(strict_types=1);

namespace App\Domain\Media\Services;

use App\Services\MediaService;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\HasMedia;

/**
 * Single entry point for attaching an admin-picked image to a model's Spatie
 * media collection (program poster/banner, episode thumbnail, live-channel
 * thumbnail).
 *
 * The picker offers two mutually exclusive sources: a direct upload, or an id
 * from the media library. Library originals are encrypted at rest, so they are
 * materialized as a plaintext temp copy first — and copied rather than linked,
 * so deleting the library item never breaks the model's image.
 */
final class MediaAttachmentService
{
    public function __construct(
        private readonly MediaService $mediaService,
    ) {}

    /**
     * Replace $collection with the given source. No-op when $source is null,
     * so callers can pass optional form fields straight through.
     *
     * @param  UploadedFile|int|null  $source  Uploaded file, or media-library id
     */
    public function attach(HasMedia $model, string $collection, UploadedFile|int|null $source): void
    {
        if ($source === null) {
            return;
        }

        $model->clearMediaCollection($collection);

        if ($source instanceof UploadedFile) {
            // Register the upload in the media library first — the library
            // works on its own temp copy, so the original file is still in
            // place for Spatie's addMedia() (which moves it) afterwards.
            // Without this, direct uploads were attached to the model but
            // never appeared in the media library.
            try {
                $this->mediaService->upload($source, ['collection_name' => $collection]);
            } catch (\Throwable $e) {
                report($e); // library registration is best-effort; the attach below must still happen
            }

            $model->addMedia($source)->toMediaCollection($collection);

            return;
        }

        // addMedia() moves the temp copy into the model's collection.
        $copy = $this->mediaService->temporaryOriginalCopyById($source);

        if ($copy !== null) {
            $model->addMedia($copy->path)
                ->usingFileName($copy->filename)
                ->toMediaCollection($collection);
        }
    }
}
