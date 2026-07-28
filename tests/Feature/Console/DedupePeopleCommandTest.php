<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Merging two people is destructive and unrecoverable from the app: the losing
 * row is deleted and every reference is rewritten. These tests pin the guards
 * that decide a merge must NOT happen.
 */
final class DedupePeopleCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_merges_transliteration_variants_and_keeps_every_reference(): void
    {
        $canonical = $this->companion('أبو هريرة', 'Abu Hurayrah');
        $variant = $this->companion(null, 'Abu Huraira');

        $kept = $this->hadithFor($canonical);
        $moved = $this->hadithFor($variant);

        $this->artisan('data:dedupe', ['--apply' => true, '--only' => 'companions', '--show' => 0])
            ->assertExitCode(0);

        $this->assertDatabaseMissing('hadith_companions', ['id' => $variant]);
        $this->assertSame($canonical, DB::table('hadith_hadiths')->where('id', $kept)->value('companion_id'));
        $this->assertSame($canonical, DB::table('hadith_hadiths')->where('id', $moved)->value('companion_id'));
    }

    public function test_it_keeps_abu_bakra_and_abu_bakr_apart(): void
    {
        $bakra = $this->companion('أبو بكرة', 'Abu Bakra');
        $bakr = $this->companion('أبو بكر', 'Abu Bakr');
        $this->hadithFor($bakra);
        $this->hadithFor($bakr);

        $this->artisan('data:dedupe', ['--apply' => true, '--only' => 'companions', '--show' => 0])
            ->assertExitCode(0);

        $this->assertDatabaseHas('hadith_companions', ['id' => $bakra]);
        $this->assertDatabaseHas('hadith_companions', ['id' => $bakr]);
    }

    public function test_it_never_merges_a_placeholder_into_a_person(): void
    {
        $unknown = $this->companion('غير معروف', 'Unknown');
        $other = $this->companion(null, 'Unknown');
        $this->hadithFor($unknown);
        $this->hadithFor($other);

        $this->artisan('data:dedupe', ['--apply' => true, '--only' => 'companions', '--show' => 0])
            ->assertExitCode(0);

        $this->assertDatabaseHas('hadith_companions', ['id' => $unknown]);
        $this->assertDatabaseHas('hadith_companions', ['id' => $other]);
    }

    public function test_it_refuses_to_merge_two_sahaba_of_different_gender(): void
    {
        $brother = $this->sahabi('al-harith-bin-suwayd', 'male', 'al-Harith bin Suwayd', 'الحارث بن سويد');
        $sister = $this->sahabi('zaynab-bint-suwayd', 'female', 'al-Harith bin Suwayd', 'الحارث بن سويد');

        $this->artisan('data:dedupe', ['--apply' => true, '--only' => 'sahaba', '--show' => 0])
            ->assertExitCode(0);

        $this->assertDatabaseHas('sahaba', ['id' => $brother]);
        $this->assertDatabaseHas('sahaba', ['id' => $sister]);
    }

    public function test_it_respects_the_sources_disambiguation_marker(): void
    {
        $first = $this->sahabi('amra-1', 'female', "'Amra bint Mas'ud #1", null);
        $second = $this->sahabi('amra-2', 'female', "'Amra bint Mas'ud #2", null);

        $this->artisan('data:dedupe', ['--apply' => true, '--only' => 'sahaba', '--show' => 0])
            ->assertExitCode(0);

        $this->assertDatabaseHas('sahaba', ['id' => $first]);
        $this->assertDatabaseHas('sahaba', ['id' => $second]);
    }

    public function test_it_merges_a_lineage_variant_and_moves_the_translations(): void
    {
        $short = $this->sahabi('al-bara-ibn-azib', 'male', "al-Bara' ibn 'Azib", null);
        $long = $this->sahabi('al-bara-bin-azib-bin-al-harith', 'male', "al-Bara' bin Azib bin al-Harith", 'البراء بن عازب بن الحارث');

        $this->artisan('data:dedupe', ['--apply' => true, '--only' => 'sahaba', '--show' => 0])
            ->assertExitCode(0);

        $survivors = DB::table('sahaba')->whereIn('id', [$short, $long])->pluck('id');
        $this->assertCount(1, $survivors, 'exactly one of the two lineage variants should remain');

        $this->assertSame(
            0,
            DB::table('sahabi_translations')->whereNotIn('sahabi_id', $survivors)->count(),
            'translations must follow the surviving record, never dangle',
        );
    }

    private function sahabi(string $slug, string $gender, string $english, ?string $arabic): int
    {
        $id = DB::table('sahaba')->insertGetId([
            'slug' => $slug, 'gender' => $gender, 'person_type' => 'sahabi', 'is_sahabi' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('sahabi_translations')->insert([
            'sahabi_id' => $id, 'locale' => 'en', 'name' => $english, 'is_ready' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        if ($arabic !== null) {
            DB::table('sahabi_translations')->insert([
                'sahabi_id' => $id, 'locale' => 'ar', 'name' => $arabic, 'is_ready' => 1,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        return $id;
    }

    private function companion(?string $arabic, string $english): int
    {
        return DB::table('hadith_companions')->insertGetId([
            'name_ar' => $arabic, 'name_en' => $english, 'verified' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function hadithFor(int $companionId): int
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
            'book_id' => $book, 'companion_id' => $companionId, 'hadith_number' => '1',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
