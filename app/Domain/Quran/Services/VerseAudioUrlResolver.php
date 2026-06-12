<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use App\Domain\Quran\Models\Reciter;
use InvalidArgumentException;

/**
 * Verse Audio URL Resolver
 *
 * Resolves per-verse audio URLs from the everyayah.com CDN, which hosts
 * one mp3 per verse per reciter at:
 *
 *   https://everyayah.com/data/{folder}/{SSS}{AAA}.mp3
 *
 * where {SSS} is the zero-padded surah number and {AAA} the zero-padded
 * verse number (e.g. Alafasy_128kbps/001001.mp3 for Al-Fatiha 1:1).
 *
 * Per-verse audio is resolved dynamically instead of being stored: storing
 * 6,236 verses x N reciters as rows would add tens of thousands of records
 * carrying no information beyond this deterministic URL pattern. Per-surah
 * recordings (different sources/CDNs per reciter) ARE stored in
 * quran_recitations.
 */
final class VerseAudioUrlResolver
{
    private const BASE_URL = 'https://everyayah.com/data';

    private const MIN_SURAH_NUMBER = 1;

    private const MAX_SURAH_NUMBER = 114;

    /**
     * Resolve the per-verse audio URL for a reciter.
     *
     * Returns null when the reciter has no everyayah folder configured
     * (i.e. no per-verse audio is available for them).
     */
    public function resolve(Reciter $reciter, int $surahNumber, int $verseNumber): ?string
    {
        if (empty($reciter->everyayah_folder)) {
            return null;
        }

        return $this->resolveForFolder($reciter->everyayah_folder, $surahNumber, $verseNumber);
    }

    /**
     * Build the everyayah.com URL for a given folder slug, surah and verse.
     *
     * @throws InvalidArgumentException When the surah or verse number is out of range.
     */
    public function resolveForFolder(string $folder, int $surahNumber, int $verseNumber): string
    {
        if ($surahNumber < self::MIN_SURAH_NUMBER || $surahNumber > self::MAX_SURAH_NUMBER) {
            throw new InvalidArgumentException(
                "Surah number must be between 1 and 114, got {$surahNumber}."
            );
        }

        if ($verseNumber < 1) {
            throw new InvalidArgumentException(
                "Verse number must be a positive integer, got {$verseNumber}."
            );
        }

        return sprintf('%s/%s/%03d%03d.mp3', self::BASE_URL, $folder, $surahNumber, $verseNumber);
    }
}
