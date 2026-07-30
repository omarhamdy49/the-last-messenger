<?php

declare(strict_types=1);

namespace Tests\Feature\Hadith\Schema;

use App\Domain\Hadith\Models\Hadith;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ~35% of records carry no sunnah.com permalink, so the urn columns can never be
 * identity columns. A UNIQUE index is still correct: MySQL permits unlimited
 * NULLs in one, while a duplicate non-null urn would make /hadith/urn/{n}
 * ambiguous.
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

    public function test_a_duplicate_arabic_urn_is_rejected(): void
    {
        Hadith::factory()->create(['sunnah_urn' => 660010]);

        $this->expectException(QueryException::class);

        Hadith::factory()->create(['sunnah_urn' => 660010]);
    }

    public function test_a_duplicate_english_urn_is_rejected(): void
    {
        Hadith::factory()->create(['sunnah_urn_en' => 700010]);

        $this->expectException(QueryException::class);

        Hadith::factory()->create(['sunnah_urn_en' => 700010]);
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
}
