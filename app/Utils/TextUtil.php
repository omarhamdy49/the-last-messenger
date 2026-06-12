<?php

declare(strict_types=1);

namespace App\Utils;

class TextUtil
{
    /**
     * Remove Arabic tashkeel (diacritical marks) from text
     *
     * Removes the following Unicode ranges:
     * - U+064B to U+065F: Fatha, Damma, Kasra, Sukun, Shadda, Tanween, etc.
     * - U+0670: Superscript Alef
     * - U+06D6 to U+06ED: Various Arabic marks
     */
    public static function removeTashkeel(string $text): string
    {
        // Arabic tashkeel (diacritical marks) Unicode ranges
        $tashkeel = [
            '\x{064B}', // Fathatan
            '\x{064C}', // Dammatan
            '\x{064D}', // Kasratan
            '\x{064E}', // Fatha
            '\x{064F}', // Damma
            '\x{0650}', // Kasra
            '\x{0651}', // Shadda
            '\x{0652}', // Sukun
            '\x{0653}', // Maddah Above
            '\x{0654}', // Hamza Above
            '\x{0655}', // Hamza Below
            '\x{0656}', // Subscript Alef
            '\x{0657}', // Inverted Damma
            '\x{0658}', // Mark Noon Ghunna
            '\x{0659}', // Zwarakay
            '\x{065A}', // Vowel Sign Small V Above
            '\x{065B}', // Vowel Sign Inverted Small V Above
            '\x{065C}', // Vowel Sign Dot Below
            '\x{065D}', // Reversed Damma
            '\x{065E}', // Fatha With Two Dots
            '\x{065F}', // Wavy Hamza Below
            '\x{0670}', // Superscript Alef
            '\x{06D6}', // Small High Ligature Sad With Lam With Alef Maksura
            '\x{06D7}', // Small High Ligature Qaf With Lam With Alef Maksura
            '\x{06D8}', // Small High Meem Initial Form
            '\x{06D9}', // Small High Lam Alef
            '\x{06DA}', // Small High Jeem
            '\x{06DB}', // Small High Three Dots
            '\x{06DC}', // Small High Seen
            '\x{06DD}', // End Of Ayah
            '\x{06DE}', // Start Of Rub El Hizb
            '\x{06DF}', // Small High Rounded Zero
            '\x{06E0}', // Small High Upright Rectangular Zero
            '\x{06E1}', // Small High Dotless Head Of Khah
            '\x{06E2}', // Small High Meem Isolated Form
            '\x{06E3}', // Small Low Seen
            '\x{06E4}', // Small High Madda
            '\x{06E5}', // Small Waw
            '\x{06E6}', // Small Yeh
            '\x{06E7}', // Small High Yeh
            '\x{06E8}', // Small High Noon
            '\x{06E9}', // Place Of Sajdah
            '\x{06EA}', // Empty Centre Low Stop
            '\x{06EB}', // Empty Centre High Stop
            '\x{06EC}', // Rounded High Stop With Filled Centre
            '\x{06ED}', // Small Low Meem
        ];

        $pattern = '/['.implode('', $tashkeel).']/u';

        return preg_replace($pattern, '', $text);
    }

    /**
     * Remove square brackets and their content
     */
    public static function removeBrackets(string $text): string
    {
        // Remove square brackets and their content
        return preg_replace('/\[([^\]]*)\]/u', '', $text);
    }

    /**
     * Remove HTML tags
     */
    public static function removeHtmlTags(string $text): string
    {
        return strip_tags($text);
    }

    /**
     * Remove special annotation tags like [narrator], [prematn], [matn], etc.
     */
    public static function removeAnnotationTags(string $text): string
    {
        $patterns = [
            '/\[narrator[^\]]*\]/u',
            '/\[\/narrator\]/u',
            '/\[prematn\]/u',
            '/\[\/prematn\]/u',
            '/\[matn\]/u',
            '/\[\/matn\]/u',
            '/\[postmatn\]/u',
            '/\[\/postmatn\]/u',
        ];

        foreach ($patterns as $pattern) {
            $text = preg_replace($pattern, '', $text);
        }

        return $text;
    }

