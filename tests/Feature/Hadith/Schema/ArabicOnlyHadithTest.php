<?php

declare(strict_types=1);

namespace Tests\Feature\Hadith\Schema;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithText;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FR-015. English is absent for ~7% of records. `body` is NOT NULL, so those
 * records must have exactly ONE text row — the locale row is omitted rather than
 * written empty, because an empty string renders as a blank translation panel
 * that looks like a bug.
 */
class ArabicOnlyHadithTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_arabic_only_hadith_persists_with_exactly_one_text_row(): void
    {
        $hadith = Hadith::factory()->create();

        HadithText::factory()->for($hadith, 'hadith')->arabic()->create();

        $this->assertSame(1, $hadith->texts()->count());
        $this->assertSame('ar', $hadith->texts()->value('locale'));
        $this->assertDatabaseCount('hadith_texts', 1);
    }

    public function test_a_bilingual_hadith_has_one_row_per_locale(): void
    {
        $hadith = Hadith::factory()->create();

        HadithText::factory()->for($hadith, 'hadith')->arabic()->create();
        HadithText::factory()->for($hadith, 'hadith')->english()->create();

        $this->assertSame(2, $hadith->texts()->count());
        $this->assertEqualsCanonicalizing(
            ['ar', 'en'],
            $hadith->texts()->pluck('locale')->all(),
        );
    }

    public function test_one_locale_may_not_appear_twice_for_the_same_hadith(): void
    {
        $hadith = Hadith::factory()->create();
        HadithText::factory()->for($hadith, 'hadith')->arabic()->create();

        $this->expectException(QueryException::class);

        HadithText::factory()->for($hadith, 'hadith')->arabic('نص آخر')->create();
    }

    public function test_the_arabic_fallback_resolves_when_the_requested_locale_is_absent(): void
    {
        $hadith = Hadith::factory()->create();
        HadithText::factory()->for($hadith, 'hadith')->arabic()->create();

        $hadith->load('texts');

        $this->assertSame('ar', $hadith->textFor('en')?->locale);
    }
}
