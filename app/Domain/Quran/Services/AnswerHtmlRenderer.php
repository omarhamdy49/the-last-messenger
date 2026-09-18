<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

/**
 * Canonical server-side renderer for "Ask Quran" answers: Markdown -> premium,
 * XSS-safe HTML with inline "go to verse" links. Rendered once on the backend so
 * the saved answer is served already-formatted (no LLM, no client-side parsing).
 *
 * It linkifies verse references written either as the marker form `[[2:153]]`
 * (current answers) or by name, e.g. `(البقرة: ١٥٣)` / `(Al-Baqara: 153)` (older
 * answers), so the verse number is clickable in the body of the text itself.
 *
 * XSS-safe: the source is HTML-escaped first; the only markup emitted is our own
 * block tags and anchors whose href is built from integers.
 */
final class AnswerHtmlRenderer
{
    private const ARABIC_DIGITS = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];

    public function __construct(
        private readonly ChapterNameIndex $names,
        private readonly AnswerVerseLinker $linker,
    ) {}

    public function render(string $markdown): string
    {
        $markdown = $this->stripNoise($markdown);
        if (trim($markdown) === '') {
            return '';
        }

        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", e($markdown)));
        $html = [];
        $count = count($lines);

        for ($i = 0; $i < $count;) {
            $line = trim($lines[$i]);

            if ($line === '') {
                $i++;

                continue;
            }

            if (preg_match('/^(#{1,6})\s+(.*)$/', $line, $m) === 1) {
                $level = min(max(strlen($m[1]), 2) + 1, 5); // '#'/'##' => h3
                $html[] = "<h{$level}>".$this->inline($m[2])."</h{$level}>";
                $i++;

                continue;
            }

            if ($this->isQuote($line)) {
                $buf = [];
                while ($i < $count && $this->isQuote(trim($lines[$i]))) {
                    $buf[] = $this->inline(preg_replace('/^&gt;\s?/', '', trim($lines[$i])) ?? '');
                    $i++;
                }
                $html[] = '<blockquote>'.implode('<br>', $buf).'</blockquote>';

                continue;
            }

            if ($this->isUl($line)) {
                $buf = [];
                while ($i < $count && $this->isUl(trim($lines[$i]))) {
                    $buf[] = '<li>'.$this->inline(preg_replace('/^[-*]\s+/', '', trim($lines[$i])) ?? '').'</li>';
                    $i++;
                }
                $html[] = '<ul>'.implode('', $buf).'</ul>';

                continue;
            }

            if ($this->isOl($line)) {
                $buf = [];
                while ($i < $count && $this->isOl(trim($lines[$i]))) {
                    $buf[] = '<li>'.$this->inline(preg_replace('/^\d+\.\s+/', '', trim($lines[$i])) ?? '').'</li>';
                    $i++;
                }
                $html[] = '<ol>'.implode('', $buf).'</ol>';

                continue;
            }

            $buf = [];
            while ($i < $count) {
                $cur = trim($lines[$i]);
                if ($cur === '' || $this->isHeading($cur) || $this->isQuote($cur) || $this->isUl($cur) || $this->isOl($cur)) {
                    break;
                }
                $buf[] = $this->inline($cur);
                $i++;
            }
            $html[] = '<p>'.implode('<br>', $buf).'</p>';
        }

        return implode('', $html);
    }

    private function isHeading(string $t): bool
    {
        return preg_match('/^#{1,6}\s+/', $t) === 1;
    }

    private function isQuote(string $t): bool
    {
        // '>' has already been escaped to '&gt;'.
        return str_starts_with($t, '&gt;');
    }

    private function isUl(string $t): bool
    {
        return preg_match('/^[-*]\s+/', $t) === 1;
    }

    private function isOl(string $t): bool
    {
        return preg_match('/^\d+\.\s+/', $t) === 1;
    }

    /**
     * Inline spans + verse links, applied to already-escaped text.
     */
    private function inline(string $text): string
    {
        $text = $this->linkMarkers($text);
        $text = $this->linkNamedRefs($text);
        $text = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $text) ?? $text;
        $text = preg_replace('/(^|[^*])\*([^*\n]+)\*(?!\*)/', '$1<em>$2</em>', $text) ?? $text;

        return preg_replace('/`([^`]+)`/', '<code>$1</code>', $text) ?? $text;
    }

    /**
     * `[[2:255]]` / `[[2:255-257]]` -> go-to-verse link.
     */
    private function linkMarkers(string $text): string
    {
        return preg_replace_callback(
            '/\[\[\s*(\d{1,3}):(\d{1,3})(?:\s*-\s*(\d{1,3}))?\s*\]\]/',
            function (array $m): string {
                $label = $m[1].':'.$m[2].(isset($m[3]) && $m[3] !== '' ? '-'.$m[3] : '');

                return $this->anchor((int) $m[1], (int) $m[2], $label);
            },
            $text,
        ) ?? $text;
    }

    /**
     * Linkify parenthesised references written by surah name, e.g.
     * `(البقرة: ١٥٣)`, `(آل عمران: ٢٠٠)`, or `(النحل: ٤٢، والعنكبوت: ٥٩)`.
     */
    private function linkNamedRefs(string $text): string
    {
        return preg_replace_callback('/\(([^()]{2,90})\)/u', function (array $paren): string {
            $segments = preg_split('/([،;]|,)/u', $paren[1], -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$paren[1]];
            $linked = false;
            $out = '';

            foreach ($segments as $segment) {
                if (preg_match('/^[،;,]$/u', $segment) === 1) {
                    $out .= $segment;

                    continue;
                }
                $out .= $this->linkSegment($segment, $linked);
            }

            return $linked ? '('.$out.')' : $paren[0];
        }, $text) ?? $text;
    }

    private function linkSegment(string $segment, bool &$linked): string
    {
        $pattern = '/^(\s*)(.+?)\s*[:：]\s*([0-9'.implode('', self::ARABIC_DIGITS).']{1,3})([\s\x{2013}\x{2014}-]*[0-9'.implode('', self::ARABIC_DIGITS).']{1,3})?(\s*)$/u';

        if (preg_match($pattern, $segment, $m) !== 1) {
            return $segment;
        }

        $surah = $this->names->resolve($m[2]);
        if ($surah === null) {
            return $segment;
        }

        $ayah = (int) $this->toLatinDigits($m[3]);
        if ($ayah < 1) {
            return $segment;
        }

        $linked = true;
        $label = trim($segment);

        return $m[1].$this->anchor($surah, $ayah, $label).$m[5];
    }

    /**
     * A premium go-to-verse anchor. `$label` is already HTML-escaped (it comes
     * from escaped answer text); the href is built from integers only.
     */
    private function anchor(int $surah, int $ayah, string $label): string
    {
        $href = e($this->linker->verseUrl($surah, $ayah));

        return '<a href="'.$href.'" class="qa-verse">'
            .'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>'
            .$label.'</a>';
    }

    private function toLatinDigits(string $value): string
    {
        return str_replace(self::ARABIC_DIGITS, ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'], $value);
    }

    /**
     * Drop horizontal-rule separators (rendered as wasted vertical space) and a
     * trailing "grounding/sources" line the model used to append.
     */
    private function stripNoise(string $markdown): string
    {
        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $markdown));
        $kept = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);

            // Horizontal rule: --- / *** / ___
            if (preg_match('/^([-*_])\1{2,}$/', $trimmed) === 1) {
                continue;
            }

            // Trailing grounding/sources line (any language we emit it in).
            $bare = preg_replace('/^[>#*\s]+/u', '', $trimmed) ?? $trimmed;
            if (preg_match('/^(grounding|grounded sources|sources?\b|المصادر|المصدر|المراجع|kaynak|sources consult|مستند)/iu', $bare) === 1) {
                continue;
            }

            $kept[] = $line;
        }

        return trim(implode("\n", $kept));
    }
}
