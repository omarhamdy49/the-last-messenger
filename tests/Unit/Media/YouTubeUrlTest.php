<?php

declare(strict_types=1);

namespace Tests\Unit\Media;

use App\Domain\Media\Support\YouTubeUrl;
use PHPUnit\Framework\TestCase;

final class YouTubeUrlTest extends TestCase
{
    public function test_extracts_video_id_from_every_url_shape(): void
    {
        $id = 'CkxLaZ6m9oQ';

        $this->assertSame($id, YouTubeUrl::videoId("https://www.youtube.com/watch?v={$id}"));
        $this->assertSame($id, YouTubeUrl::videoId("https://www.youtube.com/watch?v={$id}&list=PLxyz"));
        $this->assertSame($id, YouTubeUrl::videoId("https://www.youtube.com/embed/{$id}"));
        $this->assertSame($id, YouTubeUrl::videoId("https://www.youtube.com/shorts/{$id}"));
        $this->assertSame($id, YouTubeUrl::videoId("https://youtu.be/{$id}"));
        $this->assertSame($id, YouTubeUrl::videoId($id));
    }

    public function test_returns_null_for_non_video_values(): void
    {
        $this->assertNull(YouTubeUrl::videoId(null));
        $this->assertNull(YouTubeUrl::videoId(''));
        $this->assertNull(YouTubeUrl::videoId('https://example.com/video'));
        $this->assertNull(YouTubeUrl::videoId('not-an-id'));
    }

    public function test_extracts_playlist_id(): void
    {
        $this->assertSame('PLiJggHtOlnN2', YouTubeUrl::playlistId('https://www.youtube.com/playlist?list=PLiJggHtOlnN2'));
        $this->assertSame('PLiJggHtOlnN2', YouTubeUrl::playlistId('https://www.youtube.com/watch?v=abc12345678&list=PLiJggHtOlnN2'));
        $this->assertNull(YouTubeUrl::playlistId('https://www.youtube.com/watch?v=abc12345678'));
        $this->assertTrue(YouTubeUrl::isPlaylist('https://www.youtube.com/playlist?list=PLxyz123'));
    }

    public function test_builds_canonical_urls(): void
    {
        $this->assertSame('https://www.youtube.com/embed/abc12345678', YouTubeUrl::embedUrl('abc12345678'));
        $this->assertSame('https://www.youtube.com/watch?v=abc12345678', YouTubeUrl::watchUrl('abc12345678'));
        $this->assertSame('https://www.youtube.com/playlist?list=PLx', YouTubeUrl::playlistUrl('PLx'));
    }
}
