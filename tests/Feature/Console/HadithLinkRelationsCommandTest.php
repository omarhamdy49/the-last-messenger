<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Guards the precision of `hadith:link-relations`. The command resolves a
 * narration attribution onto a real Sahaba record, so a loose match does not
 * merely miss data — it misattributes a hadith to the wrong narrator.
 */
final class HadithLinkRelationsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_links_a_short_name_to_its_full_lineage(): void
    {
        $ali = $this->sahabi('ali-ibn-abi-talib', 'علي بن أبي طالب بن عبد المطلب', 'Ali ibn Abi Talib ibn Abd al-Muttalib');
        $companion = $this->companion('علي بن أبي طالب', 'Ali ibn Abi Talib');

        $this->artisan('hadith:link-relations', ['--apply' => true, '--show' => 0])
            ->assertExitCode(0);

        $this->assertSame($ali, DB::table('hadith_companions')->where('id', $companion)->value('sahaba_id'));
    }

    public function test_it_refuses_to_let_a_bare_record_claim_a_more_specific_name(): void
    {
        // A different, unrelated "Abu Bakr" — not Abu Bakr as-Siddiq. Allowing a
        // record to match a name it only truncates would hand every
        // "Abu Bakr <something>" to this man.
        $this->sahabi('abu-bakr-al-ansari', 'أبو بكر', 'Abu Bakr');
        $companion = $this->companion('أبو بكر الصديق', 'Abu Bakr al-Siddiq');

        $this->artisan('hadith:link-relations', ['--apply' => true, '--show' => 0])
            ->assertExitCode(0);

        $this->assertNull(DB::table('hadith_companions')->where('id', $companion)->value('sahaba_id'));
    }

    public function test_it_leaves_an_ambiguous_name_unlinked(): void
    {
        $this->sahabi('anas-one', 'أنس بن مالك الأنصاري', 'Anas ibn Malik al-Ansari');
        $this->sahabi('anas-two', 'أنس بن مالك القشيري', 'Anas ibn Malik al-Qushayri');
        $companion = $this->companion('أنس بن مالك', 'Anas ibn Malik');

        $this->artisan('hadith:link-relations', ['--apply' => true, '--show' => 0])
            ->assertExitCode(0);

        $this->assertNull(DB::table('hadith_companions')->where('id', $companion)->value('sahaba_id'));
    }

    public function test_it_resolves_related_verses_to_quran_verse_ids_and_is_idempotent(): void
    {
        $surah = DB::table('quran_surahs')->insertGetId([
            'revelation_place' => 'medina', 'revelation_order' => 87, 'bismillah_pre' => 1,
            'name_ar' => 'النور', 'name_en' => 'An-Nur', 'verses_count' => 64,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $verse = DB::table('quran_verses')->insertGetId([
            'surah_id' => $surah, 'verse_number' => 35, 'verse_key' => $surah.':35',
            'juz_number' => 18, 'hizb_number' => 36, 'rub_el_hizb_number' => 143,
            'ruku_number' => 5, 'manzil_number' => 5, 'page_number' => 354,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $related = DB::table('hadith_related_verses')->insertGetId([
            'hadith_id' => $this->hadith(), 'surah' => $surah, 'ayah' => '35',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $outOfRange = DB::table('hadith_related_verses')->insertGetId([
            'hadith_id' => $this->hadith(), 'surah' => $surah, 'ayah' => '0',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->artisan('hadith:link-relations', ['--apply' => true, '--show' => 0])->assertExitCode(0);
        // Re-running must not collide with the links the first run wrote.
        $this->artisan('hadith:link-relations', ['--apply' => true, '--show' => 0])->assertExitCode(0);

        $this->assertSame($verse, DB::table('hadith_related_verses')->where('id', $related)->value('quran_verse_id'));
        $this->assertNull(DB::table('hadith_related_verses')->where('id', $outOfRange)->value('quran_verse_id'));
    }

    private function sahabi(string $slug, string $arabic, string $english): int
    {
        $id = DB::table('sahaba')->insertGetId([
            'slug' => $slug, 'gender' => 'male', 'person_type' => 'sahabi', 'is_sahabi' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        foreach (['ar' => $arabic, 'en' => $english] as $locale => $name) {
            DB::table('sahabi_translations')->insert([
                'sahabi_id' => $id, 'locale' => $locale, 'name' => $name, 'is_ready' => 1,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        return $id;
    }

    private function companion(string $arabic, string $english): int
    {
        return DB::table('hadith_companions')->insertGetId([
            'name_ar' => $arabic, 'name_en' => $english, 'verified' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function hadith(): int
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

        return DB::table('hadith_hadiths')->insertGetId([
            'book_id' => $book, 'hadith_number' => '1',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
