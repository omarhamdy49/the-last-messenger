<?php

declare(strict_types=1);

namespace App\Utils;

use Carbon\Carbon;
use IntlCalendar;
use IntlDateFormatter;

class DateUtil
{
    /**
     * Get current Hijri date in Arabic format
     *
     * @return string
     */
    public static function getCurrentHijriDate(): string
    {
        return self::toHijri(Carbon::now());
    }

    /**
     * Convert Gregorian date to Hijri
     *
     * @param Carbon|string|null $date
     * @param string $locale
     * @param string $format
     * @return string
     */
    public static function toHijri(
        Carbon|string|null $date = null,
        string $locale = 'ar',
        string $format = 'd MMMM yyyy'
    ): string {
        if ($date === null) {
            $date = Carbon::now();
        } elseif (is_string($date)) {
            $date = Carbon::parse($date);
        }

        // Check if intl extension is available
        if (!extension_loaded('intl')) {
            // Fallback: use approximate conversion algorithm
            return self::approximateHijriConversion($date);
        }

        try {
            // Create Islamic calendar
            $calendar = IntlCalendar::createInstance(null, '@calendar=islamic-civil');
            $calendar->setTime($date->timestamp * 1000); // IntlCalendar uses milliseconds

            // Format the date in Hijri
            // Use 0 for GREGORIAN calendar type (will be overridden by locale setting)
            $formatter = new IntlDateFormatter(
                $locale . '@calendar=islamic-civil',
                IntlDateFormatter::FULL,
                IntlDateFormatter::NONE,
                $date->timezone->getName(),
                0, // Calendar type (overridden by locale)
                $format
            );

            return $formatter->format($calendar) ?: self::approximateHijriConversion($date);
        } catch (\Exception $e) {
            // Fallback to approximate conversion
            return self::approximateHijriConversion($date);
        }
    }

    /**
     * Approximate Hijri date conversion (fallback when intl is not available)
     * Uses a simplified algorithm
     *
     * @param Carbon $date
     * @return string
     */
    private static function approximateHijriConversion(Carbon $date): string
    {
        $gregorianYear = (int) $date->format('Y');
        $gregorianMonth = (int) $date->format('m');
        $gregorianDay = (int) $date->format('d');

        // Calculate Julian Day Number
        $a = floor((14 - $gregorianMonth) / 12);
        $y = $gregorianYear + 4800 - $a;
        $m = $gregorianMonth + 12 * $a - 3;

        $jd = $gregorianDay + floor((153 * $m + 2) / 5) + 365 * $y + floor($y / 4) - floor($y / 100) + floor($y / 400) - 32045;

        // Convert Julian Day to Islamic Date
        $l = $jd - 1948440 + 10632;
        $n = floor(($l - 1) / 10631);
        $l = $l - 10631 * $n + 354;
        $j = (floor((10985 - $l) / 5316)) * (floor((50 * $l) / 17719)) + (floor($l / 5670)) * (floor((43 * $l) / 15238));
        $l = $l - (floor((30 - $j) / 15)) * (floor((17719 * $j) / 50)) - (floor($j / 16)) * (floor((15238 * $j) / 43)) + 29;
        $hijriMonth = floor((24 * $l) / 709);
        $hijriDay = $l - floor((709 * $hijriMonth) / 24);
        $hijriYear = 30 * $n + $j - 30;

        // Arabic month names
        $monthNames = [
            1 => 'محرم',
            2 => 'صفر',
            3 => 'ربيع الأول',
            4 => 'ربيع الآخر',
            5 => 'جمادى الأولى',
            6 => 'جمادى الآخرة',
            7 => 'رجب',
            8 => 'شعبان',
            9 => 'رمضان',
            10 => 'شوال',
            11 => 'ذو القعدة',
            12 => 'ذو الحجة',
        ];

        $monthName = $monthNames[$hijriMonth] ?? $hijriMonth;

        return "{$hijriDay} {$monthName} {$hijriYear} هـ";
    }

    /**
     * Get Hijri month names in Arabic
     *
     * @return array
     */
    public static function getHijriMonthNames(): array
    {
        return [
            1 => 'محرم',
            2 => 'صفر',
            3 => 'ربيع الأول',
            4 => 'ربيع الآخر',
            5 => 'جمادى الأولى',
            6 => 'جمادى الآخرة',
            7 => 'رجب',
            8 => 'شعبان',
            9 => 'رمضان',
            10 => 'شوال',
            11 => 'ذو القعدة',
            12 => 'ذو الحجة',
        ];
    }

    /**
     * Get Hijri month names in English
     *
     * @return array
     */
    public static function getHijriMonthNamesEnglish(): array
    {
        return [
            1 => 'Muharram',
            2 => 'Safar',
            3 => 'Rabi\' al-Awwal',
            4 => 'Rabi\' al-Thani',
            5 => 'Jumada al-Awwal',
            6 => 'Jumada al-Thani',
            7 => 'Rajab',
            8 => 'Sha\'ban',
            9 => 'Ramadan',
            10 => 'Shawwal',
            11 => 'Dhu al-Qi\'dah',
            12 => 'Dhu al-Hijjah',
        ];
    }

    /**
     * Format Hijri date for display
     *
     * @param string|null $hijriDate
     * @param string $locale
     * @return string
     */
    public static function formatHijriDate(?string $hijriDate, string $locale = 'ar'): string
    {
        if (empty($hijriDate)) {
            return '';
        }

        // If already formatted, return as is
        if (str_contains($hijriDate, 'هـ') || str_contains($hijriDate, 'AH')) {
            return $hijriDate;
        }

        // Add Hijri indicator if missing
        return $locale === 'ar' ? "{$hijriDate} هـ" : "{$hijriDate} AH";
    }

    /**
     * Get current Hijri year
     *
     * @return int
     */
    public static function getCurrentHijriYear(): int
    {
        if (extension_loaded('intl')) {
            try {
                $calendar = IntlCalendar::createInstance(null, '@calendar=islamic-civil');
                $calendar->setTime(time() * 1000);
                return $calendar->get(IntlCalendar::FIELD_YEAR);
            } catch (\Exception $e) {
                // Fallback
            }
        }

        // Approximate calculation
        $gregorianYear = (int) date('Y');
        return (int) floor(($gregorianYear - 622) * 1.030684);
    }

    /**
     * Get Hijri date from Gregorian date string
     *
     * @param string $gregorianDate Format: Y-m-d
     * @param string $locale
     * @return string
     */
    public static function fromGregorian(string $gregorianDate, string $locale = 'ar'): string
    {
        try {
            $carbon = Carbon::createFromFormat('Y-m-d', $gregorianDate);
            return self::toHijri($carbon, $locale);
        } catch (\Exception $e) {
            return self::getCurrentHijriDate();
        }
    }
}
