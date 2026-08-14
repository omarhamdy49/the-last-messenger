<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;

if (! function_exists('format_arabic_date')) {
    /**
     * Format date in Arabic locale
     *
     * @param  string|Carbon|null  $date
     * @param  string  $format  Default format: 'd M Y'
     */
    function format_arabic_date($date, string $format = 'd M Y'): string
    {
        if (empty($date)) {
            return '';
        }

        if (! $date instanceof Carbon) {
            $date = Carbon::parse($date);
        }

        // Set locale to Arabic for date formatting
        $originalLocale = Carbon::getLocale();
        Carbon::setLocale('ar');

        $formattedDate = $date->translatedFormat($format);

        // Restore original locale
        Carbon::setLocale($originalLocale);

        return $formattedDate;
    }
}

if (! function_exists('format_relative_arabic_date')) {
    /**
     * Format date as relative time in Arabic (e.g., "منذ 5 دقائق")
     *
     * @param  string|Carbon|null  $date
     */
    function format_relative_arabic_date($date): string
    {
        if (empty($date)) {
            return '';
        }

        if (! $date instanceof Carbon) {
            $date = Carbon::parse($date);
        }

        $originalLocale = Carbon::getLocale();
        Carbon::setLocale('ar');

        $relativeDate = $date->diffForHumans();

        Carbon::setLocale($originalLocale);

        return $relativeDate;
    }
}

if (! function_exists('convert_to_arabic_numerals')) {
    /**
     * Convert Western numerals (0-9) to Arabic/Hindi numerals (٠-٩)
     *
     * @param  string|int|float  $number
     */
    function convert_to_arabic_numerals($number): string
    {
        $western = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];

        return str_replace($western, $arabic, (string) $number);
    }
}

if (! function_exists('convert_to_western_numerals')) {
    /**
     * Convert Arabic/Hindi numerals (٠-٩) to Western numerals (0-9)
     */
    function convert_to_western_numerals(string $number): string
    {
        $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $western = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        return str_replace($arabic, $western, $number);
    }
}

if (! function_exists('format_arabic_number')) {
    /**
     * Format number with thousands separator in Arabic style
     *
     * @param  int|float  $number
     * @param  int  $decimals  Number of decimal places
     * @param  bool  $useArabicNumerals  Convert to Arabic numerals
     */
    function format_arabic_number($number, int $decimals = 0, bool $useArabicNumerals = false): string
    {
        // Format with comma as thousands separator and dot as decimal separator
        $formatted = number_format((float) $number, $decimals, '.', ',');

        if ($useArabicNumerals) {
            $formatted = convert_to_arabic_numerals($formatted);
        }

        return $formatted;
    }
}

if (! function_exists('is_rtl_locale')) {
    /**
     * Check if current locale is RTL (Right-to-Left)
     *
     * @param  string|null  $locale  If null, uses current app locale
     */
    function is_rtl_locale(?string $locale = null): bool
    {
        $locale = $locale ?? app()->getLocale();
        $rtlLocales = ['ar', 'ur', 'fa', 'he'];

        return in_array($locale, $rtlLocales);
    }
}

if (! function_exists('get_text_direction')) {
    /**
     * Get text direction for current locale
     *
     * @param  string|null  $locale  If null, uses current app locale
     * @return string Returns 'rtl' or 'ltr'
     */
    function get_text_direction(?string $locale = null): string
    {
        return is_rtl_locale($locale) ? 'rtl' : 'ltr';
    }
}

if (! function_exists('hijri_date')) {
    /**
     * Convert Gregorian date to Hijri date (Islamic calendar)
     * Note: Requires additional package for accurate conversion
     *
     * @param  string|Carbon|null  $date
     */
    function hijri_date($date = null, string $format = 'd M Y'): string
    {
        if (empty($date)) {
            $date = now();
        }

        if (! $date instanceof Carbon) {
            $date = Carbon::parse($date);
        }

        // Basic approximation (for accurate conversion, use a Hijri calendar package)
        // This is a placeholder - consider using packages like:
        // - umulmrum/hijri-date
        // - alkoumi/laravel-hijri-date

        // For now, return Gregorian date with Arabic format
        return format_arabic_date($date, $format);
    }
}