    /**
     * Normalize whitespace (remove extra spaces, tabs, newlines)
     */
    public static function normalizeWhitespace(string $text): string
    {
        // Replace multiple whitespace with single space
        $text = preg_replace('/\s+/u', ' ', $text);

        // Trim leading and trailing whitespace
        return trim($text);
    }

    /**
     * Clean Arabic hadith text
     *
     * Removes:
     * - HTML tags
     * - Annotation tags ([narrator], [prematn], etc.)
     * - Square brackets
     * - Arabic tashkeel (diacritical marks)
     * - Extra whitespace
     */
    public static function cleanArabicText(string $text): string
    {
        $text = self::removeHtmlTags($text);
        $text = self::removeAnnotationTags($text);
        $text = self::removeBrackets($text);
        $text = self::removeTashkeel($text);
        $text = self::normalizeWhitespace($text);

        return $text;
    }

    /**
     * Clean English hadith text
     *
     * Removes:
     * - HTML tags
     * - Annotation tags
     * - Square brackets
     * - Extra whitespace
     */
    public static function cleanEnglishText(string $text): string
    {
        $text = self::removeHtmlTags($text);
        $text = self::removeAnnotationTags($text);
        $text = self::removeBrackets($text);
        $text = self::normalizeWhitespace($text);

        return $text;
    }

    /**
     * Clean text for search (language-agnostic)
     *
     * Removes:
     * - HTML tags
     * - Annotation tags
     * - Square brackets
     * - Extra whitespace
     * - Arabic tashkeel (if present)
     */
    public static function cleanForSearch(string $text): string
    {
        $text = self::removeHtmlTags($text);
        $text = self::removeAnnotationTags($text);
        $text = self::removeBrackets($text);
        $text = self::removeTashkeel($text); // Safe for non-Arabic text
        $text = self::normalizeWhitespace($text);

        return $text;
    }

    /**
     * Remove special characters except letters, numbers, and basic punctuation
     */
    public static function removeSpecialCharacters(string $text, bool $keepArabic = true): string
    {
        if ($keepArabic) {
            // Keep Arabic letters, English letters, numbers, and basic punctuation
            return preg_replace('/[^\p{Arabic}\p{Latin}\d\s\.\,\!\?\:\;\-\'\"]/u', '', $text);
        } else {
            // Keep only English letters, numbers, and basic punctuation
            return preg_replace('/[^a-zA-Z\d\s\.\,\!\?\:\;\-\'\"]/u', '', $text);
        }
    }

    /**
     * Clean text with custom options
     *
     * @param  string  $text  Text to clean
     * @param  array  $options  Options: removeTashkeel, removeBrackets, removeHtml, removeAnnotations, normalizeWhitespace
     * @return string Cleaned text
     */
    public static function clean(string $text, array $options = []): string
    {
        $defaults = [
            'removeHtml' => true,
            'removeAnnotations' => true,
            'removeBrackets' => true,
            'removeTashkeel' => true,
            'normalizeWhitespace' => true,
            'removeSpecialChars' => false,
        ];

        $options = array_merge($defaults, $options);

        if ($options['removeHtml']) {
            $text = self::removeHtmlTags($text);
        }

        if ($options['removeAnnotations']) {
            $text = self::removeAnnotationTags($text);
        }

        if ($options['removeBrackets']) {
            $text = self::removeBrackets($text);
        }

        if ($options['removeTashkeel']) {
            $text = self::removeTashkeel($text);
        }

        if ($options['removeSpecialChars']) {
            $text = self::removeSpecialCharacters($text);
        }

        if ($options['normalizeWhitespace']) {
            $text = self::normalizeWhitespace($text);
        }

        return $text;
    }
}
