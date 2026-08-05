<?php

declare(strict_types=1);

namespace Tests\Feature\Hadith\Import;

use App\Domain\Hadith\Enums\HadithGrade;
use App\Domain\Hadith\Import\HadithImporter;
use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithCollection;
use App\Domain\Hadith\Models\HadithGrading;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FR-006. The source's `authenticated_by` names the compiler of the collection
 * the hadith already sits in — "Imam al-Bukhari" on a hadith in Sahih al-Bukhari
 * is membership, not an independent verdict. A previous importer created a row
 * per entry and produced 9,379 + 8,863 bogus gradings. This must produce none.
 */
class ProvenanceIsNotGradingTest extends TestCase
{
    use RefreshDatabase;

    private function importPayload(array $records, string $code = 'bukhari'): void
    {
        HadithCollection::factory()->create([
            'code' => $code,
            'reference_template' => 'Sahih al-Bukhari {hadithNumber}',
        ]);

        $path = storage_path('app/hadith_source_test_'.uniqid().'.json');
        file_put_contents($path, json_encode([
            'source' => ['collection' => $code, 'book_number' => '1', 'url' => 'https://sunnah.com/'.$code.'/1'],
            'book' => ['number' => '1', 'title' => ['en' => 'Revelation', 'ar' => 'بدء الوحى']],
            'counts' => ['chapters' => 0, 'records' => count($records)],
            'chapters' => [],
            'records' => $records,
        ], JSON_UNESCAPED_UNICODE));

        try {
            app(HadithImporter::class)->importFile($path, $code);
        } finally {
            @unlink($path);
        }
    }

    private function record(array $overrides = []): array
    {
        return array_merge([
            'hadith_number' => '1',
            'sunnah_urn' => 10,
            'sunnah_urn_en' => 100010,
            'chapter_number' => null,
            'reference_rows' => [['Reference', 'Sahih al-Bukhari 1'], ['In-book reference', 'Book 1, Hadith 1']],
            'deprecated' => true,
            'texts' => ['ar' => ['body' => 'إنما الأعمال بالنيات']],
            'gradings' => [],
        ], $overrides);
    }

    public function test_a_record_with_no_grade_table_produces_zero_grading_rows(): void
    {
        // This is the real shape of Sahih al-Bukhari: it carries no grade table,
        // because inclusion in the collection IS the grading.
        $this->importPayload([$this->record()]);

        $this->assertDatabaseCount('hadith_gradings', 0);
        $this->assertNull(Hadith::query()->first()->display_grade);
    }

    public function test_an_authenticated_by_style_field_is_never_read_as_a_grading(): void
    {
        // Even when the payload carries the compiler's name in a provenance
        // field, no grading row may result: only `gradings` entries do that.
        $this->importPayload([$this->record([
            'authenticated_by' => 'Imam al-Bukhari',
            'grading' => ['authenticated_by' => 'Imam al-Bukhari', 'verified' => true],
        ])]);

        $this->assertDatabaseCount('hadith_gradings', 0);
    }

    public function test_a_real_grade_string_does_produce_a_grading(): void
    {
        // The control: the importer is not simply ignoring gradings altogether.
        $this->importPayload([$this->record([
            'gradings' => [['locale' => 'en', 'raw_grade' => 'Sahih (Al-Albani)']],
        ])]);

        $this->assertDatabaseCount('hadith_gradings', 1);
        $this->assertDatabaseHas('hadith_gradings', [
            'grade' => 'sahih',
            'scope' => 'hadith',
            'raw_grade' => 'Sahih (Al-Albani)',
        ]);
        $this->assertSame(HadithGrade::SAHIH, Hadith::query()->first()->display_grade);
    }

    public function test_the_grader_named_in_the_string_becomes_a_scholar(): void
    {
        $this->importPayload([$this->record([
            'gradings' => [['locale' => 'en', 'raw_grade' => 'Sahih (Al-Albani)']],
        ])]);

        $this->assertDatabaseHas('hadith_scholars', ['slug' => 'al-albani']);
        $this->assertNotNull(HadithGrading::query()->first()->scholar_id);
    }

    public function test_conflicting_hadith_scope_verdicts_yield_null(): void
    {
        $this->importPayload([$this->record([
            'gradings' => [
                ['locale' => 'en', 'raw_grade' => 'Sahih (Al-Albani)'],
                ['locale' => 'en', 'raw_grade' => "Da'if (Darussalam)"],
            ],
        ])]);

        $this->assertDatabaseCount('hadith_gradings', 2);
        $this->assertNull(Hadith::query()->first()->display_grade);
    }

    public function test_an_isnad_scope_verdict_does_not_set_the_display_grade(): void
    {
        // A verdict on the chain says nothing about the text, so display_grade
        // stays null rather than inheriting the chain's weakness.
        $this->importPayload([$this->record([
            'gradings' => [['locale' => 'en', 'raw_grade' => "Isnād Da'īf (Zubair `Aliza'i)"]],
        ])]);

        $this->assertDatabaseHas('hadith_gradings', ['scope' => 'isnad', 'grade' => 'daif']);
        $this->assertNull(Hadith::query()->first()->display_grade);
    }
}