if (! function_exists('localized_route')) {
    /**
     * Generate localized route URL
     *
     * @param  string  $name  Route name
     * @param  array  $parameters  Route parameters
     * @param  string|null  $locale  Locale code
     */
    function localized_route(string $name, array $parameters = [], ?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $parameters['locale'] = $locale;

        return route($name, $parameters);
    }
}

if (! function_exists('trans_fallback')) {
    /**
     * Get translation with fallback to English if not found
     *
     * @param  string  $key  Translation key
     * @param  array  $replace  Replacement values
     * @param  string|null  $locale  Locale to use
     */
    function trans_fallback(string $key, array $replace = [], ?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();

        // Try to get translation in current locale
        $translation = trans($key, $replace, $locale);

        // If translation not found and not in English, try English
        if ($translation === $key && $locale !== 'en') {
            $translation = trans($key, $replace, 'en');
        }

        return $translation;
    }
}

if (! function_exists('format_file_size')) {
    /**
     * Format file size in human-readable format (Arabic)
     *
     * @param  int  $bytes  File size in bytes
     * @param  bool  $useArabicNumerals  Use Arabic numerals
     */
    function format_file_size(int $bytes, bool $useArabicNumerals = false): string
    {
        $units = ['بايت', 'كيلوبايت', 'ميجابايت', 'جيجابايت', 'تيرابايت'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        $size = round($bytes, 2);

        if ($useArabicNumerals) {
            $size = convert_to_arabic_numerals($size);
        }

        return $size.' '.$units[$i];
    }
}

if (! function_exists('arabic_ordinal')) {
    /**
     * Convert number to Arabic ordinal (e.g., 1 => الأول)
     */
    function arabic_ordinal(int $number): string
    {
        $ordinals = [
            1 => 'الأول',
            2 => 'الثاني',
            3 => 'الثالث',
            4 => 'الرابع',
            5 => 'الخامس',
            6 => 'السادس',
            7 => 'السابع',
            8 => 'الثامن',
            9 => 'التاسع',
            10 => 'العاشر',
        ];

        return $ordinals[$number] ?? $number.'';
    }
}

if (! function_exists('get_locale_name')) {
    /**
     * Get localized name of a language
     *
     * @param  string  $locale  Language code
     * @param  string|null  $displayLocale  Locale to display name in
     */
    function get_locale_name(string $locale, ?string $displayLocale = null): string
    {
        $displayLocale = $displayLocale ?? app()->getLocale();

        $names = [
            'ar' => [
                'ar' => 'العربية',
                'en' => 'الإنجليزية',
                'fr' => 'الفرنسية',
                'tr' => 'التركية',
                'ur' => 'الأردية',
            ],
            'en' => [
                'ar' => 'Arabic',
                'en' => 'English',
                'fr' => 'French',
                'tr' => 'Turkish',
                'ur' => 'Urdu',
            ],
        ];

        return $names[$displayLocale][$locale] ?? $locale;
    }
}

if (! function_exists('country_flag')) {
    /**
     * Regional-indicator flag emoji for a 2-letter ISO country code.
     */
    function country_flag(?string $iso): ?string
    {
        if ($iso === null || strlen($iso) !== 2 || ! ctype_alpha($iso)) {
            return null;
        }

        $iso = strtoupper($iso);

        return mb_chr(0x1F1E6 + ord($iso[0]) - 65).mb_chr(0x1F1E6 + ord($iso[1]) - 65);
    }
}

if (! function_exists('country_name')) {
    /**
     * Localized country name for a 2-letter ISO code (falls back to the code).
     */
    function country_name(?string $iso, ?string $locale = null): ?string
    {
        if ($iso === null || $iso === '') {
            return null;
        }

        $iso = strtoupper($iso);
        $locale ??= app()->getLocale();

        if (class_exists(Locale::class)) {
            $name = Locale::getDisplayRegion('-'.$iso, $locale);
            if ($name !== '' && $name !== $iso) {
                return $name;
            }
        }

        return $iso;
    }
}

if (! function_exists('excerpt_sentence')) {
    /**
     * Trim to a whole sentence rather than a character count.
     *
     * Str::limit() cuts mid-word, which is merely untidy in a meta tag but ugly
     * in the event hero, where the text is set at 4xl: "…اهتمّوا بشمائل…".
     * Prefer the last sentence boundary inside the budget; if there is none,
     * fall back to the last whole word so a word is never sliced.
     *
     * Handles the Arabic full stop, question mark and comma-less clause ends,
     * plus the closing guillemet that ends a quoted sentence in this content.
     *
     * @param  int  $max  character budget, not a hard target
     */
    function excerpt_sentence(?string $text, int $max = 80, string $end = '…'): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $text)));

        if ($text === '' || mb_strlen($text) <= $max) {
            return $text;
        }

        $window = mb_substr($text, 0, $max);

        // Last sentence terminator inside the budget. The lookahead keeps the
        // terminator itself and avoids matching a decimal point mid-number.
        if (preg_match_all('/[.!?؟](?=\s|$)|[»"](?=\s|$)/u', $window, $m, PREG_OFFSET_CAPTURE)) {
            $last = end($m[0]);
            // preg offsets are in bytes; convert before slicing multibyte text.
            $chars = mb_strlen(substr($window, 0, $last[1] + strlen($last[0])));
            if ($chars >= (int) ($max * 0.45)) {
                return mb_substr($text, 0, $chars);
            }
        }

        // No usable sentence end — back off to the last whole word.
        $cut = mb_strrpos($window, ' ');

        return rtrim(mb_substr($window, 0, $cut ?: $max), " ،,;:-–—").$end;
    }
}

