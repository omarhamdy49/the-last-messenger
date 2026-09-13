<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Quran\DTOs\PlaylistData;
use App\Domain\Quran\Models\Playlist;
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
        return app(PlaylistService::class)->create(User::factory()->create(), new PlaylistData($name, null, 'full', [], $public));
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
}
