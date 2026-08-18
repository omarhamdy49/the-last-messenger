<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Shared;

use App\Domain\Shared\Services\LocationResolver;
use PHPUnit\Framework\TestCase;

/**
 * Normalisation is what makes alias matching work at all.
 *
 * The source text carries Arabic diacritics (شِعب, أُحُد, بُصرى) and inconsistent
 * alef forms, and MySQL collation folding is not dependable across them — which
 * is why matching happens in PHP over a cached vocabulary rather than in SQL.
 * See specs/003-locations-module/research.md R-04.
 */
class LocationNormaliserTest extends TestCase
{
    public function test_tashkeel_is_stripped(): void
    {
        $this->assertSame(
            LocationResolver::normalise('شعب'),
            LocationResolver::normalise('شِعب')
        );

        $this->assertSame(
            LocationResolver::normalise('احد'),
            LocationResolver::normalise('أُحُد')
        );
    }

    public function test_alef_forms_are_unified(): void
    {
        $normalised = LocationResolver::normalise('أإآا');

        $this->assertSame('اااا', $normalised);
    }

    public function test_taa_marbuta_and_alef_maqsura_are_unified(): void
    {
        $this->assertSame(
            LocationResolver::normalise('مكه'),
            LocationResolver::normalise('مكة')
        );

        $this->assertSame(
            LocationResolver::normalise('بصري'),
            LocationResolver::normalise('بصرى')
        );
    }

    public function test_tatweel_is_removed(): void
    {
        $this->assertSame(
            LocationResolver::normalise('مكة'),
            LocationResolver::normalise('مـــكة')
        );
    }

    public function test_latin_text_is_lowercased_and_article_prefixes_stripped(): void
    {
        $taif = LocationResolver::normalise("Ta'if");

        $this->assertSame($taif, LocationResolver::normalise("al-Ta'if"));
        $this->assertSame($taif, LocationResolver::normalise("Al-Ta'if"));
        $this->assertSame($taif, LocationResolver::normalise("AL-TA'IF"));
        $this->assertSame(
            LocationResolver::normalise('Surat'),
            LocationResolver::normalise('as-Surat')
        );
    }

    public function test_whitespace_is_collapsed_and_trimmed(): void
    {
        $this->assertSame(
            LocationResolver::normalise('المدينة المنورة'),
            LocationResolver::normalise("  المدينة \n  المنورة  ")
        );
    }

    public function test_the_madinah_spellings_all_normalise_together_with_their_aliases(): void
    {
        // These three are 1,419 rows of the same place stored three ways.
        $values = ['Medinah', 'Medina', 'Madinah'];
        $normalised = array_map(LocationResolver::normalise(...), $values);

        // They do not collapse to one string by normalisation alone — `Medina`
        // and `Madinah` differ in more than case — which is exactly why the
        // alias list exists. Normalisation only has to make each one stable.
        foreach ($normalised as $i => $value) {
            $this->assertSame($value, LocationResolver::normalise($values[$i]));
            $this->assertSame(strtolower($values[$i]), $value);
        }
    }

    public function test_normalising_is_idempotent(): void
    {
        foreach (['شِعب أبي طالب بمكة', "al-Ta'if", 'المدينة المنورة'] as $value) {
            $once = LocationResolver::normalise($value);
            $this->assertSame($once, LocationResolver::normalise($once));
        }
    }
}
