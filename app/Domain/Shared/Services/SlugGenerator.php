<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use Illuminate\Support\Str;

/**
 * Shared slug generation primitives.
 *
 * Two pieces of slug logic were genuinely duplicated across the Blog services
 * and are owned here:
 *
 *  - picking the source text out of a locale-keyed array (English first,
 *    Arabic second, literal fallback last);
 *  - disambiguating a taken slug with an incrementing `-N` suffix.
 *
 * The uniqueness *lookup* deliberately stays with the caller: article locale
 * slugs are unique per (slug, locale) and exclude by owning article id, while
 * category slugs are unique table-wide and exclude by their own id. Those are
 * different queries over different tables, so they are passed in as a
 * predicate rather than folded into this class.
 */
final class SlugGenerator
{
    /**
     * First non-null value from a locale-keyed array, English preferred.
     *
     * Uses `??` (not `?:`) so an explicitly empty string is a real value and is
     * NOT replaced by the fallback — this matches every call site it replaced.
     *
     * @param  array<string, string|null>  $text
     */
    public function preferredText(array $text, string $fallback): string
    {
        return $text['en'] ?? $text['ar'] ?? $fallback;
    }

    /**
     * Slugify the preferred locale value. No uniqueness check — callers that
     * need one use unique() instead.
     *
     * @param  array<string, string|null>  $text
     */
    public function fromLocalized(array $text, string $fallback): string
    {
        return Str::slug($this->preferredText($text, $fallback));
    }

    /**
     * Slugify $text, then append `-1`, `-2`, … until $isTaken reports the
     * candidate free.
     *
     * $isTaken receives a candidate slug and returns true when it is already
     * held by a record other than the one being written.
     *
     * Str::slug() returns an empty string for input with nothing
     * transliterable (e.g. "!!!"), and that empty result is passed through
     * unchanged. Every implementation this replaced behaved the same way;
     * substituting a placeholder here would change existing slugs.
     *
     * @param  callable(string): bool  $isTaken
     */
    public function unique(string $text, callable $isTaken): string
    {
        $base = Str::slug($text);
        $slug = $base;
        $counter = 1;

        while ($isTaken($slug)) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
