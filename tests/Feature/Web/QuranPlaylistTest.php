<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class QuranPlaylistTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_playlist_tables_exist(): void
    {
        $this->assertTrue(Schema::hasColumns('quran_playlists', ['user_id', 'name', 'slug', 'scope', 'is_public', 'is_featured', 'plays_count', 'items_count']));
        $this->assertTrue(Schema::hasColumns('quran_playlist_items', ['playlist_id', 'surah_id', 'reciter_id']));
    }
}
