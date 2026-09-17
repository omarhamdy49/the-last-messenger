<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Shared\Contracts\MediaLibraryLookup;
use App\Domain\Shared\Contracts\MediaReference;
use App\Models\Media;

final class MediaLibraryLookupService implements MediaLibraryLookup
{
    public function byIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

        if ($ids === []) {
            return [];
        }

        return Media::query()
            ->select(['id', 'public_key', 'disk', 'metadata', 'filename', 'original_filename', 'path', 'mime_type', 'type', 'alt_text'])
            ->whereIn('id', $ids)
            ->get()
            ->mapWithKeys(fn (Media $media): array => [
                (int) $media->id => new MediaReference(
                    id: (int) $media->id,
                    type: (string) $media->type,
                    mimeType: $media->mime_type,
                    title: (string) ($media->alt_text ?: $media->original_filename ?: $media->filename),
                    // Same narrow public door the article reader uses: it streams
                    // only while the file is attached to published content.
                    url: filled($media->public_key)
                        ? route('public.articles.media', ['media' => $media->public_key])
                        : null,
                    thumbnailUrl: $media->thumbnail_url,
                ),
            ])
            ->all();
    }
}
