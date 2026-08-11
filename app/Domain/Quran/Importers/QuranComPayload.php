<?php

declare(strict_types=1);

namespace App\Domain\Quran\Importers;

/**
 * Shared normalisation for payloads coming from the Quran.com API (v4).
 *
 * Used by both quran:import-translation and quran:import-tafsir, which consume
 * the same response shapes and previously carried byte-identical private copies
 * of these helpers.
 */
final class QuranComPayload
{
    /**
     * Locales the platform stores content in, keyed by the API's language name.
     */
    private const LANGUAGE_MAP = [
        'arabic' => 'ar',
        'english' => 'en',
        'french' => 'fr',
        'turkish' => 'tr',
        'urdu' => 'ur',
    ];

    /**
     * Map an API `language_name` ("English", "Urdu", …) to a stored locale code.
     * Unknown languages fall back to their first two letters.
     */
    public static function mapLang(string $languageName): string
    {
        return self::LANGUAGE_MAP[mb_strtolower($languageName)] ?? mb_substr($languageName, 0, 2);
    }

    /**
     * Convert the API's HTML into clean, readable plain text (the reader escapes
     * output, so storing plain text keeps it safe and consistent with the
     * existing Muyassar tafsir). Also strips the footnote markup the translation
     * endpoint embeds, e.g. <sup foot_note="...">.
     */
    public static function cleanHtml(string $html): string
    {
        // Drop footnote references entirely — their content is just the marker
        // digit ("Allāh,<sup foot_note=1>1</sup>" must not become "Allāh,1").
        $html = preg_replace('/<sup[^>]*>.*?<\/sup>/is', '', $html) ?? $html;
        $html = preg_replace('/<\s*(br|\/p|\/h[1-6]|\/div|\/li)\s*\/?>/i', "\n", $html) ?? $html;
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }
}
