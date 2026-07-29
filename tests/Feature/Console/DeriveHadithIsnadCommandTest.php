<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class DeriveHadithIsnadCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_parses_the_chain_in_order_with_its_transmission_verbs(): void
    {
        $hadith = $this->hadith(
            'حَدَّثَنَا مُسَدَّدُ بْنُ مُسَرْهَدٍ، حَدَّثَنَا حَمَّادُ بْنُ زَيْدٍ، عَنْ عَبْدِ الْعَزِيزِ بْنِ صُهَيْبٍ، عَنْ أَنَسِ بْنِ مَالِكٍ، أَنَّ رَسُولَ اللَّهِ صلى الله عليه وسلم قَالَ كَذَا'
        );

        $this->artisan('hadith:derive-isnad', ['--apply' => true, '--show' => 0])->assertExitCode(0);

        $chain = DB::table('hadith_isnad as i')
            ->join('hadith_narrators as n', 'n.id', '=', 'i.narrator_id')
            ->where('i.hadith_id', $hadith)
            ->orderBy('i.position_in_chain')
            ->get(['i.position_in_chain', 'i.transmission_method', 'n.name_ar']);

        $this->assertCount(4, $chain);
        $this->assertSame(['haddathana', 'haddathana', "'an", "'an"], $chain->pluck('transmission_method')->all());
        $this->assertSame([1, 2, 3, 4], $chain->pluck('position_in_chain')->map(fn ($p) => (int) $p)->all());
        // The chain must end at the companion, not run on into the report.
        $this->assertStringContainsString('انس', (string) $chain->last()->name_ar);
    }

    public function test_it_stops_at_the_report_and_never_captures_the_matn(): void
    {
        $hadith = $this->hadith(
            'حَدَّثَنَا وَكِيعٌ، عَنْ شُعْبَةَ، عَنْ أَنَسٍ، قَالَ اللَّهُمَّ إِنِّي أَعُوذُ بِكَ مِنَ الْهَمِّ وَالْحَزَنِ'
        );

        $this->artisan('hadith:derive-isnad', ['--apply' => true, '--show' => 0])->assertExitCode(0);

        $names = DB::table('hadith_isnad as i')
            ->join('hadith_narrators as n', 'n.id', '=', 'i.narrator_id')
            ->where('i.hadith_id', $hadith)->pluck('n.name_ar');

        $this->assertCount(3, $names);
        foreach ($names as $name) {
            $this->assertStringNotContainsString('اللهم', (string) $name, 'the supplication is the report, not a narrator');
        }
    }

    public function test_it_leaves_an_existing_chain_untouched(): void
    {
        $hadith = $this->hadith('حَدَّثَنَا مَالِكٌ، عَنْ نَافِعٍ، عَنِ ابْنِ عُمَرَ، أَنَّ النَّبِي قَالَ كَذَا');

        $narrator = DB::table('hadith_narrators')->insertGetId([
            'name_ar' => 'راو موجود', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('hadith_isnad')->insert([
            'hadith_id' => $hadith, 'narrator_id' => $narrator, 'position_in_chain' => 1,
            'transmission_method' => 'haddathana', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->artisan('hadith:derive-isnad', ['--apply' => true, '--show' => 0])->assertExitCode(0);

        $this->assertSame(1, DB::table('hadith_isnad')->where('hadith_id', $hadith)->count());
    }

    public function test_it_skips_a_relative_reference(): void
    {
        $hadith = $this->hadith('حَدَّثَنَا مُسَدَّدٌ، عَنْ بَهْزٍ، عَنْ أَبِيهِ، أَنَّ النَّبِي قَالَ كَذَا');

        $this->artisan('hadith:derive-isnad', ['--apply' => true, '--show' => 0])->assertExitCode(0);

        $names = DB::table('hadith_isnad as i')
            ->join('hadith_narrators as n', 'n.id', '=', 'i.narrator_id')
            ->where('i.hadith_id', $hadith)->pluck('n.name_ar');

        // "عن أبيه" — from his father — names nobody and must not become a link.
        $this->assertCount(2, $names);
        foreach ($names as $name) {
            $this->assertNotSame('ابيه', (string) $name);
        }
    }

    private function hadith(string $arabic): int
    {
        $collection = DB::table('hadith_collections')->insertGetId([
            'name_ar' => 'مجموعة', 'name_en' => 'Collection',
            'slug_ar' => 'majmua-'.uniqid(), 'slug_en' => 'collection-'.uniqid(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $book = DB::table('hadith_books')->insertGetId([
            'collection_id' => $collection, 'book_number' => '1', 'title_ar' => 'كتاب', 'title_en' => 'Book',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $hadith = DB::table('hadith_hadiths')->insertGetId([
            'book_id' => $book, 'hadith_number' => '1',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('hadith_translations')->insert([
            'hadith_id' => $hadith, 'language_code' => 'ar', 'text' => $arabic,
            'verified' => 0, 'is_primary' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $hadith;
    }
}
