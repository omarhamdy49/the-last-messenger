<?php

declare(strict_types=1);

namespace App\Support\Utilities;

class TextSanitizer
{
    /**
     * Clean text by removing HTML tags and special characters
     * Optimized for full-text search indexing
     *
     * @param  string  $text  Raw text to sanitize
     * @param  bool  $normalize_arabic  Apply Arabic text normalization (optional)
     * @return string Sanitized text
     */
    public static function cleanText(string $text, bool $normalize_arabic = false): string
    {
        if (empty($text)) {
            return '';
        }

        // Step 1: Strip HTML tags completely
        $text = strip_tags($text);

        // Step 2: Remove common HTML entities and encoded characters
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Step 3: Remove URLs to prevent full-text search issues
        $text = preg_replace('~https?://\S+~', ' ', $text);

        // Step 4: Remove email addresses
        $text = preg_replace('~\S+@\S+~', ' ', $text);

        // Step 5: Remove most special characters but preserve basic structure
        // Keep: letters, digits, spaces, common punctuation, and Arabic script characters
        // Arabic script range: U+0600 to U+06FF
        $text = preg_replace('~[^a-zA-Z0-9\s\.\,\;\:\!\?\-\(\)\[\]\x{0600}-\x{06FF}\'"]~u', ' ', $text);

        // Step 6: Normalize whitespace (collapse multiple spaces/newlines into single space)
        $text = preg_replace('/\s+/', ' ', $text);

        // Step 7: Trim leading/trailing whitespace
        $text = trim($text);

        // Step 8: Optional: Apply Arabic text normalization
        if ($normalize_arabic) {
            $text = self::normalizeArabic($text);
        }

        return $text;
    }

    /**
     * Clean text for plain text output (more aggressive normalization)
     * Removes all special characters except basic punctuation
     *
     * @param  string  $text  Raw text to sanitize
     * @return string Plain text output
     */
    public static function cleanTextForDisplay(string $text): string
    {
        if (empty($text)) {
            return '';
        }

        // Strip HTML tags
        $text = strip_tags($text);

        // HTML decode
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Remove special characters except letters, digits, spaces, and basic punctuation
        // Keep ASCII letters, digits, spaces, common punctuation, and Arabic characters
        $text = preg_replace('~[^a-zA-Z0-9\s\.\,\;\:\!\?\-\(\)\[\]\x{0600}-\x{06FF}\'"]~u', ' ', $text);

        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);

        // Trim
        return trim($text);
    }

    /**
     * Normalize Arabic text by removing diacritical marks
     * Useful for search when diacritics shouldn't affect matching
     *
     * @param  string  $text  Arabic text
     * @return string Normalized text
     */
    private static function normalizeArabic(string $text): string
    {
        // Remove Arabic diacritical marks (tashkeel)
        $diacritics = [
            '\u064B', // FATHATAN
            '\u064C', // DAMMATAN
            '\u064D', // KASRATAN
            '\u064E', // FATHA
            '\u064F', // DAMMA
            '\u0650', // KASRA
            '\u0651', // SHADDA
            '\u0652', // SUKUN
            '\u0653', // MADDAH ABOVE
            '\u0654', // HAMZA ABOVE
            '\u0655', // HAMZA BELOW
            '\u0656', // SUBSCRIPT ALEF
            '\u0657', // INVERTED DAMMA
            '\u0658', // MARK NOON GHUNNA
            '\u0670', // SUPERSCRIPT ALEF
        ];

        foreach ($diacritics as $diacritic) {
            $text = str_replace($diacritic, '', $text);
        }

        // Normalize common variations
        $replacements = [
            'أ' => 'ا', // ALEF WITH MADDA -> ALEF
            'إ' => 'ا', // ALEF WITH HAMZA BELOW -> ALEF
            'آ' => 'ا', // ALEF WITH MADDA ABOVE -> ALEF
            'ء' => '',   // HAMZA -> remove
            'ة' => 'ه',  // TEH MARBUTA -> HEH
        ];

        foreach ($replacements as $search => $replace) {
            $text = str_replace($search, $replace, $text);
        }

        return $text;
    }

    /**
     * Escape text for MySQL FULLTEXT search
     * Removes or escapes characters that interfere with boolean mode search
     *
     * @param  string  $text  Text to escape
     * @return string Escaped text safe for FULLTEXT search
     */
    public static function escapeForFulltextSearch(string $text): string
    {
        if (empty($text)) {
            return '';
        }

        // Remove boolean operators that could break the search
        $operators = ['+', '-', '(', ')', '"', '*', '~', '<', '>', '@'];

        foreach ($operators as $op) {
            $text = str_replace($op, '', $text);
        }

        return trim($text);
    }

    /**
     * Extract plain text from mixed content
     * Useful for text that may contain HTML or special formatting
     *
     * @param  string  $text  Mixed content
     * @return string Plain text
     */
    public static function extractPlainText(string $text): string
    {
        // Strip tags
        $text = strip_tags($text);

        // Decode entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Remove extra whitespace
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }
}
