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
            ->get(['i.position_in_chain', 'i.transmission_method', 'n.name']);

        $this->assertCount(4, $chain);
        $this->assertSame(["'an", "'an", 'haddathana', 'haddathana'], $chain->pluck('transmission_method')->all());
        $this->assertSame([1, 2, 3, 4], $chain->pluck('position_in_chain')->map(fn ($p) => (int) $p)->all());
        // Position 1 is the companion nearest the Prophet.
        $nearestProphet = json_decode((string) $chain->first()->name, true);
        $this->assertStringContainsString('انس', (string) $nearestProphet['ar']);
    }

    public function test_it_stops_at_the_report_and_never_captures_the_matn(): void
    {
        $hadith = $this->hadith(
            'حَدَّثَنَا وَكِيعٌ، عَنْ شُعْبَةَ، عَنْ أَنَسٍ، قَالَ اللَّهُمَّ إِنِّي أَعُوذُ بِكَ مِنَ الْهَمِّ وَالْحَزَنِ'
        );

        $this->artisan('hadith:derive-isnad', ['--apply' => true, '--show' => 0])->assertExitCode(0);

        $names = DB::table('hadith_isnad as i')
            ->join('hadith_narrators as n', 'n.id', '=', 'i.narrator_id')
            ->where('i.hadith_id', $hadith)->pluck('n.name');

        $this->assertCount(3, $names);
        foreach ($names as $name) {
            $localized = json_decode((string) $name, true);
            $this->assertStringNotContainsString('اللهم', (string) $localized['ar'], 'the supplication is the report, not a narrator');
        }
    }

    public function test_it_leaves_an_existing_chain_untouched(): void
    {
        $hadith = $this->hadith('حَدَّثَنَا مَالِكٌ، عَنْ نَافِعٍ، عَنِ ابْنِ عُمَرَ، أَنَّ النَّبِي قَالَ كَذَا');

        $narrator = DB::table('hadith_narrators')->insertGetId([
            'name' => json_encode(['ar' => 'راو موجود'], JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
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
            ->where('i.hadith_id', $hadith)->pluck('n.name');

        // "عن أبيه" — from his father — names nobody and must not become a link.
        $this->assertCount(2, $names);
        foreach ($names as $name) {
            $localized = json_decode((string) $name, true);
            $this->assertNotSame('ابيه', (string) $localized['ar']);
        }
    }

    private function hadith(string $arabic): int
    {
        $collection = DB::table('hadith_collections')->insertGetId([
            'code' => 'collection-'.uniqid(),
            'name' => json_encode(['ar' => 'مجموعة', 'en' => 'Collection'], JSON_UNESCAPED_UNICODE),
            'slug' => 'collection-'.uniqid(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $book = DB::table('hadith_books')->insertGetId([
            'collection_id' => $collection,
            'book_number' => '1',
            'title' => json_encode(['ar' => 'كتاب', 'en' => 'Book'], JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $chapter = DB::table('hadith_chapters')->insertGetId([
            'book_id' => $book,
            'chapter_number' => '1',
            'name' => json_encode(['ar' => 'باب'], JSON_UNESCAPED_UNICODE),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $hadith = DB::table('hadith_hadiths')->insertGetId([
            'chapter_id' => $chapter,
            'hadith_number' => '1',
            'text_hash' => hash('sha256', $arabic),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('hadith_translations')->insert([
            'hadith_id' => $hadith,
            'locale' => 'ar',
            'text' => $arabic,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $hadith;
    }
}
