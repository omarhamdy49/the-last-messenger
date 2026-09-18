<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use App\Domain\Quran\DTOs\ReaderOptions;
use App\Domain\Quran\Enums\SectionType;
use App\Domain\Quran\Models\Section;
use App\Domain\Quran\Models\Verse;
use App\Domain\Quran\Repositories\SectionRepositoryInterface;
use App\Domain\Quran\Repositories\VerseRepositoryInterface;
use App\Domain\Quran\Support\ArabicNormalizer;
use Illuminate\Support\Facades\Cache;

/**
 * Quran divisions and "where do I go" answers: sections, quick navigation from a typed
 * query (2:255 · 2 · juz 3 · page 50 · a chapter name), random verse, verse of the day.
 */
final class NavigationService
{
    public function __construct(
        private readonly SectionRepositoryInterface $sections,
        private readonly VerseRepositoryInterface $verses,
        private readonly ReaderService $reader,
    ) {}

    /**
     * @return list<array{number: int, first_verse_id: int, last_verse_id: int, verses_count: int, verse_mapping: array<string, string>}>
     */
    public function sections(SectionType $type): array
    {
        return $this->sections->ofType($type)->map(static fn (Section $s): array => [
            'number' => $s->number,
            'first_verse_id' => $s->first_verse_id,
            'last_verse_id' => $s->last_verse_id,
            'verses_count' => $s->verses_count,
            'verse_mapping' => $s->verse_mapping,
        ])->values()->all();
    }

    public function section(SectionType $type, int $number): ?Section
    {
        return $this->sections->find($type, $number);
    }

    /**
     * Typed-query navigation hits, QF search `navigation[]` shape.
     *
     * @return list<array{result_type: string, key: string, name: string, url: string}>
     */
    public function resolveQuick(string $query, string $locale, int $limit = 5): array
    {
        $query = trim(ArabicNormalizer::digits($query));
        if ($query === '') {
            return [];
        }

        $chapters = collect($this->reader->chapterList($locale))->keyBy('number');
        $hits = [];

        if (($key = Verse::parseKey(str_replace(' ', '', $query))) !== null) {
            [$c, $v] = $key;
            if (isset($chapters[$c]) && $v >= 1 && $v <= $chapters[$c]['verses']) {
                $hits[] = ['result_type' => 'ayah', 'key' => "{$c}:{$v}", 'name' => $chapters[$c]['nameAr']." ({$c}:{$v})",
                    'url' => route('public.quran.surah', $c).'#v'.$v];
            }
        }

        if (ctype_digit($query) && isset($chapters[(int) $query])) {
            $c = $chapters[(int) $query];
            $hits[] = ['result_type' => 'surah', 'key' => (string) $c['number'], 'name' => $c['nameAr'], 'url' => route('public.quran.surah', $c['number'])];
        }

        $patterns = [
            'juz' => ['/^(?:juz|جزء|الجزء)\s*(\d{1,2})$/iu', 30, 'public.quran.juz'],
            'hizb' => ['/^(?:hizb|حزب|الحزب)\s*(\d{1,2})$/iu', 60, 'public.quran.hizb'],
            'page' => ['/^(?:page|صفحة|الصفحة)\s*(\d{1,3})$/iu', 604, 'public.quran.page'],
        ];
        foreach ($patterns as $type => [$regex, $max, $route]) {
            if (preg_match($regex, $query, $m) === 1 && (int) $m[1] >= 1 && (int) $m[1] <= $max) {
                $hits[] = ['result_type' => $type, 'key' => $m[1], 'name' => __('quran.nav.'.$type).' '.$m[1], 'url' => route($route, (int) $m[1])];
            }
        }

        $needle = ArabicNormalizer::normalize($query);
        if (mb_strlen($needle) >= 2) {
            foreach ($chapters as $c) {
                foreach ([$c['nameAr'], $c['nameEn'], $c['transliteration']] as $name) {
                    if (str_contains(ArabicNormalizer::normalize((string) $name), $needle)) {
                        $hits[] = ['result_type' => 'surah', 'key' => (string) $c['number'], 'name' => $c['nameAr'], 'url' => route('public.quran.surah', $c['number'])];
                        break;
                    }
                }
            }
        }

        return array_slice(array_values(array_unique($hits, SORT_REGULAR)), 0, $limit);
    }

    /**
     * @return array{0: int, 1: int}|null chapter, verse number — only for a verse that exists
     */
    public function verseLocation(string $key): ?array
    {
        $parsed = Verse::parseKey($key);

        return $parsed !== null && $this->verses->byKey($key) !== null ? $parsed : null;
    }

    public function randomVerseKey(): ?string
    {
        return $this->verses->random([])?->verse_key;
    }

    /**
     * One verse per day per locale (same for every visitor).
     *
     * @return array{verse_key: string, text: string, translation: ?string, chapter: int, verse: int, chapter_name: string}|null
     */
    public function verseOfTheDay(string $locale): ?array
    {
        return Cache::remember(QuranCacheKeys::verseOfTheDay(now()->toDateString(), $locale), 86400, function () use ($locale): ?array {
            $list = $this->reader->chapterList($locale);
            $total = array_sum(array_column($list, 'verses'));
            if ($total === 0) {
                return null;
            }

            // Deterministic per day: everyone sees the same verse, no random query per request.
            $index = crc32(now()->toDateString()) % $total;
            foreach ($list as $chapter) {
                if ($index < $chapter['verses']) {
                    break;
                }
                $index -= $chapter['verses'];
            }
            // The text columns are not in the base select, so ask for the script:
            // without it the verse of the day rendered as an empty card.
            $verse = $this->verses->byKey($chapter['number'].':'.($index + 1), ['text_uthmani']);
            if ($verse === null) {
                return null;
            }

            // Arabic readers get the English rendering as the sub line, which is what
            // the home panel shows beneath the ayah.
            $default = $this->reader->defaultTranslation($locale === 'ar' ? 'en' : $locale);
            $translation = null;
            if ($default !== null) {
                $parts = $this->verses->payload([$verse->id], new ReaderOptions(translationIds: [$default->id]));
                $translation = ReaderService::plain($parts[$verse->id]['translations'][$default->id]['text'] ?? null) ?: null;
            }

            return [
                'verse_key' => $verse->verse_key,
                'text' => (string) $verse->text_uthmani,
                'translation' => $translation,
                'chapter' => $verse->chapter_id,
                'verse' => $verse->verse_number,
                'chapter_name' => $chapter['nameAr'],
            ];
        });
    }
}
