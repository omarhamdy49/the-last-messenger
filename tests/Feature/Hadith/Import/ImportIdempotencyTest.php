<?php

declare(strict_types=1);

namespace Tests\Feature\Hadith\Import;

use App\Domain\Hadith\Import\HadithImporter;
use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Re-running an import must be safe. The corpus is fetched over 609 slow page
 * loads, so partial re-runs are normal operating procedure, not an exception.
 */
class ImportIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    private string $path;

    protected function setUp(): void
    {
        parent::setUp();
        HadithCollection::factory()->create([
            'code' => 'bukhari',
            'reference_template' => 'Sahih al-Bukhari {hadithNumber}',
        ]);
        $this->path = storage_path('app/idempotency_'.uniqid().'.json');
    }

    protected function tearDown(): void
    {
        @unlink($this->path);
        parent::tearDown();
    }

    private function write(array $records, array $chapters = []): void
    {
        file_put_contents($this->path, json_encode([
            'source' => ['collection' => 'bukhari', 'book_number' => '1', 'url' => 'https://sunnah.com/bukhari/1'],
            'book' => ['number' => '1', 'title' => ['en' => 'Revelation', 'ar' => 'بدء الوحى']],
            'counts' => ['chapters' => count($chapters), 'records' => count($records)],
            'chapters' => $chapters,
            'records' => $records,
        ], JSON_UNESCAPED_UNICODE));
    }

    private function import(bool $reset = false): void
    {
        app(HadithImporter::class)->importFile($this->path, 'bukhari', $reset);
    }

    private function record(string $number, array $overrides = []): array
    {
        return array_merge([
            'hadith_number' => $number,
            'sunnah_urn' => 10 + (int) $number,
            'sunnah_urn_en' => 100000 + (int) $number,
            'chapter_number' => '(1)',
            'reference_rows' => [['Reference', 'Sahih al-Bukhari '.$number], ['In-book reference', 'Book 1, Hadith '.$number]],
            'deprecated' => true,
            'texts' => [
                'ar' => ['body' => 'إنما الأعمال بالنيات'],
                'en' => ['body' => 'Actions are judged by intentions', 'narrated_by' => "Narrated 'Umar:"],
            ],
            'gradings' => [['locale' => 'en', 'raw_grade' => 'Sahih (Al-Albani)']],
        ], $overrides);
    }

    /** @return array<string, int> */
    private function counts(): array
    {
        return [
            'hadiths' => \DB::table('hadith_hadiths')->count(),
            'texts' => \DB::table('hadith_texts')->count(),
            'gradings' => \DB::table('hadith_gradings')->count(),
            'chapters' => \DB::table('hadith_chapters')->count(),
            'books' => \DB::table('hadith_books')->count(),
        ];
    }

    public function test_a_second_import_of_the_same_source_leaves_row_counts_unchanged(): void
    {
        $chapters = [['number' => '(1)', 'title' => ['en' => 'Chapter', 'ar' => 'باب'], 'intro' => [], 'sort_order' => 1]];
        $this->write([$this->record('1'), $this->record('2')], $chapters);

        $this->import();
        $first = $this->counts();

        $this->import();

        $this->assertSame($first, $this->counts());
        $this->assertSame(2, $first['hadiths']);
        $this->assertSame(4, $first['texts']);
        $this->assertSame(2, $first['gradings']);
    }

    public function test_a_reimport_removes_a_text_row_the_corrected_source_dropped(): void
    {
        $this->write([$this->record('1')]);
        $this->import();
        $this->assertSame(2, $this->counts()['texts']);

        // The corrected source no longer carries English.
        $this->write([$this->record('1', ['texts' => ['ar' => ['body' => 'إنما الأعمال بالنيات']]])]);
        $this->import();

        $this->assertSame(1, $this->counts()['texts']);
        $this->assertDatabaseMissing('hadith_texts', ['locale' => 'en']);
    }

    public function test_a_reimport_removes_a_grading_the_corrected_source_dropped(): void
    {
        $this->write([$this->record('1')]);
        $this->import();
        $this->assertSame(1, $this->counts()['gradings']);

        $this->write([$this->record('1', ['gradings' => []])]);
        $this->import();

        $this->assertSame(0, $this->counts()['gradings']);
    }

    /** A routine refresh must not undo an editor's decision to unpublish. */
    public function test_a_routine_reimport_preserves_editor_owned_publication(): void
    {
        $this->write([$this->record('1')]);
        $this->import();

        Hadith::query()->update(['is_published' => false]);

        $this->import();

        $this->assertFalse((bool) Hadith::query()->first()->is_published);
    }

    public function test_reset_restores_the_sources_view_of_editor_owned_fields(): void
    {
        $this->write([$this->record('1')]);
        $this->import();
        Hadith::query()->update(['is_published' => false]);

        $this->import(reset: true);

        $this->assertTrue((bool) Hadith::query()->first()->is_published);
    }

    public function test_a_truncated_source_file_is_refused_rather_than_partially_imported(): void
    {
        // counts.records says 2 but only one record is present — the shape a
        // half-written download takes. Importing it would silently lose data.
        file_put_contents($this->path, json_encode([
            'source' => ['collection' => 'bukhari', 'book_number' => '1'],
            'book' => ['number' => '1', 'title' => ['en' => 'Revelation']],
            'counts' => ['chapters' => 0, 'records' => 2],
            'chapters' => [],
            'records' => [$this->record('1')],
        ]));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('refusing a partial import');

        $this->import();
    }
}
