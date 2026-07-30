<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Services;

/**
 * Folds Arabic to a search-stable form.
 *
 * This exists because the same word is written several ways across the corpus:
 * with or without tashkeel, with alef maqsura or ya, with any of four alef
 * hamza forms, and — in text of Persian/Urdu provenance — with Farsi yeh and
 * keheh instead of the Arabic letters. A reader typing "صحيح" must find text
 * stored as "صَحِيح" or "صحیح".
 *
 * The output is written to `hadith_texts.search_body` and is also applied to a
 * search query before matching, so both sides fold identically. Display text is
 * never normalised — diacritics are part of the scripture.
 */
final class ArabicTextNormalizer
{
    /**
     * Tashkeel (U+064B–U+0652), superscript alef (U+0670), and the Quranic
     * annotation marks (U+06D6–U+06ED) that appear inside quoted verses.
     */
    private const DIACRITICS = '/[\x{064B}-\x{0652}\x{0670}\x{06D6}-\x{06ED}]/u';

    /** Tatweel is decoration only and never distinguishes two words. */
    private const TATWEEL = "\u{0640}";

    /**
     * Letter variants folded to one representative.
     *
     * Hamza carriers collapse to bare alef, ta marbuta to ha, alef maqsura to
     * ya, and the Farsi forms to their Arabic equivalents. Folding is
     * deliberately aggressive: a false match costs a reader one glance, a missed
     * match costs them the hadith.
     */
    private const LETTER_FOLDING = [
        "\u{0622}" => "\u{0627}",  // آ  alef madda
        "\u{0623}" => "\u{0627}",  // أ  alef hamza above
        "\u{0625}" => "\u{0627}",  // إ  alef hamza below
        "\u{0671}" => "\u{0627}",  // ٱ  alef wasla
        "\u{0624}" => "\u{0648}",  // ؤ  waw hamza
        "\u{0626}" => "\u{064A}",  // ئ  ya hamza
        "\u{0649}" => "\u{064A}",  // ى  alef maqsura
        "\u{06CC}" => "\u{064A}",  // ی  Farsi yeh
        "\u{06D2}" => "\u{064A}",  // ے  Urdu barree yeh
        "\u{0629}" => "\u{0647}",  // ة  ta marbuta
        "\u{06C1}" => "\u{0647}",  // ہ  Urdu heh goal
        "\u{06C3}" => "\u{0647}",  // ۃ  Urdu teh marbuta goal
        "\u{06A9}" => "\u{0643}",  // ک  keheh
        "\u{06AA}" => "\u{0643}",  // ڪ  swash kaf
    ];

    /**
     * Zero-width and directional marks. The source embeds these around quoted
     * speech; left in, they split one word into two unsearchable halves.
     */
    private const INVISIBLES = [
        "\u{200B}", "\u{200C}", "\u{200D}", "\u{200E}", "\u{200F}",
        "\u{FEFF}", "\u{061C}",
    ];

    public function normalize(string $text): string
    {
        $text = str_replace(self::INVISIBLES, '', $text);
        $text = (string) preg_replace(self::DIACRITICS, '', $text);
        $text = str_replace(self::TATWEEL, '', $text);
        $text = strtr($text, self::LETTER_FOLDING);

        // Punctuation the source uses decoratively around quotations. Dropping
        // it keeps "قال ‏"‏ إنما" and "قال إنما" equivalent.
        $text = (string) preg_replace('/[«»“”"\'‘’\x{060C}\x{061B}\x{061F}.,;:!?()\[\]{}]/u', ' ', $text);

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    /**
     * `search_body` for a text row: normalised for Arabic, unchanged for
     * Latin-script locales, where folding would do nothing useful and would
     * damage proper nouns.
     */
    public function forLocale(string $body, string $locale): string
    {
        return $locale === 'ar' ? $this->normalize($body) : trim($body);
    }
}
