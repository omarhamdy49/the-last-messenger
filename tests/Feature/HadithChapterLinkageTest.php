<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithChapter;
use App\Domain\Hadith\Services\JsonImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifies the hadith → chapter linkage added by
 * 2026_06_11_000001_add_chapter_id_to_hadith_hadiths_table:
 * the column exists on the test driver, the importer resolves and links
 * chapters idempotently, and HadithChapter::hadiths() returns linked rows.
 */
class HadithChapterLinkageTest extends TestCase
{
    use RefreshDatabase;

    private string $fixturePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixturePath = tempnam(sys_get_temp_dir(), 'hadith_chapter_fixture').'.json';
        file_put_contents($this->fixturePath, json_encode($this->singleHadithFixture(), JSON_UNESCAPED_UNICODE));
    }

    protected function tearDown(): void
    {
        @unlink($this->fixturePath);

        parent::tearDown();
    }

    public function test_hadith_hadiths_table_has_chapter_id_column(): void
    {
        $this->assertTrue(
            \Illuminate\Support\Facades\Schema::hasColumn('hadith_hadiths', 'chapter_id'),
        );
    }

    public function test_import_links_hadith_to_resolved_chapter(): void
    {
        app(JsonImportService::class)->importFromFile($this->fixturePath);

        $hadith = Hadith::query()->where('hadith_number', '5')->firstOrFail();
        $chapter = HadithChapter::query()
            ->where('book_id', $hadith->book_id)
            ->where('chapter_number', 3)
            ->firstOrFail();

        $this->assertSame($chapter->id, $hadith->chapter_id);
        $this->assertSame($chapter->id, $hadith->chapter()->firstOrFail()->id);
    }

    public function test_chapter_hadiths_relation_returns_linked_hadiths(): void
    {
        app(JsonImportService::class)->importFromFile($this->fixturePath);

        $chapter = HadithChapter::query()->where('chapter_number', 3)->firstOrFail();
        $linked = $chapter->hadiths()->get();

        $this->assertCount(1, $linked);
        $this->assertSame('5', $linked->first()->hadith_number);
    }

    public function test_reimport_is_idempotent_and_keeps_linkage(): void
    {
        $service = app(JsonImportService::class);
        $service->importFromFile($this->fixturePath);
        $service->importFromFile($this->fixturePath);

        $this->assertSame(1, Hadith::query()->count());
        $this->assertSame(1, HadithChapter::query()->count());
        $this->assertNotNull(Hadith::query()->firstOrFail()->chapter_id);
    }

    /**
     * Minimal production-shaped single-hadith payload whose matched collection
     * entry carries book_number + chapter_number + chapter_name_* fields.
     *
     * @return array<string, mixed>
     */
    private function singleHadithFixture(): array
    {
        return [
            'hadith_text' => [
                'ar' => 'إنما الأعمال بالنيات',
                'en' => 'Actions are but by intentions.',
            ],
            'verification_status' => 'source_verified',
            'collections' => [
                [
                    'name_en' => 'Sahih al-Bukhari',
                    'name_ar' => 'صحيح البخاري',
                    'hadith_number' => '5',
                    'book_number' => '2',
                    'book_name_ar' => 'كتاب الإيمان',
                    'book_name_en' => 'Book of Faith',
                    'chapter_number' => '3',
                    'chapter_name_ar' => 'باب النية',
                    'chapter_name_en' => 'Chapter on Intention',
                    'source_url' => 'https://sunnah.com/bukhari:5',
                    'verified' => true,
                ],
            ],
            'grading' => [],
            'metadata' => [
                'id' => 5,
                'id_in_book' => 5,
                'collection' => [
                    'name_ar' => 'صحيح البخاري',
                    'name_en' => 'Sahih al-Bukhari',
                    'total_hadiths' => 7277,
                ],
            ],
        ];
    }
}
