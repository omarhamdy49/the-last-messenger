<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LocaleConfigurationTest extends TestCase
{
    /**
     * Test default locale is Arabic
     */
    public function test_default_locale_is_arabic(): void
    {
        $this->assertEquals('ar', config('app.locale'));
        $this->assertEquals('ar', app()->getLocale());
    }

    /**
     * Test fallback locale is Arabic
     */
    public function test_fallback_locale_is_arabic(): void
    {
        $this->assertEquals('ar', config('app.fallback_locale'));
    }

    /**
     * Test faker locale is Arabic
     */
    public function test_faker_locale_is_arabic_saudi(): void
    {
        $this->assertEquals('ar_SA', config('app.faker_locale'));
    }

    /**
     * Test Carbon locale is Arabic
     */
    public function test_carbon_locale_is_arabic(): void
    {
        $this->assertEquals('ar', Carbon::getLocale());
    }

    /**
     * Test admin routes use Arabic locale
     */
    public function test_admin_routes_use_arabic_locale(): void
    {
        $response = $this->get('/su/portal/v1/admin/login');

        // Should redirect to login or dashboard
        $this->assertTrue(
            $response->isRedirect() || $response->isSuccessful()
        );

        // App locale should be Arabic
        $this->assertEquals('ar', app()->getLocale());
    }

    /**
     * Test Arabic validation messages exist
     */
    public function test_arabic_validation_messages_exist(): void
    {
        app()->setLocale('ar');

        $validator = validator(
            ['email' => 'invalid-email'],
            ['email' => 'required|email']
        );

        $validator->fails();

        $errors = $validator->errors()->first('email');

        // Should contain Arabic text
        $this->assertStringContainsString('البريد الإلكتروني', $errors);
    }

    /**
     * Test Arabic date formatting
     */
    public function test_arabic_date_formatting(): void
    {
        $date = Carbon::parse('2025-01-01');

        $arabicDate = format_arabic_date($date, 'F Y');

        // Should contain Arabic month name
        $this->assertNotEquals('January 2025', $arabicDate);
    }

    /**
     * Test RTL locale detection
     */
    public function test_rtl_locale_detection(): void
    {
        $this->assertTrue(is_rtl_locale('ar'));
        $this->assertTrue(is_rtl_locale('ur'));
        $this->assertFalse(is_rtl_locale('en'));
        $this->assertFalse(is_rtl_locale('fr'));
    }

    /**
     * Test text direction helper
     */
    public function test_text_direction_helper(): void
    {
        $this->assertEquals('rtl', get_text_direction('ar'));
        $this->assertEquals('rtl', get_text_direction('ur'));
        $this->assertEquals('ltr', get_text_direction('en'));
        $this->assertEquals('ltr', get_text_direction('fr'));
    }

    /**
     * Test Arabic numeral conversion
     */
    public function test_arabic_numeral_conversion(): void
    {
        $this->assertEquals('١٢٣٤٥', convert_to_arabic_numerals('12345'));
        $this->assertEquals('٠', convert_to_arabic_numerals(0));
        $this->assertEquals('٩٩٩', convert_to_arabic_numerals(999));
    }

    /**
     * Test Western numeral conversion
     */
    public function test_western_numeral_conversion(): void
    {
        $this->assertEquals('12345', convert_to_western_numerals('١٢٣٤٥'));
        $this->assertEquals('0', convert_to_western_numerals('٠'));
        $this->assertEquals('999', convert_to_western_numerals('٩٩٩'));
    }

    /**
     * Test Arabic number formatting
     */
    public function test_arabic_number_formatting(): void
    {
        $formatted = format_arabic_number(1234567.89, 2);
        $this->assertEquals('1,234,567.89', $formatted);

        $formattedArabic = format_arabic_number(1234567.89, 2, true);
        $this->assertStringContainsString('١', $formattedArabic);
    }

    /**
     * Test locale name helper
     */
    public function test_locale_name_helper(): void
    {
        // In Arabic
        $this->assertEquals('العربية', get_locale_name('ar', 'ar'));
        $this->assertEquals('الإنجليزية', get_locale_name('en', 'ar'));

        // In English
        $this->assertEquals('Arabic', get_locale_name('ar', 'en'));
        $this->assertEquals('English', get_locale_name('en', 'en'));
    }

    /**
     * Test supported content locales are configured for multilingual modules
     */
    public function test_supported_locales_include_all_content_languages(): void
    {
        $supportedLocales = config('app.supported_locales');

        $this->assertIsArray($supportedLocales);
        $this->assertSame(['ar', 'en', 'fr', 'tr', 'ur'], array_keys($supportedLocales));
    }
}
