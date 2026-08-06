<?php

declare(strict_types=1);

namespace Tests\Unit\People;

use App\Domain\Shared\Support\ArabicNormalizer;
use PHPUnit\Framework\TestCase;

/**
 * The normalizer is used on BOTH sides of every search: writing search_name_*
 * and folding the incoming query. These tests pin the folding rules, because a
 * change on one side only fails silently and in one direction.
 */
class ArabicNormalizerTest extends TestCase
{
    private ArabicNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->normalizer = new ArabicNormalizer;
    }

    public function test_diacritics_are_stripped(): void
    {
        $this->assertSame(
            $this->normalizer->normalize('محمد'),
            $this->normalizer->normalize('مُحَمَّد')
        );
    }

    public function test_tatweel_is_stripped(): void
    {
        $this->assertSame('محمد', $this->normalizer->normalize('محـــمد'));
    }

    public function test_alef_variants_fold_together(): void
    {
        $expected = $this->normalizer->normalize('احمد');

        foreach (['أحمد', 'إحمد', 'آحمد', 'ٱحمد'] as $variant) {
            $this->assertSame($expected, $this->normalizer->normalize($variant), $variant);
        }
    }

    public function test_alef_maqsura_folds_to_ya(): void
    {
        $this->assertSame(
            $this->normalizer->normalize('يحيي'),
            $this->normalizer->normalize('يحيى')
        );
    }

    public function test_ta_marbuta_folds_to_ha(): void
    {
        $this->assertSame(
            $this->normalizer->normalize('فاطمه'),
            $this->normalizer->normalize('فاطمة')
        );
    }

    public function test_punctuation_becomes_a_token_break_not_a_deletion(): void
    {
        // "عبد-الله" must fold to two tokens. Deleting the hyphen instead would
        // produce the single token "عبدالله", which then fails to match a
        // search for "عبد".
        $this->assertSame('عبد الله', $this->normalizer->normalize('عبد-الله'));
    }

    public function test_arabic_indic_digits_fold_to_ascii(): void
    {
        $this->assertSame('123', $this->normalizer->normalize('١٢٣'));
    }

    public function test_whitespace_is_collapsed_and_trimmed(): void
    {
        $this->assertSame('عبد الله', $this->normalizer->normalize("  عبد   \n الله  "));
    }

    public function test_english_is_lowercased(): void
    {
        $this->assertSame('abu hurayrah', $this->normalizer->normalize('Abu Hurayrah'));
    }

    public function test_null_passes_through(): void
    {
        $this->assertNull($this->normalizer->normalize(null));
    }

    public function test_parts_are_combined_and_deduplicated(): void
    {
        // A name repeated inside the kunya would otherwise inflate the FULLTEXT
        // relevance score for no reason.
        $this->assertSame(
            'محمد بن عبدالله ابو القاسم',
            $this->normalizer->normalizeParts(['محمد بن عبدالله', 'أبو القاسم محمد'])
        );
    }

    public function test_empty_parts_yield_null(): void
    {
        $this->assertNull($this->normalizer->normalizeParts([null, '', '   ']));
    }
}
