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

    /**
     * Printed-mushaf layout for a surah, shaped for line-by-line rendering:
     * an ordered list of pages, each an ordered list of lines, each an ordered
     * list of words. Mirrors the printed mushaf (which word sits on which line
     * and page). Empty array on failure so the reader can fall back gracefully.
     *
     * @return list<array{
     *     page: int,
     *     lines: list<array{line: int, words: list<array{text: string, type: string, verse: int, location: string}>}>
     * }>
     */
    public function getMushafLayout(int $surahNumber): array
    {
        try {
            $words = $this->verseRepository->getMushafWordsBySurah($surahNumber);
        } catch (Throwable) {
            return [];
        }

        // Group the already-ordered words (page → line → verse → position) into
        // nested page/line maps, then re-index to ordered lists for the view.
        $pages = [];
        foreach ($words as $word) {
            $pages[$word->page_number]['page'] = $word->page_number;
            $pages[$word->page_number]['lines'][$word->line_number]['line'] = $word->line_number;
            $pages[$word->page_number]['lines'][$word->line_number]['words'][] = [
                'text' => $word->text_uthmani,
                'type' => $word->char_type,
                'verse' => $word->verse_number,
                'location' => $word->location,
            ];
        }

        return array_values(array_map(static function (array $page): array {
            $page['lines'] = array_values($page['lines']);

            return $page;
        }, $pages));
    }
}
