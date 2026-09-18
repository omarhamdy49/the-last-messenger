<?php

declare(strict_types=1);

namespace App\Domain\Quran\Import\Reciters;

use App\Domain\Quran\DTOs\AudioFileData;
use App\Domain\Quran\DTOs\ReciterData;
use App\Domain\Quran\DTOs\ReciterPreview;
use App\Domain\Quran\DTOs\TimestampRowData;
use App\Domain\Quran\Enums\ResourceKind;

/**
 * One external audio source (Quran Foundation or mp3quran) addressed by the source's
 * own reference id. Implementations throw InvalidArgumentException for an unknown id.
 */
interface ReciterFetcher
{
    public function lookup(string $refId, ResourceKind $kind): ReciterPreview;

    public function data(string $refId, ResourceKind $kind): ReciterData;

    /**
     * Chapter files (target = chapter number) or ayah files (target = verse key), absolute URLs.
     *
     * @return iterable<AudioFileData>
     */
    public function files(string $refId, ResourceKind $kind): iterable;

    /**
     * Verse timings of one chapter, in chapter order. Empty when the source has none.
     *
     * @return list<TimestampRowData>
     */
    public function timestamps(string $refId, int $chapter): array;
}
