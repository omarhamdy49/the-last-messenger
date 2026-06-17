<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use App\Domain\Quran\Models\Surah;
use Illuminate\Support\Facades\Cache;

/**
 * Resolves a written surah name to its mushaf number (1-114) so answer text that
 * cites a verse by name — e.g. "البقرة" / "Al-Baqara" — can be linked to the
 * surah reader. Names are normalised (diacritics, alef/ya/ta variants, the
 * "سورة" prefix and leading conjunctions stripped) so loose spellings still match.
 *
 * The map is built once from the surahs table and cached.
 */
final class SurahNameIndex
{
    /**
     * @return int|null The surah number, or null when the name is not recognised.
     */
    public function resolve(string $name): ?int
    {
        $key = $this->normalize($name);

        return $this->map()[$key] ?? null;
    }

    /**
     * Normalised Arabic/English name => surah number.
     *
     * @return array<string, int>
     */
    private function map(): array
    {
        /** @var array<string, int> $map */
        $map = Cache::rememberForever(QuranCacheKeys::surahNameIndex(), function (): array {
            $map = [];
            foreach (Surah::query()->get(['id', 'name_ar', 'name_en', 'transliteration']) as $surah) {
                $number = (int) $surah->id;
                foreach ([$surah->name_ar, $surah->name_en, $surah->transliteration] as $name) {
                    $normalized = $this->normalize((string) $name);
                    if ($normalized !== '') {
                        $map[$normalized] = $number;
                    }
                }
            }

            return $map;
        });

        return $map;
    }

    /**
     * Strip diacritics/tatweel, fold alef/ya/ta-marbuta variants, drop the
     * "سورة" prefix and a leading conjunction, lowercase, and collapse spaces.
     */
    private function normalize(string $name): string
    {
        // Strip every Arabic combining mark — harakat AND the Uthmani Quranic
        // annotation signs the surah names carry (e.g. U+06E1) — plus tatweel.
        // \p{Mn} (nonspacing marks) covers all of them in one pass.
        $clean = preg_replace('/[\p{Mn}\x{0640}]/u', '', $name) ?? $name;

        $clean = strtr($clean, [
            'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ٱ' => 'ا',
            'ى' => 'ي', 'ئ' => 'ي', 'ؤ' => 'و', 'ة' => 'ه',
            '-' => ' ', '_' => ' ', '’' => '', "'" => '',
        ]);

        $clean = mb_strtolower(trim($clean));
        $clean = preg_replace('/^(سوره|سورت)\s+/u', '', $clean) ?? $clean; // "سورة X" -> "X"
        $clean = preg_replace('/^(و|ف)(?=ال)/u', '', $clean) ?? $clean;     // "والعنكبوت" -> "العنكبوت"
        $clean = preg_replace('/\s+/u', ' ', $clean) ?? $clean;

        return trim($clean);
    }
}
