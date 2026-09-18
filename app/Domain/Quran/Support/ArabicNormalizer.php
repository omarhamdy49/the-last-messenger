<?php

declare(strict_types=1);

namespace App\Domain\Quran\Support;

/**
 * Search-side normalisation for Arabic text: no tashkeel or tatweel, one alef, one yaa,
 * taa marbuta as haa, Arabic-Indic digits as ASCII, lower-cased Latin.
 */
final class ArabicNormalizer
{
    public static function normalize(string $text): string
    {
        $text = strtr(self::stripMarks($text), ['أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ٱ' => 'ا', 'ى' => 'ي', 'ة' => 'ه', 'ؤ' => 'و', 'ئ' => 'ي']);

        return mb_strtolower($text);
    }

    /**
     * Digits, tashkeel and tatweel only — for matching the diacritic-free imlaei text in
     * MySQL, whose accent-insensitive collation already folds hamza forms of alef.
     */
    public static function stripMarks(string $text): string
    {
        $text = self::digits($text);
        // Quranic annotation marks, harakat, superscript alef, tatweel.
        $text = (string) preg_replace('/[\x{0610}-\x{061A}\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}\x{0640}]/u', '', $text);

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    public static function digits(string $text): string
    {
        return strtr($text, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ]);
    }
}
