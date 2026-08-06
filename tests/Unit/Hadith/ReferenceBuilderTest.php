<?php

declare(strict_types=1);

namespace Tests\Unit\Hadith;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithCollection;
use App\Domain\Hadith\Services\ReferenceBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Expected strings were read off sunnah.com on 2026-07-30. They are asserted
 * verbatim because a reader cites them elsewhere: a reference that is merely
 * "close" is a wrong citation.
 */
class ReferenceBuilderTest extends TestCase
{
    private ReferenceBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new ReferenceBuilder;
    }

    private function hadith(array $collection, array $book, array $hadith): Hadith
    {
        $collectionModel = new HadithCollection;
        $collectionModel->forceFill($collection);

        $bookModel = new HadithBook;
        $bookModel->forceFill($book);

        $model = new Hadith;
        $model->forceFill($hadith);
        $model->setRelation('collection', $collectionModel);
        $model->setRelation('book', $bookModel);

        return $model;
    }

    public function test_bukhari_1_yields_the_three_exact_reference_strings(): void
    {
        $set = $this->builder->build($this->hadith(
            ['code' => 'bukhari', 'reference_template' => 'Sahih al-Bukhari {hadithNumber}'],
            ['book_number' => '1'],
            [
                'hadith_number' => '1',
                'in_book_number' => 1,
                'volume_number' => '1',
                'source_in_book_reference' => 'Book 1, Hadith 1',
                'source_translation_reference' => 'Vol. 1, Book 1, Hadith 1',
            ],
        ));

        $this->assertSame('Sahih al-Bukhari 1', $set->canonical);
        $this->assertSame('Book 1, Hadith 1', $set->inBook);
        $this->assertSame('Vol. 1, Book 1, Hadith 1', $set->translation);
        $this->assertTrue($set->translationDeprecated);
    }

    public function test_a_non_deprecated_collection_omits_the_note(): void
    {
        $set = $this->builder->build($this->hadith(
            ['code' => 'tirmidhi', 'reference_template' => 'Jami` at-Tirmidhi {hadithNumber}'],
            ['book_number' => '1'],
            ['hadith_number' => '1', 'in_book_number' => 1, 'volume_number' => '1'],
        ));

        $this->assertSame('Jami` at-Tirmidhi 1', $set->canonical);
        $this->assertFalse($set->translationDeprecated);
    }

    public function test_optional_reference_lines_are_not_fabricated_from_structural_numbers(): void
    {
        $set = $this->builder->build($this->hadith(
            ['code' => 'adab', 'reference_template' => 'Al-Adab Al-Mufrad {hadithNumber}'],
            ['book_number' => '2'],
            ['hadith_number' => '14', 'in_book_number' => 14, 'volume_number' => null],
        ));

        $this->assertSame('Al-Adab Al-Mufrad 14', $set->canonical);
        $this->assertNull($set->inBook);
        $this->assertNull($set->translation);
    }

    /** malik and darimi use the book-scoped form as their canonical reference. */
    public function test_a_book_scoped_template_substitutes_the_book_number(): void
    {
        $set = $this->builder->build($this->hadith(
            ['code' => 'malik', 'reference_template' => 'Book {bookNumber}, Hadith {hadithNumber}'],
            ['book_number' => '1'],
            ['hadith_number' => '3', 'in_book_number' => 3, 'volume_number' => null],
        ));

        $this->assertSame('Book 1, Hadith 3', $set->canonical);
    }

    /** The forties print the number mid-line, so it is substituted, not appended. */
    public function test_the_forties_place_the_number_before_the_collection_name(): void
    {
        $set = $this->builder->build($this->hadith(
            ['code' => 'nawawi40', 'reference_template' => 'Hadith {hadithNumber}, 40 Hadith an-Nawawi'],
            ['book_number' => '0'],
            ['hadith_number' => '1', 'in_book_number' => 1, 'volume_number' => null],
        ));

        $this->assertSame('Hadith 1, 40 Hadith an-Nawawi', $set->canonical);
    }

    public function test_an_exact_stored_source_reference_wins_over_template_or_fallback(): void
    {
        $set = $this->builder->build($this->hadith(
            ['code' => 'nawawi40', 'reference_template' => null],
            ['book_number' => 'collection'],
            [
                'hadith_number' => '42',
                'in_book_number' => null,
                'volume_number' => null,
                'source_reference' => 'Hadith 42, 40 Hadith an-Nawawi',
            ],
        ));

        $this->assertSame('Hadith 42, 40 Hadith an-Nawawi', $set->canonical);
        $this->assertNull($set->inBook);
        $this->assertNull($set->translation);
    }

    public function test_exact_optional_references_can_be_recovered_from_source_metadata(): void
    {
        $set = $this->builder->build($this->hadith(
            ['code' => 'riyadussalihin', 'reference_template' => null],
            ['book_number' => 'introduction'],
            [
                'hadith_number' => '1',
                'in_book_number' => 1,
                'volume_number' => null,
                'source_reference' => 'Riyad as-Salihin 1',
                'source_metadata' => [
                    'source' => [
                        'references' => [
                            'in_book_reference' => 'Introduction, Hadith 1',
                        ],
                    ],
                ],
            ],
        ));

        $this->assertSame('Riyad as-Salihin 1', $set->canonical);
        $this->assertSame('Introduction, Hadith 1', $set->inBook);
        $this->assertNull($set->translation);
    }

    public function test_mishkat_book_30_suppresses_the_invalid_raw_english_translation_reference(): void
    {
        $set = $this->builder->build($this->hadith(
            ['code' => 'mishkat', 'reference_template' => null],
            ['book_number' => '30'],
            [
                'hadith_number' => '30.62',
                'in_book_number' => 62,
                'volume_number' => null,
                'source_reference' => 'Book 30, Hadith 62',
                'source_in_book_reference' => 'Book 30, Hadith 62',
                'source_translation_reference' => null,
                'source_metadata' => [
                    'source' => [
                        'references' => [
                            'sunnah_com_reference' => 'Book 30, Hadith 62',
                            'english_translation' => 'Book 26, Hadith 0',
                        ],
                    ],
                ],
            ],
        ));

        $this->assertSame('Book 30, Hadith 62', $set->canonical);
        $this->assertSame('Book 30, Hadith 62', $set->inBook);
        $this->assertNull($set->translation);
        $this->assertFalse($set->translationDeprecated);
    }

    public function test_a_non_numeric_hadith_number_is_carried_through(): void
    {
        $set = $this->builder->build($this->hadith(
            ['code' => 'muslim', 'reference_template' => 'Sahih Muslim {hadithNumber}'],
            ['book_number' => '1'],
            ['hadith_number' => '1a', 'in_book_number' => 1, 'volume_number' => null],
        ));

        $this->assertSame('Sahih Muslim 1a', $set->canonical);
    }

    public function test_a_missing_template_degrades_visibly_rather_than_silently(): void
    {
        $set = $this->builder->build($this->hadith(
            ['code' => 'newcoll', 'reference_template' => null],
            ['book_number' => '1'],
            ['hadith_number' => '5', 'in_book_number' => 5, 'volume_number' => null],
        ));

        $this->assertSame('newcoll 5', $set->canonical);
    }
}
