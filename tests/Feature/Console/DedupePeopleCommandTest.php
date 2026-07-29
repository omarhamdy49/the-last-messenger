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

    public function test_it_refuses_to_merge_two_sahaba_of_different_gender(): void
    {
        $brother = $this->sahabi('al-harith-bin-suwayd', 'male', 'al-Harith bin Suwayd', 'الحارث بن سويد');
        $sister = $this->sahabi('zaynab-bint-suwayd', 'female', 'al-Harith bin Suwayd', 'الحارث بن سويد');

        $this->artisan('data:dedupe', ['--apply' => true, '--show' => 0])
            ->assertExitCode(0);

        $this->assertDatabaseHas('sahaba', ['id' => $brother]);
        $this->assertDatabaseHas('sahaba', ['id' => $sister]);
    }

    public function test_it_respects_the_sources_disambiguation_marker(): void
    {
        $first = $this->sahabi('amra-1', 'female', "'Amra bint Mas'ud #1", null);
        $second = $this->sahabi('amra-2', 'female', "'Amra bint Mas'ud #2", null);

        $this->artisan('data:dedupe', ['--apply' => true, '--show' => 0])
            ->assertExitCode(0);

        $this->assertDatabaseHas('sahaba', ['id' => $first]);
        $this->assertDatabaseHas('sahaba', ['id' => $second]);
    }

    public function test_it_merges_a_lineage_variant_and_moves_the_translations(): void
    {
        $short = $this->sahabi('al-bara-ibn-azib', 'male', "al-Bara' ibn 'Azib", null);
        $long = $this->sahabi('al-bara-bin-azib-bin-al-harith', 'male', "al-Bara' bin Azib bin al-Harith", 'البراء بن عازب بن الحارث');

        $this->artisan('data:dedupe', ['--apply' => true, '--show' => 0])
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
}
