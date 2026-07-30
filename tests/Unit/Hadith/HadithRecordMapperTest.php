<?php

declare(strict_types=1);

namespace Tests\Unit\Hadith;

use App\Domain\Hadith\Enums\GradeScope;
use App\Domain\Hadith\Enums\HadithGrade;
use App\Domain\Hadith\Import\GradeNormalizer;
use App\Domain\Hadith\Import\HadithRecordMapper;
use App\Domain\Hadith\Services\ArabicTextNormalizer;
use PHPUnit\Framework\TestCase;

/**
 * Driven by tests/Fixtures/Hadith/bukhari-book-1.json — two records cut verbatim
 * from the real extraction, including hadith 4, whose deprecated translation
 * number (3) genuinely differs from its in-book number (4).
 */
class HadithRecordMapperTest extends TestCase
{
    private HadithRecordMapper $mapper;

    /** @var array<string, mixed> */
    private array $fixture;

    protected function setUp(): void
    {
        parent::setUp();
        $normalizer = new ArabicTextNormalizer;
        $this->mapper = new HadithRecordMapper($normalizer, new GradeNormalizer($normalizer));
        $this->fixture = json_decode(
            (string) file_get_contents(__DIR__.'/../../Fixtures/Hadith/bukhari-book-1.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
    }

    /** @return array<string, mixed> */
    private function record(string $number): array
    {
        foreach ($this->fixture['records'] as $record) {
            if ($record['hadith_number'] === $number) {
                return $record;
            }
        }

        $this->fail("fixture has no record {$number}");
    }

    public function test_it_maps_the_book(): void
    {
        $book = $this->mapper->mapBook($this->fixture['book'], '1');

        $this->assertSame('1', $book['book_number']);
        $this->assertSame('Revelation', $book['title']['en']);
        $this->assertSame('كتاب بدء الوحى', $book['title']['ar']);
    }

    /** The source prints "(1)"; parentheses are presentation, not identity. */
    public function test_it_strips_the_parentheses_from_a_chapter_number(): void
    {
        $chapter = $this->mapper->mapChapter($this->fixture['chapters'][0], 1);

        $this->assertSame('1', $chapter['chapter_number']);
    }

    public function test_it_maps_identity_and_both_urns(): void
    {
        $mapped = $this->mapper->mapRecord($this->record('1'));

        $this->assertSame('1', $mapped['hadith']['hadith_number']);
        $this->assertSame(10, $mapped['hadith']['sunnah_urn']);
        $this->assertSame(100010, $mapped['hadith']['sunnah_urn_en']);
        $this->assertSame('1', $mapped['chapter_number']);
    }

    public function test_it_reads_the_in_book_and_volume_numbers_from_the_reference_lines(): void
    {
        $mapped = $this->mapper->mapRecord($this->record('1'));

        $this->assertSame(1, $mapped['hadith']['in_book_number']);
        $this->assertSame('1', $mapped['hadith']['volume_number']);
    }

    /**
     * Hadith 4 is in-book 4 but its deprecated translation line says Hadith 3.
     * The in-book number must come from the in-book row, not from whichever row
     * happens to be scanned last.
     */
    public function test_it_prefers_the_in_book_row_when_the_deprecated_row_disagrees(): void
    {
        $mapped = $this->mapper->mapRecord($this->record('4'));

        $this->assertSame(4, $mapped['hadith']['in_book_number']);
    }

    public function test_it_produces_one_text_row_per_populated_locale(): void
    {
        $mapped = $this->mapper->mapRecord($this->record('1'));

        $this->assertCount(2, $mapped['texts']);
        $this->assertEqualsCanonicalizing(['ar', 'en'], array_column($mapped['texts'], 'locale'));
    }

    public function test_the_arabic_search_body_is_normalised_but_the_body_is_not(): void
    {
        $mapped = $this->mapper->mapRecord($this->record('1'));
        $arabic = collect($mapped['texts'])->firstWhere('locale', 'ar');

        $this->assertStringContainsString('إِنَّمَا', $arabic['body'], 'display text keeps its diacritics');
        $this->assertStringNotContainsString('إِنَّمَا', $arabic['search_body'], 'the indexed copy is folded');
        $this->assertStringContainsString('انما', $arabic['search_body']);
    }

    public function test_a_latin_locale_keeps_its_body_as_the_search_body(): void
    {
        $mapped = $this->mapper->mapRecord($this->record('1'));
        $english = collect($mapped['texts'])->firstWhere('locale', 'en');

        $this->assertSame($english['body'], $english['search_body']);
    }

    public function test_a_locale_with_no_body_produces_no_row(): void
    {
        $record = $this->record('1');
        $record['texts']['en'] = ['body' => '   '];

        $mapped = $this->mapper->mapRecord($record);

        $this->assertCount(1, $mapped['texts']);
        $this->assertSame('ar', $mapped['texts'][0]['locale']);
    }

    /** Bukhari carries no grade table at all — inclusion is the grading. */
    public function test_bukhari_records_map_to_no_gradings(): void
    {
        $this->assertSame([], $this->mapper->mapRecord($this->record('1'))['gradings']);
    }

    public function test_a_grade_string_maps_to_a_claim_with_its_attribution(): void
    {
        $record = $this->record('1');
        $record['gradings'] = [['locale' => 'en', 'raw_grade' => "Isnād Da'īf (Zubair `Aliza'i)"]];

        $gradings = $this->mapper->mapRecord($record)['gradings'];

        $this->assertCount(1, $gradings);
        $this->assertSame(HadithGrade::DAIF, $gradings[0]['grade']);
        $this->assertSame(GradeScope::ISNAD, $gradings[0]['scope']);
        $this->assertSame("Isnād Da'īf (Zubair `Aliza'i)", $gradings[0]['raw_grade']);
        $this->assertSame("Zubair `Aliza'i", $gradings[0]['attributed_to']);
    }

    public function test_a_zero_urn_is_treated_as_absent(): void
    {
        $record = $this->record('1');
        $record['sunnah_urn'] = 0;

        $this->assertNull($this->mapper->mapRecord($record)['hadith']['sunnah_urn']);
    }
}
