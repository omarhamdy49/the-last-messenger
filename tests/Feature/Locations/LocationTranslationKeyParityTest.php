<?php

declare(strict_types=1);

namespace Tests\Feature\Locations;

use App\Domain\Shared\Enums\LocationType;
use Tests\TestCase;

/**
 * Constitution V: every user-facing string goes through __() with the key
 * present in ALL FIVE language files. Arabic is authored first and is both the
 * default and the fallback, so it is the reference set.
 *
 * A missing key does not throw — Laravel renders the key itself — so this is
 * the only thing that catches it.
 */
class LocationTranslationKeyParityTest extends TestCase
{
    private const LOCALES = ['en', 'fr', 'tr', 'ur'];

    /**
     * @param  array<string, mixed>  $translations
     * @return array<int, string>
     */
    private function flatten(array $translations, string $prefix = ''): array
    {
        $keys = [];

        foreach ($translations as $key => $value) {
            $full = $prefix === '' ? (string) $key : "{$prefix}.{$key}";

            if (is_array($value)) {
                $keys = array_merge($keys, $this->flatten($value, $full));

                continue;
            }

            $keys[] = $full;
        }

        return $keys;
    }

    /**
     * @return array<int, string>
     */
    private function keysFor(string $locale): array
    {
        $path = lang_path("{$locale}/locations.php");

        $this->assertFileExists($path, "resources/lang/{$locale}/locations.php is missing");

        $keys = $this->flatten(require $path);
        sort($keys);

        return $keys;
    }

    public function test_every_locale_carries_the_full_arabic_key_set(): void
    {
        $arabic = $this->keysFor('ar');

        $this->assertNotEmpty($arabic);

        foreach (self::LOCALES as $locale) {
            $keys = $this->keysFor($locale);

            $this->assertSame(
                [],
                array_values(array_diff($arabic, $keys)),
                "resources/lang/{$locale}/locations.php is missing keys present in Arabic"
            );

            $this->assertSame(
                [],
                array_values(array_diff($keys, $arabic)),
                "resources/lang/{$locale}/locations.php has keys Arabic does not"
            );
        }
    }

    public function test_no_translation_value_is_empty(): void
    {
        foreach (array_merge(['ar'], self::LOCALES) as $locale) {
            foreach ($this->flatten(require lang_path("{$locale}/locations.php")) as $key) {
                $value = __("locations.{$key}", [], $locale);

                $this->assertNotSame('', trim((string) $value), "locations.{$key} is empty in {$locale}");
                $this->assertNotSame("locations.{$key}", $value, "locations.{$key} is unresolved in {$locale}");
            }
        }
    }

    public function test_every_location_type_has_a_label_in_every_locale(): void
    {
        foreach (LocationType::values() as $type) {
            foreach (array_merge(['ar'], self::LOCALES) as $locale) {
                $key = "locations.types.{$type}";

                $this->assertNotSame($key, __($key, [], $locale), "{$key} missing in {$locale}");
            }
        }
    }
}
