<?php

declare(strict_types=1);

namespace Tests\Unit\Hadith;

use App\Domain\Hadith\Enums\GradeScope;
use App\Domain\Hadith\Enums\HadithGrade;
use App\Domain\Hadith\Import\GradeNormalizer;
use App\Domain\Hadith\Services\ArabicTextNormalizer;
use PHPUnit\Framework\TestCase;

/**
 * Driven by tests/Fixtures/Hadith/raw-grades.json: 170 distinct strings observed
 * across nine real book pages, with their occurrence counts.
 */
class GradeNormalizerTest extends TestCase
{
    private GradeNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->normalizer = new GradeNormalizer(new ArabicTextNormalizer);
    }

    /** @dataProvider observedStrings */
    public function test_every_observed_string_yields_at_least_one_claim(string $raw, string $locale): void
    {
        $claims = $this->normalizer->normalize($raw, $locale);

        $this->assertNotEmpty($claims, "produced no claim for: {$raw}");
    }

    /**
     * The headline requirement: the corpus must not be dominated by `unknown`.
     * A normaliser that maps everything to unknown would pass every other
     * assertion here while shipping a dead grading subsystem.
     */
    public function test_it_interprets_the_overwhelming_majority_of_the_corpus(): void
    {
        $rows = self::fixture();
        $interpreted = 0;
        $total = 0;

        foreach ($rows as $row) {
            $claims = $this->normalizer->normalize($row['raw'], $row['locale']);
            $isUnknown = count($claims) === 1 && $claims[0]['grade'] === HadithGrade::UNKNOWN;
            $total += $row['n'];
            $interpreted += $isUnknown ? 0 : $row['n'];
        }

        $share = $interpreted / $total;

        $this->assertGreaterThan(
            0.95,
            $share,
            sprintf('only %.1f%% of observed grade occurrences were interpreted', $share * 100),
        );
    }

    /** @dataProvider exactExpectations */
    public function test_known_strings_map_to_the_expected_claim(string $raw, HadithGrade $grade, GradeScope $scope): void
    {
        $claims = $this->normalizer->normalize($raw);

        $this->assertCount(1, $claims, "expected exactly one claim for: {$raw}");
        $this->assertSame($grade, $claims[0]['grade'], "wrong grade for: {$raw}");
        $this->assertSame($scope, $claims[0]['scope'], "wrong scope for: {$raw}");
    }

    /** @return array<string, array{0: string, 1: HadithGrade, 2: GradeScope}> */
    public static function exactExpectations(): array
    {
        return [
            'plain english' => ['Sahih', HadithGrade::SAHIH, GradeScope::HADITH],
            'with grader' => ['Sahih (Al-Albani)', HadithGrade::SAHIH, GradeScope::HADITH],
            'with publisher' => ['Da\'if (Darussalam)', HadithGrade::DAIF, GradeScope::HADITH],
            'macron form' => ['Sahīh (Zubair `Aliza\'i)', HadithGrade::SAHIH, GradeScope::HADITH],
            'compound wins over sahih' => ['Hasan Sahih (Al-Albani)', HadithGrade::HASAN, GradeScope::HADITH],
            'agreed upon is sahih' => ['Muttafaqun \'alayh (Zubair `Aliza\'i)', HadithGrade::SAHIH, GradeScope::HADITH],
            'isnad scope english' => ['Isnād Da\'īf (Zubair `Aliza\'i)', HadithGrade::DAIF, GradeScope::ISNAD],
            'arabic plain' => ['صحيح', HadithGrade::SAHIH, GradeScope::HADITH],
            'arabic diacritised' => ['صَحِيحٌ   (الألباني)', HadithGrade::SAHIH, GradeScope::HADITH],
            'arabic farsi yeh' => ['صحیح   (زبیر علی زئی)', HadithGrade::SAHIH, GradeScope::HADITH],
            'arabic weak' => ['ضعيف   (الألباني)', HadithGrade::DAIF, GradeScope::HADITH],
            'arabic compound' => ['حسن صحيح   (الألباني)', HadithGrade::HASAN, GradeScope::HADITH],
            'arabic agreed upon' => ['متفق عليه   (زبیر علی زئی)', HadithGrade::SAHIH, GradeScope::HADITH],
            'arabic isnad scope' => ['إسنادہ ضعيف   (زبیر علی زئی)', HadithGrade::DAIF, GradeScope::ISNAD],
            'fabricated' => ['Mawdu\'', HadithGrade::MAWDU, GradeScope::HADITH],
        ];
    }

    public function test_an_uninterpretable_string_yields_unknown_and_is_reported(): void
    {
        $claims = $this->normalizer->normalize('See the discussion in volume three');

        $this->assertCount(1, $claims);
        $this->assertSame(HadithGrade::UNKNOWN, $claims[0]['grade']);
        $this->assertContains('See the discussion in volume three', $this->normalizer->unmappedGrades());
    }

    public function test_an_empty_string_yields_no_claim_at_all(): void
    {
        $this->assertSame([], $this->normalizer->normalize('   '));
    }

    /** @return array<int, array{0: string, 1: string}> */
    public static function observedStrings(): array
    {
        return array_map(
            static fn (array $row): array => [$row['raw'], $row['locale']],
            self::fixture(),
        );
    }

    /** @return list<array{locale: string, raw: string, n: int}> */
    private static function fixture(): array
    {
        return json_decode(
            (string) file_get_contents(__DIR__.'/../../Fixtures/Hadith/raw-grades.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
    }
}
