<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Support;

/**
 * Sunnah.com marks every Quran quotation inside a hadith matn with a shortcode:
 *
 *     ...قَالَ لَمَّا نَزَلَتْ [quran sura="4" aya_start="95" aya_end="95"]{لاَ يَسْتَوِي الْقَاعِدُونَ}
 *
 * The import stores the matn verbatim — deliberately, since the marker is the
 * only record of which ayah is being quoted — and the public pages printed it
 * raw, so readers saw the shortcode itself.
 *
 * This turns the marker into either a link (`toHtml`) or nothing (`strip`).
 *
 * Shape of the corpus this was written against — 2,998 markers across 2,058
 * Arabic matns, every one of them exactly `[quran sura="N" aya_start="N"
 * aya_end="N"]`, with no closing tag and none in the English text:
 *
 *   - 2,350 are followed by the ayah in braces  `{...}`
 *   -   632 are followed by the ayah in parens  `(...)`
 *   -    16 quote nothing and just mark the position
 *   -   139 span a range (`aya_start` != `aya_end`)
 *   - RLM/LRM marks and spaces sit between the marker and the quote in ~1 in 20
 *
 * Both delimiters are kept as they were found. Re-quoting them as ornate
 * parentheses would read better, but it would be this class silently editing
 * scripture, which is not its job.
 */
final class QuranCitation
{
    /** The Mushaf is 114 surahs — a domain constant, not a DB count. */
    private const MAX_SURAH = 114;

    /**
     * The marker, plus the ayah it quotes when there is one.
     *
     * Parameterised by the quote character because `toHtml` matches against
     * already-escaped text, where `"` has become `&quot;`.
     *
     * Groups: 1 sura, 2 aya_start, 3 aya_end, 4 separator, 5 braced ayah,
     * 6 parenthesised ayah.
     */
    private static function pattern(string $quote): string
    {
        $q = preg_quote($quote, '/');

        return '/\[quran\s+sura='.$q.'(\d{1,3})'.$q
            .'\s+aya_start='.$q.'(\d{1,3})'.$q
            .'\s+aya_end='.$q.'(\d{1,3})'.$q.'\]'
            .'([\x{200E}\x{200F}\s]*)'
            .'(?:\{([^{}]*)\}|\(([^()]*)\))?/u';
    }

    /**
     * The matn as plain text, with the markers removed and the quotation left
     * exactly as it was.
     *
     * For everywhere the text must not carry markup: meta descriptions, JSON-LD,
     * the copy-to-clipboard buffer, admin list excerpts.
     */
    public static function strip(?string $text): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        // Only the marker and the run of separators behind it are dropped — the
        // space in front of it is what keeps the surrounding words apart.
        $stripped = preg_replace(
            '/\[quran\s+sura="\d{1,3}"\s+aya_start="\d{1,3}"\s+aya_end="\d{1,3}"\][\x{200E}\x{200F}\s]*/u',
            '',
            $text,
        );

        // preg_replace returns null only on a backtrack/compile failure; keeping
        // the marker is better than blanking the hadith.
        return $stripped === null ? $text : $stripped;
    }

    /**
     * The matn as HTML, with every quotation linked to its ayah in our reader.
     *
     * The text is escaped FIRST and the pattern is matched against the escaped
     * string, so there is no branch through this method that can emit an
     * unescaped character of imported third-party content.
     */
    public static function toHtml(?string $text): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        $escaped = e($text);

        $html = preg_replace_callback(
            self::pattern('&quot;'),
            static fn (array $matches): string => self::render($matches),
            $escaped,
        );

        return $html ?? $escaped;
    }

    /**
     * One marker, rendered.
     *
     * @param  array<int, string>  $matches
     */
    private static function render(array $matches): string
    {
        $surah = (int) $matches[1];
        $from = (int) $matches[2];
        $to = (int) $matches[3];

        $quoted = ($matches[5] ?? '') !== '' ? $matches[5] : ($matches[6] ?? '');
        $delimiters = ($matches[5] ?? '') !== '' ? ['{', '}'] : ['(', ')'];

        // Nothing in the corpus falls outside the Mushaf, but a bad marker must
        // not become a link to a 404. Drop it and keep the quotation.
        if ($surah < 1 || $surah > self::MAX_SURAH || $from < 1) {
            return $quoted === '' ? '' : $delimiters[0].$quoted.$delimiters[1];
        }

        // `/quran/verse/{key}` owns where a verse lives — it resolves the key
        // and redirects to the chapter page anchored at it. Linking to the
        // anchor directly would copy that format into a second place.
        $url = e(route('public.quran.verse', $surah.':'.$from));

        $label = $to > $from
            ? arabic_digits($surah).':'.arabic_digits($from).'–'.arabic_digits($to)
            : arabic_digits($surah).':'.arabic_digits($from);

        $title = e(__('hadith.quran_citation', ['reference' => $label]));

        $classes = 'quran-citation text-[#0056b3] dark:text-[#64CCC5] underline decoration-dotted '
            .'decoration-[#0056b3]/40 dark:decoration-[#64CCC5]/40 underline-offset-[6px] '
            .'hover:decoration-solid focus:outline-none focus-visible:ring-2 focus-visible:ring-[#64CCC5] rounded-sm';

        $open = '<a href="'.$url.'" class="'.$classes.'" title="'.$title.'" data-quran="'.$surah.':'.$from.'">';

        // 16 markers in the corpus quote nothing — the ayah runs on unquoted
        // afterwards, so there is no text to wrap. Those get the reference
        // itself as the link, rather than vanishing.
        if ($quoted === '') {
            return $open.'﴿'.$label.'﴾</a> ';
        }

        return $open.$delimiters[0].$quoted.$delimiters[1].'</a>';
    }
}
