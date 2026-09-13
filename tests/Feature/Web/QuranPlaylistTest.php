<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Domain\Quran\DTOs\PlaylistData;
use App\Domain\Quran\Models\Playlist;
use App\Domain\Quran\Models\Recitation;
use App\Domain\Quran\Models\Reciter;
use App\Domain\Quran\Models\ReciterProfile;
use App\Domain\Quran\Models\Surah;
use App\Domain\Quran\Services\PlaylistService;
use App\Models\User;
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

    // ------------------------------------------------------------ fixtures

    /** @return list<int> surah ids 1..n */
    private function surahs(int $n): array
    {
        $ids = [];
        for ($i = 1; $i <= $n; $i++) {
            $ids[] = Surah::query()->forceCreate([
                'id' => $i, 'revelation_place' => 'makkah', 'revelation_order' => $i, 'bismillah_pre' => true,
                'name_ar' => 'س'.$i, 'name_en' => 'S'.$i, 'transliteration' => 'S'.$i, 'verses_count' => 3,
            ])->id;
        }

        return $ids;
    }

    /**
     * @param  list<int>  $covers
     * @return array{0: int, 1: int} [profile id, recording id]
     */
    private function person(string $slug, array $covers): array
    {
        $profile = ReciterProfile::query()->create(['identity_key' => 'test:'.$slug, 'slug' => $slug, 'is_active' => true]);
        $profile->translations()->create(['locale' => 'ar', 'name' => 'قارئ '.$slug]);
        $recording = Reciter::query()->create(['slug' => $slug.'-rec', 'profile_id' => $profile->id, 'kind' => 'recitation', 'is_active' => true, 'display_order' => 1]);
        $recording->translations()->create(['locale' => 'ar', 'name' => $slug, 'is_ready' => true]);
        foreach ($covers as $surah) {
            Recitation::query()->create(['reciter_id' => $recording->id, 'surah_id' => $surah, 'audio_url' => "https://cdn.test/{$slug}/{$surah}.mp3", 'format' => 'mp3', 'duration_seconds' => 90]);
        }
        $profile->update(['default_recording_id' => $recording->id]);

        return [$profile->id, $recording->id];
    }

    private function makePlaylist(User $owner, array $items, bool $public, string $name = 'ختمتي'): Playlist
    {
        return app(PlaylistService::class)->create($owner, new PlaylistData($name, null, 'custom', $items, $public));
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
        $this->actingAs($owner)->get(route('public.quran.playlists.show', $playlist->slug))->assertOk();
    }

    public function test_public_playlist_is_visible_to_guest_and_plays_increment(): void
    {
        $surahs = $this->surahs(1);
        [, $recA] = $this->person('a', $surahs);
        $playlist = $this->makePlaylist(User::factory()->create(), [1 => $recA], true, 'قائمة الفجر');

        $this->get(route('public.quran.playlists.show', $playlist->slug))->assertOk()->assertSee('قائمة الفجر');
        $this->post(route('public.quran.playlists.plays', $playlist->slug))->assertOk();
        $this->assertSame(1, $playlist->fresh()->plays_count);
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
}
