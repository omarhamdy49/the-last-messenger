<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Domain\Media\Models\MediaProgram;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Covers media:backfill-artwork — attaching the best-resolution YouTube
 * thumbnail as the program banner (maxres → sd → hq fallback), skipping
 * programs that already have artwork so admin uploads are never replaced.
 */
final class BackfillMediaArtworkTest extends TestCase
{
    use RefreshDatabase;

    private const VIDEO_ID = 'dQw4w9WgXcQ';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /**
     * Real JPEG bytes: Spatie's nonQueued conversions run inline on attach
     * and throw on non-image content, which would read as "not attached".
     */
    private function jpegBytes(): string
    {
        $image = imagecreatetruecolor(4, 4);
        ob_start();
        imagejpeg($image);

        return (string) ob_get_clean();
    }

    private function makeProgramWithEpisode(string $slug): int
    {
        $programId = (int) DB::table('media_programs')->insertGetId([
            'slug' => $slug,
            'category' => 'seerah',
            'maturity_rating' => 'all',
            'year' => 2024,
            'rating' => 8.5,
            'is_featured' => false,
            'is_trending' => false,
            'is_published' => true,
            'views_count' => 0,
            'display_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('media_episodes')->insert([
            'program_id' => $programId,
            'season_number' => 1,
            'episode_number' => 1,
            'slug' => "artwork-episode-{$programId}",
            'video_url' => 'https://www.youtube.com/embed/'.self::VIDEO_ID,
            'duration_seconds' => 600,
            'views_count' => 0,
            'is_featured' => false,
            'is_published' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $programId;
    }

    public function test_backfill_attaches_best_available_resolution_as_banner(): void
    {
        // maxres missing (404) — the command must fall through to sddefault.
        Http::fake([
            'i.ytimg.com/vi/*/maxresdefault.jpg' => Http::response('', 404),
            'i.ytimg.com/*' => Http::response($this->jpegBytes(), 200),
        ]);

        $programId = $this->makeProgramWithEpisode('needs-artwork');

        $this->artisan('media:backfill-artwork')->assertSuccessful();

        $program = MediaProgram::find($programId);
        $banner = $program->getFirstMedia('banner');
        $this->assertNotNull($banner, 'A banner should be attached from the CDN.');
        $this->assertSame(self::VIDEO_ID.'-banner.jpg', $banner->file_name);
        $this->assertNotNull($program->getFirstMedia('poster'), 'A poster should be attached too.');
        $this->assertNotNull(
            $program->episodes()->first()->getFirstMedia('thumbnail'),
            'The episode thumbnail should be attached too.',
        );
    }

    public function test_backfill_skips_programs_that_already_have_a_banner(): void
    {
        Http::fake(['i.ytimg.com/*' => Http::response($this->jpegBytes(), 200)]);

        $programId = $this->makeProgramWithEpisode('has-artwork');

        $this->artisan('media:backfill-artwork')->assertSuccessful();
        $this->artisan('media:backfill-artwork')->assertSuccessful();

        // Re-runs never duplicate or replace the existing banner.
        $this->assertSame(1, MediaProgram::find($programId)->getMedia('banner')->count());
    }

    public function test_backfill_skips_programs_without_a_youtube_episode(): void
    {
        Http::fake();

        $programId = (int) DB::table('media_programs')->insertGetId([
            'slug' => 'no-video-program',
            'category' => 'seerah',
            'maturity_rating' => 'all',
            'year' => 2024,
            'rating' => 8.5,
            'is_featured' => false,
            'is_trending' => false,
            'is_published' => true,
            'views_count' => 0,
            'display_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('media:backfill-artwork')->assertSuccessful();

        $this->assertNull(MediaProgram::find($programId)->getFirstMedia('banner'));
        Http::assertNothingSent();
    }
}
