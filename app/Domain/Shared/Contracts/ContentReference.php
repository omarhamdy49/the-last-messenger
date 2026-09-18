<?php

declare(strict_types=1);

namespace App\Domain\Shared\Contracts;

use App\Domain\Shared\Enums\LinkedContentType;

/**
 * A resolved, display-ready pointer to content in another module.
 *
 * `url` is the public page (null when the content has none or is not public);
 * `subtitle` is short context such as a verse key, hadith collection, or
 * programme name.
 */
final readonly class ContentReference
{
    public function __construct(
        public LinkedContentType $type,
        public int $id,
        public string $title,
        public ?string $subtitle = null,
        public ?string $url = null,
        public ?string $thumbUrl = null,
    ) {}
}