if (! function_exists('enhance_rich_embeds')) {
    /**
     * Render-time polish for editor module embeds stored inside rich content.
     *
     * The admin editor's verse blot stores the ayah text with bare end-of-ayah
     * marks (U+06DD) and no numbers — the ornament renders as an empty circle.
     * The verse range starts at the number in the embed's own anchor (#N), so
     * the marks can be numbered sequentially at render time, which fixes every
     * already-stored embed without re-authoring. Bare marks become the same
     * `.verse-number` chips the article renderer uses; the closing ornament
     * gains the final verse's number. Idempotent: numbered marks are skipped.
     *
     * Also strips the decorative emoji (📖 📜 📍) older blot payloads stored in
     * footer links — the icon belongs to CSS, not content.
     */
    function enhance_rich_embeds(?string $html): ?string
    {
        if ($html === null || ! str_contains($html, 'module-embed')) {
            return $html;
        }

        // Emoji out of embed footer links (any embed type).
        $html = preg_replace(
            '/(<div class="module-embed__footer">\s*<a [^>]*>)\s*[\x{1F4D6}\x{1F4DC}\x{1F4CD}]\s*/u',
            '$1',
            $html
        ) ?? $html;

        $numbered = preg_replace_callback(
            '/<div class="module-embed module-embed--verse[^"]*"[^>]*>.*?<\/div><\/div>/su',
            function (array $m): string {
                $block = $m[0];

                // The range's first ayah number lives in the embed's own link.
                if (! preg_match('/#(\d+)/', $block, $anchor)) {
                    return $block;
                }
                $next = (int) $anchor[1];

                // Fill each bare end-of-ayah mark (no digits already attached).
                $block = preg_replace_callback(
                    '/\x{06DD}(?!\s*[0-9\x{0660}-\x{0669}])/u',
                    function () use (&$next): string {
                        return '<span class="verse-number">'.arabic_digits($next++).'</span>';
                    },
                    $block
                ) ?? $block;

                // The final verse carries no mark — number it before the
                // closing ornament.
                return preg_replace(
                    '/\s*﴾/u',
                    ' <span class="verse-number">'.arabic_digits($next).'</span>﴾',
                    $block,
                    1
                ) ?? $block;
            },
            $html
        );

        return $numbered ?? $html;
    }
}

if (! function_exists('arabic_digits')) {
    /**
     * Western digits → Arabic-Indic (٠١٢٣٤٥٦٧٨٩). Quranic verse numbers read
     * in the script of the verse itself.
     */
    function arabic_digits(int|string $number): string
    {
        return strtr((string) $number, [
            '0' => '٠', '1' => '١', '2' => '٢', '3' => '٣', '4' => '٤',
            '5' => '٥', '6' => '٦', '7' => '٧', '8' => '٨', '9' => '٩',
        ]);
    }
}
