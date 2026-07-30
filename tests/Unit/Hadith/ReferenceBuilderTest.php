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
            ['hadith_number' => '1', 'in_book_number' => 1, 'volume_number' => '1'],
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

    public function test_a_collection_without_volumes_falls_back_to_the_in_book_form(): void
    {
        $set = $this->builder->build($this->hadith(
            ['code' => 'adab', 'reference_template' => 'Al-Adab Al-Mufrad {hadithNumber}'],
            ['book_number' => '2'],
            ['hadith_number' => '14', 'in_book_number' => 14, 'volume_number' => null],
        ));

        $this->assertSame('Al-Adab Al-Mufrad 14', $set->canonical);
        $this->assertSame('Book 2, Hadith 14', $set->translation);
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
