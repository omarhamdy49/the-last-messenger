<?php

declare(strict_types=1);

namespace Tests\Feature\Hadith\Import;

use App\Domain\Hadith\Import\HadithImporter;
use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithChapter;
use App\Domain\Hadith\Models\HadithCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression cover for a defect that produced NO null to notice: hadiths were
 * attributed to chapters by `chapter_number`, which does not identify a chapter.
 * Sahih al-Bukhari repeats a chapter number within a book 354 times and leaves
 * six chapters unnumbered, so 503 of 7,277 records pointed at the wrong chapter
 * while chapter_id looked perfectly populated.
 */
class ChapterAttributionTest extends TestCase
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
        $this->path = storage_path('app/chapter_attribution_'.uniqid().'.json');
    }

    protected function tearDown(): void
    {
        @unlink($this->path);
        parent::tearDown();
    }

    /**
     * @param  list<array{number: ?string, en: string}>  $chapters
     * @param  list<array{hadith: string, index: ?int, number: ?string}>  $records
     */
    private function importFixture(array $chapters, array $records): void
    {
        file_put_contents($this->path, json_encode([
            'source' => ['collection' => 'bukhari', 'book_number' => '65', 'url' => 'https://sunnah.com/bukhari/65'],
            'book' => ['number' => '65', 'title' => ['en' => 'Tafsir', 'ar' => 'التفسير']],
            'counts' => ['chapters' => count($chapters), 'records' => count($records)],
            'chapters' => array_map(static fn (array $c, int $i): array => [
                'number' => $c['number'],
                'title' => ['en' => $c['en'], 'ar' => 'باب'],
                'intro' => [],
                'sort_order' => $i + 1,
            ], $chapters, array_keys($chapters)),
            'records' => array_map(static fn (array $r): array => [
                'hadith_number' => $r['hadith'],
                'sunnah_urn' => null,
                'sunnah_urn_en' => null,
                'chapter_index' => $r['index'],
                'chapter_number' => $r['number'],
                'reference_rows' => [['Reference', 'Sahih al-Bukhari '.$r['hadith']]],
                'deprecated' => false,
                'texts' => ['ar' => ['body' => 'نص '.$r['hadith']]],
                'gradings' => [],
            ], $records),
        ], JSON_UNESCAPED_UNICODE));

        app(HadithImporter::class)->importFile($this->path, 'bukhari');
    }

    public function test_two_chapters_sharing_a_number_keep_their_own_hadiths(): void
    {
        $this->importFixture(
            chapters: [
                ['number' => '(1)', 'en' => 'First chapter one'],
                ['number' => '(2)', 'en' => 'Chapter two'],
                ['number' => '(1)', 'en' => 'Second chapter one'],
            ],
            records: [
                ['hadith' => '10', 'index' => 0, 'number' => '(1)'],
                ['hadith' => '11', 'index' => 1, 'number' => '(2)'],
                ['hadith' => '12', 'index' => 2, 'number' => '(1)'],
            ],
        );

        // A repeated number must not collapse two chapters into one.
        $this->assertDatabaseCount('hadith_chapters', 3);

        $first = Hadith::query()->where('hadith_number', '10')->value('chapter_id');
        $third = Hadith::query()->where('hadith_number', '12')->value('chapter_id');

        $this->assertNotSame($first, $third, 'hadiths 10 and 12 are in different chapters that share the number 1');
        $this->assertSame('First chapter one', HadithChapter::find($first)->getTranslation('title', 'en', true));
        $this->assertSame('Second chapter one', HadithChapter::find($third)->getTranslation('title', 'en', true));
    }

    public function test_unnumbered_chapters_stay_distinct(): void
    {
        // Book 65's Quranic-verse headings carry no number at all; under the old
        // key every one of them collapsed onto a single row.
        $this->importFixture(
            chapters: [
                ['number' => null, 'en' => 'Manna and quails'],
                ['number' => null, 'en' => 'No change in Khalq-illah'],
                ['number' => null, 'en' => 'The treasures of the heavens'],
            ],
            records: [
                ['hadith' => '4639', 'index' => 0, 'number' => null],
                ['hadith' => '4775', 'index' => 1, 'number' => null],
                ['hadith' => '4906', 'index' => 2, 'number' => null],
                ['hadith' => '4907', 'index' => 2, 'number' => null],
            ],
        );

        $this->assertDatabaseCount('hadith_chapters', 3);

        $titleFor = fn (string $number): string => HadithChapter::find(
            Hadith::query()->where('hadith_number', $number)->value('chapter_id')
        )->getTranslation('title', 'en', true);

        // These four are the real records the audit flagged; the expected titles
        // were read off sunnah.com's own pages for bukhari:4639 and :4907.
        $this->assertSame('Manna and quails', $titleFor('4639'));
        $this->assertSame('No change in Khalq-illah', $titleFor('4775'));
        $this->assertSame('The treasures of the heavens', $titleFor('4906'));
        $this->assertSame('The treasures of the heavens', $titleFor('4907'),
            'two hadiths may legitimately share one chapter');
    }

    public function test_a_record_with_no_chapter_gets_a_null_chapter_id(): void
    {
        $this->importFixture(
            chapters: [],
            records: [['hadith' => '1', 'index' => null, 'number' => null]],
        );

        $this->assertNull(Hadith::query()->where('hadith_number', '1')->value('chapter_id'));
    }

    /** An absent intro must stay a true NULL, not the literal JSON "[]". */
    public function test_a_chapter_without_an_intro_stores_null_not_an_empty_array(): void
    {
        $this->importFixture(
            chapters: [['number' => '(1)', 'en' => 'Chapter']],
            records: [['hadith' => '1', 'index' => 0, 'number' => '(1)']],
        );

        $this->assertSame(0, HadithChapter::query()->whereRaw('intro = ?', ['[]'])->count());
        $this->assertSame(1, HadithChapter::query()->whereNull('intro')->count());
    }
}
