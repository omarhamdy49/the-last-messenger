<?php

declare(strict_types=1);

namespace Tests\Feature\Quran\Import;

use App\Domain\Quran\Import\QuranFoundation\Importers\AssetDownloader;
use App\Domain\Quran\Models\Chapter;
use App\Domain\Quran\Models\Mushaf;
use App\Domain\Quran\Models\Verse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class AssetDownloaderTest extends TestCase
{
    use RefreshDatabase;

    private string $fontsDir;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        Storage::fake('public');
        config(['quran.qf.fonts_base' => 'https://fonts.test/hafs', 'quran.assets.fonts_public_path' => 'fonts/quran-test-'.getmypid()]);
        $this->fontsDir = public_path('fonts/quran-test-'.getmypid());
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->fontsDir);
        parent::tearDown();
    }

    public function test_verse_images_are_stored_and_failures_itemised(): void
    {
        Chapter::factory()->number(1)->withVerses(2)->create();
        Verse::query()->where('verse_key', '1:1')->update(['image_url' => '//images.test/1_1.png']);
        Verse::query()->where('verse_key', '1:2')->update(['image_url' => '//images.test/1_2.png']);

        Http::fake([
            'images.test/1_1.png' => Http::response('PNGDATA'),
            'images.test/1_2.png' => Http::response('', 404),
        ]);

        $report = app(AssetDownloader::class)->images();

        Storage::disk('public')->assertExists('quran/verse-images/1_1.png');
        $this->assertSame('quran/verse-images/1_1.png', Verse::query()->where('verse_key', '1:1')->value('image_path'));
        $this->assertSame(1, $report->written);
        $this->assertCount(1, $report->failed);
        $this->assertStringContainsString('1_2.png', $report->failed[0]['key']);
        $this->assertFalse($report->passed());

        Http::fake(['images.test/*' => Http::response('PNGDATA')]);
        $again = app(AssetDownloader::class)->images();
        $this->assertSame(1, $again->unchanged); // existing file skipped
    }

    public function test_single_file_fonts_download_and_mark_mushafs(): void
    {
        Mushaf::factory()->create(['id' => 5, 'font_version' => 'hafs']);
        Http::fake(['fonts.test/*' => Http::response('WOFF2')]);

        $report = app(AssetDownloader::class)->fonts(['hafs']);

        $this->assertTrue($report->passed());
        $this->assertFileExists($this->fontsDir.'/hafs/UthmanicHafs1Ver18.woff2');
        $this->assertNotNull(Mushaf::query()->findOrFail(5)->fonts_downloaded_at);
    }

    public function test_unknown_font_version_is_reported(): void
    {
        $report = app(AssetDownloader::class)->fonts(['v9']);

        $this->assertSame('font:v9', $report->failed[0]['key']);
    }
}
