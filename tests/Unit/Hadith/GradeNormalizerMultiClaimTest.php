<?php

declare(strict_types=1);

namespace Tests\Unit\Hadith;

use App\Domain\Hadith\Enums\GradeScope;
use App\Domain\Hadith\Enums\HadithGrade;
use App\Domain\Hadith\Import\GradeNormalizer;
use App\Domain\Hadith\Services\ArabicTextNormalizer;
use PHPUnit\Framework\TestCase;

/**
 * One source string may assert several independent claims. A normaliser that
 * returns a single verdict silently discards one claim on every such record —
 * which is why these are asserted separately from the single-claim cases.
 */
class GradeNormalizerMultiClaimTest extends TestCase
{
    private GradeNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->normalizer = new GradeNormalizer(new ArabicTextNormalizer);
    }

    public function test_a_chain_and_text_verdict_yields_two_scoped_claims(): void
    {
        $claims = $this->normalizer->normalize("Sanad Da'if wal-Hadith Hasan");

        $this->assertCount(2, $claims);

        $this->assertSame(HadithGrade::DAIF, $claims[0]['grade']);
        $this->assertSame(GradeScope::ISNAD, $claims[0]['scope'], 'the chain verdict is isnad-scoped');

        $this->assertSame(HadithGrade::HASAN, $claims[1]['grade']);
        $this->assertSame(GradeScope::HADITH, $claims[1]['scope'], 'the text verdict is hadith-scoped');
    }

    public function test_a_numbered_string_yields_two_labelled_claims(): void
    {
        $claims = $this->normalizer->normalize("1: Hasan<br>2: Da'if");

        $this->assertCount(2, $claims);
        $this->assertSame(HadithGrade::HASAN, $claims[0]['grade']);
        $this->assertSame('1', $claims[0]['part_label']);
        $this->assertSame(HadithGrade::DAIF, $claims[1]['grade']);
        $this->assertSame('2', $claims[1]['part_label']);
    }

    public function test_a_single_verdict_carries_no_part_label(): void
    {
        $claims = $this->normalizer->normalize('Sahih (Al-Albani)');

        $this->assertCount(1, $claims);
        $this->assertNull($claims[0]['part_label']);
    }

    public function test_repeated_identical_verdicts_collapse_to_one_claim(): void
    {
        // Observed in the corpus: "لم تتمّ دراسته, لم تتمّ دراسته". A record must
        // not gain two identical grading rows because the source repeated itself.
        $claims = $this->normalizer->normalize('Sahih, Sahih');

        $this->assertCount(1, $claims);
    }

    /**
     * "Its study was not completed" is the source stating it has NOT graded this
     * hadith. `unknown` is the correct reading, not a gap in the rules — so it
     * must not be quietly mapped onto a verdict later.
     */
    public function test_an_explicit_not_yet_graded_note_is_unknown_by_intent(): void
    {
        $claims = $this->normalizer->normalize('لم تتمّ دراسته   (الألباني)');

        $this->assertCount(1, $claims);
        $this->assertSame(HadithGrade::UNKNOWN, $claims[0]['grade']);
    }
}
