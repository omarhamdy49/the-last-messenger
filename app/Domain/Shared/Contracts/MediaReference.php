<?php

declare(strict_types=1);

namespace App\Domain\Shared\Contracts;

/**
 * How a domain module refers to a media-library item without importing
 * `App\Models\Media`.
 *
 * `url` is the public stream URL (null when the file has no public key and so
 * cannot be served to visitors); `thumbnailUrl` is the admin-only preview used
 * by the editing form.
 */
final readonly class MediaReference
{
    public function __construct(
        public int $id,
        public string $type,
        public ?string $mimeType,
        public string $title,
        public ?string $url,
        public ?string $thumbnailUrl,
    ) {}
}
