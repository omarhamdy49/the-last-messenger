<?php

declare(strict_types=1);

namespace Tests\Feature\Hadith\Schema;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithCollection;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ~35% of records carry no sunnah.com permalink, so the urn columns can never be
 * identity columns. Uniqueness is collection-scoped because aggregate sources
 * such as `forty` intentionally repeat the standalone `nawawi40` URNs.
 *
 * If UNIQUE were mistakenly enforced against NULLs, the second Arabic-only record
 * ever imported would fail — which is why this is asserted rather than assumed.
 */
class UrnUniquenessTest extends TestCase
{
    use RefreshDatabase;

    public function test_many_hadiths_may_have_no_urn_at_all(): void
    {
        Hadith::factory()->count(3)->create(['sunnah_urn' => null, 'sunnah_urn_en' => null]);

        $this->assertDatabaseCount('hadith_hadiths', 3);
        $this->assertSame(3, Hadith::query()->whereNull('sunnah_urn')->count());
    }

    public function test_a_duplicate_arabic_urn_in_one_collection_is_rejected(): void
    {
        $collection = HadithCollection::factory()->create();
        $book = HadithBook::factory()->for($collection, 'collection')->create();
        Hadith::query()->create($this->row($collection->id, $book->id, ['sunnah_urn' => 660010]));

        $this->expectException(QueryException::class);

        Hadith::query()->create($this->row($collection->id, $book->id, ['sunnah_urn' => 660010]));
    }

    public function test_a_duplicate_english_urn_in_one_collection_is_rejected(): void
    {
        $collection = HadithCollection::factory()->create();
        $book = HadithBook::factory()->for($collection, 'collection')->create();
        Hadith::query()->create($this->row($collection->id, $book->id, ['sunnah_urn_en' => 700010]));

        $this->expectException(QueryException::class);

        Hadith::query()->create($this->row($collection->id, $book->id, ['sunnah_urn_en' => 700010]));
    }

    public function test_the_same_urn_may_appear_in_different_collections(): void
    {
        Hadith::factory()->create(['sunnah_urn' => 1500010, 'sunnah_urn_en' => 1400010]);
        Hadith::factory()->create(['sunnah_urn' => 1500010, 'sunnah_urn_en' => 1400010]);

        $this->assertDatabaseCount('hadith_hadiths', 2);
    }

    public function test_the_two_urn_columns_are_independent(): void
    {
        // The same integer may legitimately appear once in each column: they are
        // separate sunnah.com keyspaces, not two views of one number.
        Hadith::factory()->create(['sunnah_urn' => 500, 'sunnah_urn_en' => null]);
        $second = Hadith::factory()->create(['sunnah_urn' => null, 'sunnah_urn_en' => 500]);

        $this->assertTrue($second->exists);
        $this->assertDatabaseCount('hadith_hadiths', 2);
    }

    /** @param array<string, mixed> $overrides @return array<string, mixed> */
    private function row(int $collectionId, int $bookId, array $overrides): array
    {
        return array_merge([
            'collection_id' => $collectionId,
            'book_id' => $bookId,
            'chapter_id' => null,
            'hadith_number' => (string) fake()->unique()->numberBetween(1, 99999),
            'in_book_number' => 1,
            'is_published' => true,
        ], $overrides);
    }
}
