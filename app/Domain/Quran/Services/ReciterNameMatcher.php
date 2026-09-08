<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use Normalizer;

/**
 * Matches our Arabic reciter names against an external catalogue's spelling.
 *
 * mp3quran.net writes names the way a reader types them, not the way we store
 * them: "عبدالباسط عبدالصمد" for our "عبد الباسط عبد الصمد", "شيخ أبو بكر
 * الشاطري" for our "أبو بكر الشاطري". Almost every real difference is spacing,
 * an honorific, or a hamza/ya/ta-marbuta variant, so normalisation does most of
 * the work and the scoring only has to break the remaining ties.
 *
 * Deliberately conservative: a wrong match writes another qari's audio under a
 * reciter's name, which is worse than leaving the row empty. Anything under
 * {@see AUTO_THRESHOLD} is reported for a human instead of written.
 */
final class ReciterNameMatcher
{
    /** At or above this, the pair is the same person — safe to write. */
    public const AUTO_THRESHOLD = 0.85;

    /** Between this and AUTO_THRESHOLD: plausible, needs a human. Below: not a match. */
    public const REVIEW_THRESHOLD = 0.50;

    /**
     * Words that carry no identity: honorifics, and the lineage particle that
     * one catalogue writes and the other omits ("مشاري بن راشد" / "مشاري راشد").
     */
    private const NOISE_WORDS = ['الشيخ', 'القارئ', 'الدكتور', 'شيخ', 'بن'];

    /** Harakat, dagger alif and Quranic annotation marks. */
    private const DIACRITICS = '/[\x{064B}-\x{0652}\x{0670}\x{06D6}-\x{06ED}]/u';

    /** Anything parenthesised is a style note — "(مجود)", "(ورش)" — never part of the name. */
    private const BRACKETED = '/[\(\[\{][^\)\]\}]*[\)\]\}]?/u';

    /** Arabic letters only; digits, latin and punctuation are noise here. */
    private const NON_ARABIC = '/[^\x{0621}-\x{063A}\x{0641}-\x{064A}\s]/u';

    private const LETTER_FOLDING = [
        'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ٱ' => 'ا',
        'ى' => 'ي', 'ئ' => 'ي', 'ة' => 'ه', 'ؤ' => 'و',
        'ـ' => '',
    ];

    /**
     * Reduce a name to comparable Arabic words. Returns '' when the name holds
     * no Arabic at all — recited translations ("Besim Korkut (Bosnian)") are not
     * in the catalogue and must never be forced onto a qari.
     */
    public static function normalize(string $name): string
    {
        $name = Normalizer::normalize($name, Normalizer::FORM_KC) ?: $name;
        $name = preg_replace(self::DIACRITICS, '', $name);
        $name = preg_replace(self::BRACKETED, ' ', $name);
        $name = strtr($name, self::LETTER_FOLDING);
        $name = preg_replace(self::NON_ARABIC, ' ', $name);

        $words = array_diff(
            preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [],
            self::NOISE_WORDS,
        );

        return implode(' ', $words);
    }

    /**
     * 0.0 (unrelated) to 1.0 (same name). See the class docblock for the bands.
     */
    public static function score(string $ours, string $theirs): float
    {
        return self::compare($ours, $theirs)['score'];
    }

    /**
     * Rank catalogue entries against one of our names, best first.
     *
     * Ranked on word overlap before the fuzzy score so the shortlist printed for
     * an ambiguous reciter shows the plausible namesakes rather than whatever
     * string happens to share the most letters.
     *
     * @param  iterable<array{id: int, name: string}>  $candidates
     * @return list<array{id: int, name: string, score: float}>
     */
    public static function rank(string $ours, iterable $candidates, int $limit = 3): array
    {
        $ranked = [];

        foreach ($candidates as $candidate) {
            $result = self::compare($ours, $candidate['name']);

            if ($result['score'] <= 0.0) {
                continue;
            }

            $ranked[] = [
                'id' => $candidate['id'],
                'name' => $candidate['name'],
                'score' => $result['score'],
                'overlap' => $result['overlap'],
            ];
        }

        usort($ranked, fn (array $a, array $b) => [$b['overlap'], $b['score']] <=> [$a['overlap'], $a['score']]);

        return array_map(
            static fn (array $r) => ['id' => $r['id'], 'name' => $r['name'], 'score' => $r['score']],
            array_slice($ranked, 0, $limit),
        );
    }

    /**
     * @return array{score: float, overlap: float}
     */
    private static function compare(string $ours, string $theirs): array
    {
        $a = self::normalize($ours);
        $b = self::normalize($theirs);

        if ($a === '' || $b === '') {
            return ['score' => 0.0, 'overlap' => 0.0];
        }

        // Spacing is the single most common difference between the two
        // catalogues, so compare with it removed before anything else.
        $flatA = str_replace(' ', '', $a);
        $flatB = str_replace(' ', '', $b);

        if ($flatA === $flatB) {
            return ['score' => 1.0, 'overlap' => 1.0];
        }

        $overlap = self::wordOverlap($a, $b);

        [$short, $long] = mb_strlen($flatA) <= mb_strlen($flatB) ? [$flatA, $flatB] : [$flatB, $flatA];

        // One name containing the other is only meaningful when the extra part
        // is small. "ياسين" sits inside "ياسينالجزائري" and they are two people.
        if (str_contains($long, $short) && mb_strlen($short) >= 0.6 * mb_strlen($long)) {
            return ['score' => 0.9, 'overlap' => $overlap];
        }

        // Edit distance catches the cases word overlap cannot: a name split
        // differently ("بوخاطر" / "بو خاطر") shares no whole word yet is close.
        // It only ever lifts a pair into the review band, never past it.
        $distance = levenshtein($flatA, $flatB);
        $longest = max(strlen($flatA), strlen($flatB));
        $fuzzy = $longest > 0 ? 1 - ($distance / $longest) : 0.0;

        return [
            'score' => round(max($overlap, min($fuzzy, self::AUTO_THRESHOLD - 0.01)), 4),
            'overlap' => $overlap,
        ];
    }

    /**
     * Shared words over the longer name's word count. The definite article is
     * dropped from words long enough to survive without it, since the two
     * catalogues disagree on it ("الطبلاوي" / "طبلاوي").
     */
    private static function wordOverlap(string $a, string $b): float
    {
        $wordsA = self::words($a);
        $wordsB = self::words($b);

        if ($wordsA === [] || $wordsB === []) {
            return 0.0;
        }

        return count(array_intersect($wordsA, $wordsB)) / max(count($wordsA), count($wordsB));
    }

    /**
     * @return list<string>
     */
    private static function words(string $normalized): array
    {
        $words = [];

        foreach (preg_split('/\s+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $word) {
            if (mb_strlen($word) > 4 && str_starts_with($word, 'ال')) {
                $word = mb_substr($word, 2);
            }

            $words[$word] = true;
        }

        return array_keys($words);
    }
}
