<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The command rewrites who narrated a hadith, so a bad reading does not merely
 * lose data — it misattributes a report to the wrong companion. These pin the
 * readings that must be refused.
 */
final class DeriveHadithCompanionsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_takes_the_companion_from_the_english_attribution(): void
    {
        $hadith = $this->hadith(
            english: 'Narrated Abu Huraira: The Prophet said something.',
            arabic: 'حَدَّثَنَا قُتَيْبَةُ، عَنْ مَالِكٍ، عَنْ أَبِي هُرَيْرَةَ، أَنَّ رَسُولَ اللَّهِ صلى الله عليه وسلم قَالَ كَذَا',
        );

        $this->artisan('hadith:derive-companions', ['--apply' => true, '--show' => 0])->assertExitCode(0);

        $name = DB::table('hadith_hadiths as h')
            ->join('hadith_companions as c', 'c.id', '=', 'h.companion_id')
            ->where('h.id', $hadith)->value('c.name_en');

        $this->assertSame('Abu Huraira', $name);
    }

    public function test_it_falls_back_to_the_arabic_isnad_when_the_translation_names_nobody(): void
    {
        $hadith = $this->hadith(
            english: 'This hadith has been reported through another chain of transmitters.',
            arabic: 'حَدَّثَنَا مَالِكٌ، عَنْ نَافِعٍ، عَنِ ابْنِ عُمَرَ، أَنَّ النَّبِي قَالَ كَذَا',
        );

        $this->artisan('hadith:derive-companions', ['--apply' => true, '--show' => 0])->assertExitCode(0);

        $row = DB::table('hadith_hadiths as h')
            ->join('hadith_companions as c', 'c.id', '=', 'h.companion_id')
            ->where('h.id', $hadith)->first(['c.name_en', 'c.name_ar']);

        // "This hadith has been..." is a reference, not a narrator, so the
        // English must be refused and the Arabic chain used instead.
        $this->assertNull($row->name_en);
        $this->assertStringContainsString('عمر', (string) $row->name_ar);
    }

    public function test_it_refuses_the_books_own_transmitter(): void
    {
        $hadith = $this->hadith(
            english: 'Yahya related to me from Malik from Nafi from Ibn Umar that the Prophet said something.',
            arabic: 'وَحَدَّثَنِي عَنْ مَالِكٍ، عَنْ نَافِعٍ، عَنِ ابْنِ عُمَرَ، أَنَّ النَّبِي قَالَ كَذَا',
        );

        $this->artisan('hadith:derive-companions', ['--apply' => true, '--show' => 0])->assertExitCode(0);

        $name = (string) DB::table('hadith_hadiths as h')
            ->join('hadith_companions as c', 'c.id', '=', 'h.companion_id')
            ->where('h.id', $hadith)->value('c.name_en');

        $this->assertNotSame('Yahya', $name, 'the Muwatta transmitter must never be recorded as the companion');
    }

    public function test_it_refuses_a_relative_reference_as_a_narrator(): void
    {
        $hadith = $this->hadith(
            english: 'It was narrated that he said: something happened.',
            arabic: 'حَدَّثَنَا مُسَدَّدٌ، عَنْ بَهْزٍ، عَنْ أَبِيهِ، قَالَ كَذَا وَكَذَا',
        );

        $this->artisan('hadith:derive-companions', ['--apply' => true, '--show' => 0])->assertExitCode(0);

        // "عن أبيه" — from his father — names nobody, so nothing may be assigned.
        $this->assertNull(DB::table('hadith_hadiths')->where('id', $hadith)->value('companion_id'));
    }

    private function hadith(string $english, string $arabic): int
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

        foreach (['en' => $english, 'ar' => $arabic] as $language => $text) {
            DB::table('hadith_translations')->insert([
                'hadith_id' => $hadith, 'language_code' => $language, 'text' => $text,
                'verified' => 0, 'is_primary' => 1,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        return $hadith;
    }
}
