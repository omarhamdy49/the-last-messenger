<?php

declare(strict_types=1);

namespace App\Domain\Shared\Models;

use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;

/**
 * Spatie MediaLibrary attachment model.
 *
 * The default `media` table name is taken by the custom media-manager module,
 * so MediaLibrary attachments (program posters/banners, episode thumbnails,
 * sahabi/seerah/genealogy images) live in their own `spatie_media` table.
 */
class SpatieMedia extends BaseMedia
{
    protected $table = 'spatie_media';
}
