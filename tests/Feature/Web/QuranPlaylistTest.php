<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Domain\Quran\DTOs\PlaylistData;
use App\Domain\Quran\Models\Chapter;
use App\Domain\Quran\Models\ChapterAudioFile;
use App\Domain\Quran\Models\Playlist;
use App\Domain\Quran\Models\QuranResource;
use App\Domain\Quran\Services\PlaylistService;
use App\Jobs\RecordPlaylistPlayJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
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
        $this->assertTrue(Schema::hasColumns('quran_playlist_items', ['playlist_id', 'chapter_id', 'resource_id']));
    }

    // ------------------------------------------------------------ fixtures

    /** @return list<int> chapter numbers 1..n */
    private function surahs(int $n): array
    {
        $ids = [];
        for ($i = 1; $i <= $n; $i++) {
            $ids[] = Chapter::factory()->number($i)->create(['name_arabic' => 'س'.$i, 'name_simple' => 'S'.$i, 'verses_count' => 3])->id;
        }

        return $ids;
    }

    /**
     * One chapter reciter with a file for each covered chapter. In 006 the "person" and
     * the "recording" are the same resource.
     *
     * @param  list<int>  $covers
     * @return array{0: int, 1: int} [reciter id, reciter id]
     */
    private function person(string $slug, array $covers): array
    {
        $reciter = QuranResource::factory()->chapterReciter()->create(['slug' => $slug, 'name' => $slug, 'is_active' => true, 'display_order' => 1]);
        $reciter->syncTranslations(['name' => ['ar' => 'قارئ '.$slug]]);
        foreach ($covers as $chapter) {
            ChapterAudioFile::factory()->create(['resource_id' => $reciter->id, 'chapter_id' => $chapter, 'url' => "https://cdn.test/{$slug}/{$chapter}.mp3", 'duration_ms' => 90000]);
        }

        return [$reciter->id, $reciter->id];
    }

    private function makePlaylist(User $owner, array $items, bool $public, string $name = 'ختمتي'): Playlist
    {
        return app(PlaylistService::class)->create($owner->id, new PlaylistData($name, null, 'custom', $items, $public));
    }

    /** @param list<int> $profiles */
    private function payload(array $profiles, array $surahs, array $over = []): array
    {
        $preview = app(PlaylistService::class)->assign($profiles, $surahs);

        return $over + ['name' => 'ختمتي', 'description' => null, 'scope' => 'custom', 'items' => $preview->items, 'is_public' => false];
    }

    // ------------------------------------------------------------ wizard + preview

    public function test_guest_cannot_open_wizard(): void
    {
        $this->get(route('public.quran.playlists.create'))->assertRedirect();
    }

    public function test_preview_assigns_only_covering_recordings(): void
    {
        $this->surahs(3);
        [$a, $recA] = $this->person('a', [1, 2]);

        $res = $this->actingAs(User::factory()->create())
            ->postJson(route('public.quran.playlists.preview'), ['profile_ids' => [$a], 'scope' => 'custom', 'surah_ids' => [1, 2, 3]])
            ->assertOk();

        $this->assertSame([1 => $recA, 2 => $recA], array_map('intval', $res->json('items')));
        $this->assertSame([3], $res->json('uncovered'));
        $this->assertSame([(string) $a => [$recA]], $res->json('alternatives.1'));
    }

    // ------------------------------------------------------------ store

    public function test_store_persists_previewed_items(): void
    {
        $surahs = $this->surahs(2);
        [$a] = $this->person('a', $surahs);
        $user = User::factory()->create();

        $res = $this->actingAs($user)->postJson(route('public.quran.playlists.store'), $this->payload([$a], $surahs))->assertCreated();

        $playlist = Playlist::query()->firstOrFail();
        $this->assertSame($user->id, $playlist->user_id);
        $this->assertSame(2, $playlist->items_count);
        $this->assertMatchesRegularExpression('/^[a-z0-9]{12}$/', $playlist->slug);
        $this->assertSame(route('public.quran.playlists.show', $playlist->slug), $res->json('redirect'));
    }

    public function test_store_rejects_pair_without_audio(): void
    {
        $surahs = $this->surahs(2);
        [$a, $recA] = $this->person('a', [1]);

        $this->actingAs(User::factory()->create())
            ->postJson(route('public.quran.playlists.store'), $this->payload([$a], $surahs, ['items' => [1 => $recA, 2 => $recA]]))
            ->assertUnprocessable()
            ->assertJsonValidationErrorFor('items.2');
    }

    public function test_store_enforces_playlist_cap(): void
    {
        $surahs = $this->surahs(1);
        [$a] = $this->person('a', $surahs);
        $user = User::factory()->create();
        for ($i = 0; $i < 20; $i++) {
            $user->quranPlaylists()->create(['name' => 'p'.$i, 'scope' => 'full']);
        }

        $this->actingAs($user)->postJson(route('public.quran.playlists.store'), $this->payload([$a], $surahs))->assertUnprocessable();
    }

    // ------------------------------------------------------------ visibility

    public function test_private_playlist_is_404_for_guest_and_others_but_visible_to_owner(): void
    {
        $surahs = $this->surahs(1);
        [, $recA] = $this->person('a', $surahs);
        $owner = User::factory()->create();
        $playlist = $this->makePlaylist($owner, [1 => $recA], false);

        $this->get(route('public.quran.playlists.show', $playlist->slug))->assertNotFound();
        $this->actingAs(User::factory()->create())->get(route('public.quran.playlists.show', $playlist->slug))->assertNotFound();
        $this->actingAs($owner)->get(route('public.quran.playlists.show', $playlist->slug))
            ->assertOk()
            // Owner controls: the more-menu with edit + inline delete confirmation.
            ->assertSee(__('quran.playlists.more'))
            ->assertSee(route('public.quran.playlists.edit', $playlist->slug), false)
            ->assertSee(__('quran.playlists.delete_confirm_btn'));
    }

    public function test_public_playlist_is_visible_to_guest_and_plays_increment(): void
    {
        $surahs = $this->surahs(1);
        [, $recA] = $this->person('a', $surahs);
        $playlist = $this->makePlaylist(User::factory()->create(), [1 => $recA], true, 'قائمة الفجر');

        $this->get(route('public.quran.playlists.show', $playlist->slug))->assertOk()->assertSee('قائمة الفجر');
        // QUEUE_CONNECTION=sync in tests: the tracking job runs inline, end to end.
        $this->post(route('public.quran.playlists.plays', $playlist->slug))->assertOk();
        $this->assertSame(1, $playlist->fresh()->plays_count);
    }

    public function test_play_is_queued_on_tracking_and_deduped_per_visitor(): void
    {
        $surahs = $this->surahs(1);
        [, $recA] = $this->person('a', $surahs);
        $playlist = $this->makePlaylist(User::factory()->create(), [1 => $recA], true);
        Queue::fake();

        $listener = User::factory()->create();
        $this->actingAs($listener)->postJson(route('public.quran.playlists.plays', $playlist->slug))->assertOk();
        $this->actingAs($listener)->postJson(route('public.quran.playlists.plays', $playlist->slug))->assertOk();

        Queue::assertPushedOn('tracking', RecordPlaylistPlayJob::class);
        Queue::assertPushed(RecordPlaylistPlayJob::class, 1);
        // Nothing was written on the request path.
        $this->assertSame(0, $playlist->fresh()->plays_count);

        // A different visitor is counted separately.
        $this->actingAs(User::factory()->create())->postJson(route('public.quran.playlists.plays', $playlist->slug))->assertOk();
        Queue::assertPushed(RecordPlaylistPlayJob::class, 2);
    }

    public function test_play_job_increments_and_tolerates_a_deleted_playlist(): void
    {
        $surahs = $this->surahs(1);
        [, $recA] = $this->person('a', $surahs);
        $playlist = $this->makePlaylist(User::factory()->create(), [1 => $recA], true);

        (new RecordPlaylistPlayJob($playlist->id))->handle();
        (new RecordPlaylistPlayJob($playlist->id))->handle();
        $this->assertSame(2, $playlist->fresh()->plays_count);

        $id = $playlist->id;
        $playlist->delete();
        (new RecordPlaylistPlayJob($id))->handle(); // no row, no exception
        $this->assertDatabaseMissing('quran_playlists', ['id' => $id]);
    }

    public function test_private_playlist_play_is_404_and_not_queued(): void
    {
        $surahs = $this->surahs(1);
        [, $recA] = $this->person('a', $surahs);
        $playlist = $this->makePlaylist(User::factory()->create(), [1 => $recA], false);
        Queue::fake();

        $this->postJson(route('public.quran.playlists.plays', $playlist->slug))->assertNotFound();
        Queue::assertNothingPushed();
    }

    public function test_gallery_lists_public_playlists_plus_the_viewers_own_private_ones(): void
    {
        $surahs = $this->surahs(1);
        [, $recA] = $this->person('a', $surahs);
        $owner = User::factory()->create();
        $this->makePlaylist($owner, [1 => $recA], true, 'عامة جدًا');
        $this->makePlaylist($owner, [1 => $recA], false, 'سرية جدًا');

        $this->get(route('public.quran.playlists.index'))->assertOk()->assertSee('عامة جدًا')->assertDontSee('سرية جدًا');
        $this->actingAs(User::factory()->create())->get(route('public.quran.playlists.index'))->assertOk()->assertDontSee('سرية جدًا');
        $this->actingAs($owner)->get(route('public.quran.playlists.index'))->assertOk()->assertSee('سرية جدًا')->assertSee(__('quran.playlists.private'));
    }

    // ------------------------------------------------------------ owner actions

    public function test_owner_can_update_toggle_and_delete_and_others_cannot(): void
    {
        $surahs = $this->surahs(1);
        [$a, $recA] = $this->person('a', $surahs);
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $playlist = $this->makePlaylist($owner, [1 => $recA], false);

        $this->actingAs($other)->putJson(route('public.quran.playlists.update', $playlist->slug), $this->payload([$a], $surahs))->assertNotFound();
        $this->actingAs($owner)->putJson(route('public.quran.playlists.update', $playlist->slug), $this->payload([$a], $surahs, ['name' => 'جديد']))->assertOk();
        $this->assertSame('جديد', $playlist->fresh()->name);

        $this->actingAs($owner)->patchJson(route('public.quran.playlists.visibility', $playlist->slug), ['is_public' => true])->assertOk();
        $this->assertTrue($playlist->fresh()->is_public);

        $this->actingAs($other)->deleteJson(route('public.quran.playlists.destroy', $playlist->slug))->assertNotFound();
        $this->actingAs($owner)->deleteJson(route('public.quran.playlists.destroy', $playlist->slug))->assertOk();
        $this->assertDatabaseMissing('quran_playlists', ['id' => $playlist->id]);
    }

    // ------------------------------------------------------------ entry points

    public function test_reader_accepts_reciter_query_and_ignores_invalid(): void
    {
        $surahs = $this->surahs(1);
        [, $recA] = $this->person('a', $surahs);

        $this->get(route('public.quran.surah', 1).'?reciter='.$recA)->assertOk()->assertViewHas('forcedReciterId', $recA);
        $this->get(route('public.quran.surah', 1).'?reciter=999999')->assertOk()->assertViewHas('forcedReciterId', null);
    }

    public function test_profile_lists_own_playlists(): void
    {
        $surahs = $this->surahs(1);
        [, $recA] = $this->person('a', $surahs);
        $owner = User::factory()->create();
        $this->makePlaylist($owner, [1 => $recA], false, 'قائمة الفجر');

        $this->actingAs($owner)->get(route('public.profile'))->assertOk()->assertSee('قائمة الفجر');
    }

    public function test_quran_home_links_to_the_playlists_page(): void
    {
        $this->get(route('public.quran'))->assertOk()->assertSee(route('public.quran.playlists.index'), false);
    }

    public function test_items_are_stored_and_listed_in_surah_order_whatever_order_was_sent(): void
    {
        $surahs = $this->surahs(5);
        [$a] = $this->person('a', $surahs);
        $preview = app(PlaylistService::class)->assign([$a], $surahs)->items;
        $shuffled = [5 => $preview[5], 2 => $preview[2], 4 => $preview[4], 1 => $preview[1], 3 => $preview[3]];

        $res = $this->actingAs(User::factory()->create())
            ->postJson(route('public.quran.playlists.store'), ['name' => 'ترتيب', 'scope' => 'custom', 'items' => $shuffled, 'is_public' => true])
            ->assertCreated();

        $playlist = Playlist::query()->firstOrFail();
        $this->assertSame([1, 2, 3, 4, 5], $playlist->items->pluck('chapter_id')->all());
        $this->assertSame([1, 2, 3, 4, 5], array_column(app(PlaylistService::class)->tracks($playlist, 'ar'), 'surah'));
        $html = $this->get($res->json('redirect'))->assertOk()->getContent();
        $this->assertLessThan(strpos($html, 'س2'), strpos($html, 'س1'));
        $this->assertLessThan(strpos($html, 'س5'), strpos($html, 'س4'));
    }

    public function test_audio_endpoint_redirects_or_returns_json_and_404s_without_audio(): void
    {
        $this->surahs(2);
        [, $recA] = $this->person('a', [1]);

        $this->get(route('public.quran.playlists.audio', [1, $recA]))->assertRedirect('https://cdn.test/a/1.mp3');
        $this->getJson(route('public.quran.playlists.audio', [1, $recA]))->assertOk()->assertExactJson(['url' => 'https://cdn.test/a/1.mp3']);
        $this->getJson(route('public.quran.playlists.audio', [2, $recA]))->assertNotFound();
    }

    public function test_wizard_prefills_a_numbered_default_name_per_locale(): void
    {
        $this->surahs(1);
        [, $recA] = $this->person('a', [1]);
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('public.quran.playlists.create'))->assertOk()->assertSee('قائمة #١', false);

        $this->makePlaylist($user, [1 => $recA], false);
        $this->actingAs($user)->get(route('public.quran.playlists.create'))->assertOk()->assertSee('قائمة #٢', false);

        $this->actingAs($user)->withSession(['locale' => 'en'])
            ->get(route('public.quran.playlists.create'))->assertOk()->assertSee('Playlist #2', false);
    }

    public function test_download_streams_a_zip_of_the_tracks_and_skips_dead_links(): void
    {
        $surahs = $this->surahs(2);
        [, $recA] = $this->person('a', $surahs);
        $owner = User::factory()->create();
        $playlist = $this->makePlaylist($owner, [1 => $recA, 2 => $recA], true);

        Http::fake([
            '*cdn.test/a/1.mp3' => Http::response('AUDIO-ONE'),
            '*cdn.test/a/2.mp3' => Http::response('', 404),
        ]);

        $this->get(route('public.quran.playlists.download', $playlist->slug))->assertRedirect(); // guests log in first

        $response = $this->actingAs($owner)->get(route('public.quran.playlists.download', $playlist->slug))->assertOk();
        $path = tempnam(sys_get_temp_dir(), 'pl').'.zip';
        file_put_contents($path, $response->streamedContent());

        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($path) === true);
        $this->assertSame(1, $zip->numFiles, 'the 404 track is skipped, not zipped');
        $this->assertSame('001 - س1 - قارئ a.mp3', $zip->getNameIndex(0));
        $this->assertSame('AUDIO-ONE', $zip->getFromIndex(0));
        $zip->close();
        unlink($path);
    }
}
