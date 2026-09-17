<?php

declare(strict_types=1);

namespace App\Domain\Shared\Contracts;

/**
 * Resolves media-library ids to {@see MediaReference} values.
 *
 * `seerah_events_media.media_id` is a foreign key into `media`, which is
 * legitimate — database keys may cross module boundaries. What may NOT cross is
 * the PHP import: constitution IV forbids a domain module importing
 * `App\Models\Media`, and deptrac enforces it. Seerah asks this contract instead,
 * and the application layer supplies the implementation.
 */
interface MediaLibraryLookup
{
    /**
     * One query regardless of how many ids are passed. Unknown ids are omitted.
     *
     * @param  array<int, int>  $ids
     * @return array<int, MediaReference> keyed by media id
     */
    public function byIds(array $ids): array;
}
