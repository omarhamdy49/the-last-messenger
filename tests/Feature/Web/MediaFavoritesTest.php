<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Domain\Media\Models\MediaProgram;
use App\Models\Favorite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Covers server-side My List (favorites) for media programs: the `media`
 * favorable type added for the public media hub/episode save buttons.
 */
final class MediaFavoritesTest extends TestCase
{
    use RefreshDatabase;

    private const PROGRAM_TITLE_AR = 'برنامج السيرة التجريبي';

    /**
     * Insert a minimal, valid media program (+ Arabic translation) and return
     * its id. Written via the query builder so the test stays independent of
     * factory randomness.
     */
    private function makeMediaProgram(string $slug): int
    {
        $programId = (int) DB::table('media_programs')->insertGetId([
            'slug' => $slug,
            'category' => 'seerah',
            'maturity_rating' => 'all',
            'year' => 2024,
            'rating' => 8.5,
            'is_featured' => false,
            'is_trending' => false,
            'views_count' => 0,
            'display_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('media_program_translations')->insert([
            'media_program_id' => $programId,
            'locale' => 'ar',
            'title' => self::PROGRAM_TITLE_AR,
            'description' => 'وصف البرنامج التجريبي للاختبار',
            'host' => null,
            'is_ready' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $programId;
    }

    public function test_guest_store_via_json_is_unauthorized(): void
    {
        $programId = $this->makeMediaProgram('guest-program');

        $this->postJson(route('public.favorites.store'), [
            'type' => 'media',
            'id' => $programId,
        ])->assertUnauthorized();
    }

    public function test_guest_store_via_form_redirects_to_login(): void
    {
        $programId = $this->makeMediaProgram('guest-form-program');

        $this->post(route('public.favorites.store'), [
            'type' => 'media',
            'id' => $programId,
        ])->assertRedirect();

        $this->assertGuest();
        $this->assertSame(0, Favorite::query()->count());
    }

    public function test_user_can_add_list_and_remove_a_media_favorite(): void
    {
        $user = User::factory()->create();
        $programId = $this->makeMediaProgram('my-program');

        // Add
        $this->actingAs($user)
            ->postJson(route('public.favorites.store'), ['type' => 'media', 'id' => $programId])
            ->assertCreated()
            ->assertJson(['success' => true, 'favorited' => true]);

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'favorable_type' => MediaProgram::class,
            'favorable_id' => $programId,
        ]);

        // Idempotent re-add (200, no duplicate row).
        $this->actingAs($user)
            ->postJson(route('public.favorites.store'), ['type' => 'media', 'id' => $programId])
            ->assertOk();

        $this->assertSame(1, Favorite::where('user_id', $user->id)->count());

        // Remove
        $this->actingAs($user)
            ->deleteJson(route('public.favorites.destroy'), ['type' => 'media', 'id' => $programId])
            ->assertOk()
            ->assertJson(['success' => true, 'favorited' => false]);

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'favorable_id' => $programId,
        ]);
    }

    public function test_store_still_rejects_invalid_type_with_422(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('public.favorites.store'), ['type' => 'not-a-type', 'id' => 1])
            ->assertStatus(422)
            ->assertJsonValidationErrors('type');
    }

    public function test_store_returns_404_for_missing_media_program(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('public.favorites.store'), ['type' => 'media', 'id' => 999999])
            ->assertNotFound();
    }

    public function test_profile_favorites_page_renders_a_media_favorite(): void
    {
        $user = User::factory()->create();
        $programId = $this->makeMediaProgram('rendered-program');

        $this->actingAs($user)
            ->postJson(route('public.favorites.store'), ['type' => 'media', 'id' => $programId])
            ->assertCreated();

        $this->actingAs($user)
            ->get(route('public.favorites'))
            ->assertOk()
            ->assertViewIs('public.favorites.index')
            ->assertSee(self::PROGRAM_TITLE_AR)
            ->assertViewHas('counts', fn (array $counts): bool => ($counts['media'] ?? 0) === 1);
    }

    public function test_profile_favorites_page_filters_by_media_type(): void
    {
        $user = User::factory()->create();
        $programId = $this->makeMediaProgram('filtered-program');

        $this->actingAs($user)
            ->postJson(route('public.favorites.store'), ['type' => 'media', 'id' => $programId])
            ->assertCreated();

        $this->actingAs($user)
            ->get(route('public.favorites', ['type' => 'media']))
            ->assertOk()
            ->assertViewHas('activeType', 'media')
            ->assertViewHas('bookmarks', fn ($bookmarks) => $bookmarks->count() === 1);
    }
}
