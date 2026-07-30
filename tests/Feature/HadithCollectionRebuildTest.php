<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Hadith\Services\JsonImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use Tests\TestCase;

class HadithCollectionRebuildTest extends TestCase
{
    use RefreshDatabase;

    private string $portalRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->portalRoot = sys_get_temp_dir().'/hadith-rebuild-'.bin2hex(random_bytes(6));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->portalRoot);

        parent::tearDown();
    }

    public function test_two_consecutive_fixture_imports_have_identical_row_counts(): void
    {
        $service = app(JsonImportService::class);
        $first = $service->rebuildCollection('pilot', $this->fixtureRoot(), $this->portalRoot);
        $second = $service->rebuildCollection('pilot', $this->fixtureRoot(), $this->portalRoot);

        $this->assertSame($first['rows_written'], $second['rows_written']);
        $this->assertSame(1, $second['mapping']['inputs']['narrators']);
        $this->assertSame(1, $second['mapping']['outputs']['narrators']);
        $this->assertSame(4, $second['rows_written']['hadith_gradings']);
        $this->assertSame(4, $second['rows_written']['hadith_themes']);
        $this->assertSame(4, $second['rows_written']['hadith_keywords']);
        $this->assertSame(2, $second['rows_written']['hadith_applications']);
        $this->assertSame(7, $second['rows_written']['hadith_fiqh_rulings']);
        $this->assertSame(1, $second['mapping']['keyword_sources']['tags']);
        $this->assertSame(1, $second['mapping']['keyword_sources']['concepts']);
        $this->assertDatabaseHas('hadith_gradings', [
            'raw_grade' => 'Sahih [source wording preserved]',
            'grade_key' => 'sahih',
        ]);
        $this->assertDatabaseMissing('hadith_themes', [
            'name' => json_encode(['ar' => 'الإحسان', 'en' => 'Compassion'], JSON_UNESCAPED_UNICODE),
        ]);
        $this->assertSame(2, DB::table('hadith_gradings')
            ->where('raw_grade', 'Sahih')
            ->whereNotNull('scholar_id')
            ->count());
    }

    public function test_unmapped_grading_status_aborts_collection_import(): void
    {
        try {
            app(JsonImportService::class)->rebuildCollection(
                'unmapped',
                $this->fixtureRoot(),
                $this->portalRoot,
            );
            $this->fail('Expected the unmapped grading status to abort the import.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame(
                'Unmapped grading.status: citation only [unbalanced',
                $exception->getMessage(),
            );
        }

        $this->assertSame(0, DB::table('hadith_collections')->count());
        $this->assertSame(0, DB::table('hadith_hadiths')->count());
    }

    private function fixtureRoot(): string
    {
        return base_path('tests/Fixtures/HadithRebuild');
    }
}
