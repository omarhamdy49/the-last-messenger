<?php

declare(strict_types=1);

namespace App\Http\Support;

/**
 * Sanitises a free-text question/search submitted on the public "Ask Quran" page.
 *
 * Defence-in-depth at the input boundary: it removes every character that is not
 * a letter (any script), a combining mark (Arabic harakat), a digit, whitespace,
 * or one of the explicitly allowed punctuation marks ( ! ? ؟ - _ ). The Arabic
 * question mark (؟, U+061F) is permitted as the equivalent of `?`. Disallowed
 * characters — including the ones used in SQL/HTML injection (`'`, `"`, `;`, `<`,
 * `>`, `=`, `(`, `)`, backticks, etc.) — are replaced with a space so words are
 * not silently glued together, then whitespace is collapsed.
 *
 * Storage/queries are already parameterised (Eloquent) and Blade-escaped; this
 * narrows the accepted input surface as an additional, explicit guard.
 */
final class QuestionSanitizer
{
    /** Validation pattern: the whole string must consist only of allowed characters. */
    public const PATTERN = '/^[\p{L}\p{M}\p{N}\s!?\x{061F}_-]+$/u';

    /** Negated character class used to strip anything outside the allowlist. */
    private const DISALLOWED = '/[^\p{L}\p{M}\p{N}\s!?\x{061F}_-]+/u';

    public static function sanitize(string $value): string
    {
        // Strip disallowed characters (the dash is last in the class so it is literal).
        $clean = preg_replace(self::DISALLOWED, ' ', $value) ?? $value;

        // Collapse the gaps left behind, then trim.
        $clean = preg_replace('/\s+/u', ' ', $clean) ?? $clean;

        return trim($clean);
    }
}
