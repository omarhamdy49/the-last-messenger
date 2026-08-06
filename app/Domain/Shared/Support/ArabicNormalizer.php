<?php

declare(strict_types=1);

namespace App\Domain\Shared\Support;

/**
 * Folds Arabic text into a search-comparable form.
 *
 * This exact function is used in TWO places that must never diverge:
 *   1. writing `muslims.search_name_*` and `hadiths.search_*` at import and on
 *      every admin save;
 *   2. folding an incoming search query before it reaches MATCH().
 *
 * If the two sides ever fold differently, diacritic-insensitive search fails in
 * one direction only — queries with diacritics stop matching stored text that
 * had them stripped — and that failure is silent. Hence one class, one method.
 *
 * Normalising at query time instead (MATCH(strip(text_ar))) is not an option:
 * a function on an indexed column is not SARGable and defeats FULLTEXT entirely
 * (constitution II).
 */
final class ArabicNormalizer
{
    /**
     * Tashkeel (harakat), Quranic annotation marks, and the tatweel elongation
     * character. Stripped rather than folded: they carry pronunciation, not
     * identity, and a reader searching "محمد" must match "مُحَمَّد".
     */
    private const DIACRITICS = '/[\x{0610}-\x{061A}\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}\x{0640}]/u';

    /**
     * Letter-variant folding. Arabic orthography writes the same word several
     * ways — hamza seats vary by writer and era, final ya and alef maqsura are
     * routinely interchanged, and ta marbuta is often typed as ha.
     */
    private const LETTER_FOLDING = [
        'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ٱ' => 'ا', 'ٲ' => 'ا', 'ٳ' => 'ا',
        'ؤ' => 'و',
        'ئ' => 'ي', 'ى' => 'ي', 'ی' => 'ي',
        'ة' => 'ه',
        'ك' => 'ك', 'ک' => 'ك',
        'ﻻ' => 'لا', 'ﻷ' => 'لا', 'ﻹ' => 'لا', 'ﻵ' => 'لا',
    ];

    /**
     * Arabic-Indic digits fold to ASCII so "٢" and "2" are one token.
     */
    private const DIGIT_FOLDING = [
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
    ];

    public function normalize(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        $text = preg_replace(self::DIACRITICS, '', $text) ?? $text;
        $text = strtr($text, self::LETTER_FOLDING);
        $text = strtr($text, self::DIGIT_FOLDING);

        // Punctuation becomes whitespace rather than vanishing: "عبد-الله"
        // must fold to two tokens, not the single token "عبدالله".
        $text = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim(mb_strtolower($text, 'UTF-8'));
    }

    /**
     * Fold several fields into one search column. Used for `search_name_*`,
     * which is built from every retained name field (name + kunya) so a reader
     * searching a kunya finds the person.
     *
     * @param  array<int, string|null>  $parts
     */
    public function normalizeParts(array $parts): ?string
    {
        $normalized = [];

        foreach ($parts as $part) {
            $value = $this->normalize($part);
            if ($value !== null && $value !== '') {
                $normalized[] = $value;
            }
        }

        if ($normalized === []) {
            return null;
        }

        // Deduplicate: a name repeated inside a kunya would otherwise inflate
        // the FULLTEXT relevance score for no reason.
        return implode(' ', array_unique(explode(' ', implode(' ', $normalized))));
    }
}
