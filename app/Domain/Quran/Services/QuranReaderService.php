<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use App\Domain\Quran\Repositories\RecitationRepositoryInterface;
use App\Domain\Quran\Repositories\TafseerRepositoryInterface;
use App\Domain\Quran\Repositories\VerseRepositoryInterface;
use Throwable;

/**
 * Read model for the public Quran reader page.
 *
 * Provides the presentation-shaped, plain-array data the Site controller needs
 * (audio URLs, translation/tafsir edition lists, and lazily-loaded edition text)
 * so the controller never touches Eloquent directly. Each method degrades to an
 * empty result on failure to keep the reader resilient; repositories own the
 * queries and caching.
 */
final class QuranReaderService
{
    public function __construct(
        private readonly RecitationRepositoryInterface $recitationRepository,
        private readonly TafseerRepositoryInterface $tafseerRepository,
        private readonly VerseRepositoryInterface $verseRepository,
    ) {}

    /**
     * Per-surah recitation URLs keyed by reciter id (surah-level audio player).
     *
     * @return array<int, string>
     */
    public function getSurahAudioUrls(int $surahNumber): array
    {
        try {
            return $this->recitationRepository->getSurahAudioUrls($surahNumber);
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Active tafsir editions for the reader's source selector, with names
     * localized to the given locale.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getTafsirResources(string $locale): array
    {
        try {
            return $this->tafseerRepository->getActiveResources()
                ->map(fn ($r) => [
                    'id' => $r->id,
                    'name' => $r->getName($locale),
                    'language' => $r->language,
                ])
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Active translation editions for the reader's translation selector, with
     * names localized to the given locale.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getTranslationResources(string $locale): array
    {
        try {
            return $this->verseRepository->getActiveTranslationResources()
                ->map(fn ($r) => [
                    'id' => $r->id,
                    'name' => $r->getName($locale),
                    'language' => $r->language,
                    'direction' => $r->direction,
                    'is_default' => $r->is_default,
                ])
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * One verse's tafsir text for a single edition (per-verse lazy load), or ''
     * when the edition has no tafsir for that verse.
     */
    public function getVerseTafsir(int $verseId, int $resourceId): string
    {
        try {
            return $this->tafseerRepository->getVerseText($verseId, $resourceId) ?? '';
        } catch (Throwable) {
            return '';
        }
    }

    /**
     * One translation edition's text for a surah, keyed by verse id (lazy load).
     *
     * @return array<int, string>
     */
    public function getSurahTranslation(int $surahNumber, int $resourceId): array
    {
        try {
            return $this->verseRepository->getSurahTranslationByResource($surahNumber, $resourceId);
        } catch (Throwable) {
            return [];
        }
    }
}
