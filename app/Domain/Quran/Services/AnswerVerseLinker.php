<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

/**
 * Turns the verse references that appear in a grounded AI answer into structured,
 * linkable data pointing at our own surah reader (a "go to verse" link).
 *
 * References come from two places, both parsed the same way:
 *   1. Inline markers the model is told to emit, e.g. `[[2:255]]` / `[[2:255-257]]`.
 *   2. The grounding trail (citation tool args `ayahs`), e.g. `fetch_tafsir(2:255)`.
 *
 * The canonical link is `/quran/{surah}#v{ayah}` — the same anchor the surah
 * reader assigns to each verse card and that the inquiry search already uses.
 */
final class AnswerVerseLinker
{
    /** Hard cap on the referenced-verses list so a runaway answer can't bloat the payload. */
    private const MAX_VERSES = 12;

    /**
     * Canonical "go to verse" URL on our own surah reader.
     */
    public function verseUrl(int $surah, int $ayah): string
    {
        return url("/quran/{$surah}")."#v{$ayah}";
    }

    /**
     * Ordered, de-duplicated list of the verses an answer references, ready for
     * the "referenced verses" UI section and for storage as structured JSON.
     *
     * @param  list<array{tool?: string, args?: array<string, mixed>}>  $citations
     * @return list<array{key: string, surah: int, ayah: int, url: string}>
     */
    public function fromAnswer(string $answer, array $citations = []): array
    {
        $refs = $this->parse($answer);

        foreach ($citations as $citation) {
            $ayahs = $citation['args']['ayahs'] ?? null;
            if (is_string($ayahs)) {
                $refs = [...$refs, ...$this->parse($ayahs)];
            }
        }

        $seen = [];
        $verses = [];
        foreach ($refs as [$surah, $ayah]) {
            $key = "{$surah}:{$ayah}";
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $verses[] = [
                'key' => $key,
                'surah' => $surah,
                'ayah' => $ayah,
                'url' => $this->verseUrl($surah, $ayah),
            ];

            if (count($verses) >= self::MAX_VERSES) {
                break;
            }
        }

        return $verses;
    }

    /**
     * Extract every `surah:ayah` reference from a string. Accepts the inline
     * marker form (`[[2:255]]`), a range (`2:255-257` → the start ayah), and
     * comma-separated lists. Out-of-range references are dropped.
     *
     * @return list<array{0: int, 1: int}>
     */
    private function parse(string $text): array
    {
        if (! preg_match_all('/(\d{1,3})\s*:\s*(\d{1,3})/', $text, $matches, PREG_SET_ORDER)) {
            return [];
        }

        $refs = [];
        foreach ($matches as $match) {
            $surah = (int) $match[1];
            $ayah = (int) $match[2];

            if ($surah < 1 || $surah > 114 || $ayah < 1) {
                continue;
            }

            $refs[] = [$surah, $ayah];
        }

        return $refs;
    }
}
