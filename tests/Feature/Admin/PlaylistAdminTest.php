<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Quran\DTOs\PlaylistData;
use App\Domain\Quran\Models\Playlist;
use App\Domain\Quran\Models\Recitation;
use App\Domain\Quran\Models\Reciter;
use App\Domain\Quran\Models\ReciterProfile;
use App\Domain\Quran\Models\Surah;
use App\Domain\Quran\Services\PlaylistService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PlaylistAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function playlist(bool $public = true, string $name = 'قائمة للاختبار'): Playlist
    {
        return app(PlaylistService::class)->create(User::factory()->create()->id, new PlaylistData($name, null, 'full', [], $public));
    }

    public function test_guest_is_redirected(): void
    {
        $this->get(route('admin.playlists.index'))->assertRedirect();
    }

    public function test_admin_sees_index_with_owner(): void
    {
        $playlist = $this->playlist();

        $this->actingAs($this->createAdminUser())
            ->get(route('admin.playlists.index'))
            ->assertOk()
            ->assertSee('قائمة للاختبار')
            ->assertSee($playlist->owner->email);
    }

    public function test_admin_can_toggle_public_and_featured(): void
    {
        $playlist = $this->playlist();
        $admin = $this->createAdminUser();

        $this->actingAs($admin)->post(route('admin.playlists.toggle', [$playlist->id, 'public']))->assertRedirect();
        $this->assertFalse($playlist->fresh()->is_public);

        $this->actingAs($admin)->post(route('admin.playlists.toggle', [$playlist->id, 'featured']))->assertRedirect();
        $this->assertTrue($playlist->fresh()->is_featured);
    }

    public function test_admin_can_search_playlists_by_owner_email(): void
    {
        $matching = $this->playlist(name: 'Matching playlist');
        $matching->owner->update(['email' => 'playlist-owner@example.test']);
        $this->playlist(name: 'Unrelated playlist');

        $this->actingAs($this->createAdminUser())
            ->get(route('admin.playlists.index', ['search' => 'playlist-owner@example.test']))
            ->assertOk()
            ->assertSee('Matching playlist')
            ->assertDontSee('Unrelated playlist');
    }

    public function test_admin_can_rename_and_delete(): void
    {
        $playlist = $this->playlist();
        $admin = $this->createAdminUser();

        $this->actingAs($admin)
            ->put(route('admin.playlists.update', $playlist->id), ['name' => 'اسم جديد', 'description' => '', 'is_public' => 1, 'is_featured' => 0, 'items' => []])
            ->assertRedirect(route('admin.playlists.edit', $playlist->id));
        $this->assertSame('اسم جديد', $playlist->fresh()->name);

        $this->actingAs($admin)
            ->delete(route('admin.playlists.destroy', $playlist->id))
            ->assertRedirect(route('admin.playlists.index'));
        $this->assertDatabaseMissing('quran_playlists', ['id' => $playlist->id]);
    }

    public function test_bulk_unpublish(): void
    {
        $a = $this->playlist();
        $b = $this->playlist();
        app(PlaylistService::class)->setFeatured($a, true);

        $this->actingAs($this->createAdminUser())
            ->post(route('admin.playlists.bulk-unpublish'), ['ids' => [$a->id, $b->id]])
            ->assertRedirect();

        $this->assertSame(0, Playlist::query()->where('is_public', true)->count());
        $this->assertFalse($a->fresh()->is_featured);
    }

    public function test_admin_can_add_and_remove_surahs_and_rows_stay_in_surah_order(): void
    {
        foreach ([1, 2, 3] as $i) {
            Surah::query()->forceCreate(['id' => $i, 'revelation_place' => 'makkah', 'revelation_order' => $i, 'bismillah_pre' => true, 'name_ar' => 'س'.$i, 'name_en' => 'S'.$i, 'transliteration' => 'S'.$i, 'verses_count' => 3]);
        }
        $profile = ReciterProfile::query()->create(['identity_key' => 't:a', 'slug' => 'a', 'is_active' => true]);
        $profile->translations()->create(['locale' => 'ar', 'name' => 'قارئ أ']);
        $recording = Reciter::query()->create(['slug' => 'a-rec', 'profile_id' => $profile->id, 'kind' => 'recitation', 'is_active' => true]);
        $recording->translations()->create(['locale' => 'ar', 'name' => 'a', 'is_ready' => true]);
        foreach ([1, 3] as $surah) {
            Recitation::query()->create(['reciter_id' => $recording->id, 'surah_id' => $surah, 'audio_url' => "https://cdn.test/{$surah}.mp3", 'format' => 'mp3']);
        }
        $playlist = app(PlaylistService::class)->create(User::factory()->create()->id, new PlaylistData('x', null, 'custom', [1 => $recording->id, 2 => null], true));
        $admin = $this->createAdminUser();

        $this->actingAs($admin)->get(route('admin.playlists.edit', $playlist->id))->assertOk()->assertSee(__('quran.playlists_admin.add_surah'));
        $this->actingAs($admin)->getJson(route('admin.playlists.coverage', 3))->assertOk()->assertJsonPath('options.0.id', $recording->id);

        // Drop surah 2, add surah 3 — posted out of order on purpose.
        $this->actingAs($admin)
            ->put(route('admin.playlists.update', $playlist->id), ['name' => 'قائمة', 'is_public' => 1, 'items' => [3 => $recording->id, 1 => $recording->id]])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.playlists.edit', $playlist->id));

        $this->assertSame([1 => $recording->id, 3 => $recording->id], $playlist->fresh()->items->pluck('reciter_id', 'surah_id')->all());
        $this->assertSame(2, $playlist->fresh()->items_count);
    }
}
