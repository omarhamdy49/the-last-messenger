<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Domain\Quran\Models\Chapter;
use App\Domain\Quran\Models\QuranResource;
use App\Domain\Quran\Models\Translation;
use App\Domain\Quran\Models\Verse;
use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Shared\Enums\FavorableType;
use App\Domain\Shared\Services\FavoriteService;
use App\Models\Favorite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Covers the authenticated visitor profile dashboard and the bookmarks
 * (favorites) web endpoints, including the IDOR boundary.
 */
final class ProfileFavoritesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Insert a minimal, valid bookmarkable Seerah event and return its id.
     * Written via the query builder so the test does not depend on another
     * module's translation mechanics.
     */
    private function makeSeerahEvent(string $slug): int
    {
        // Translatable text lives in seerah_event_translations; the base row
        // only needs slug + type.
        DB::table('seerah_event_types')->insertOrIgnore([
            'slug' => 'other',
            'locale' => json_encode(['ar' => 'أخرى', 'en' => 'Other']),
            'is_active' => true,
        ]);

        return (int) DB::table('seerah_events')->insertGetId([
            'slug' => $slug,
            'event_type' => DB::table('seerah_event_types')->where('slug', 'other')->value('id'),
            'status' => 'published',
            'order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // ---------------------------------------------------------------------
    // Profile dashboard
    // ---------------------------------------------------------------------

    public function test_profile_requires_authentication(): void
    {
        // Guests are redirected to a login page (the app routes unauthenticated
        // web requests to its login route).
        $this->get(route('public.profile'))->assertRedirect();
        $this->assertGuest();
    }

    public function test_authenticated_user_can_view_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('public.profile'))
            ->assertOk()
            ->assertViewIs('public.profile.index');
    }

    public function test_user_can_update_their_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
            'language' => 'en',
        ]);

        $response = $this->actingAs($user)->put(route('public.profile.update'), [
            'name' => 'New Name',
            'email' => 'new@example.com',
            'language' => 'ar',
        ]);

        $response->assertRedirect(route('public.profile').'#settings');
        $response->assertSessionHas('toast');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
            'email' => 'new@example.com',
            'language' => 'ar',
        ]);
    }

    public function test_profile_update_validates_unique_email_except_self(): void
    {
        $other = User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->create(['email' => 'mine@example.com']);

        // Re-saving own email must pass.
        $this->actingAs($user)
            ->from(route('public.profile'))
            ->put(route('public.profile.update'), [
                'name' => 'Mine',
                'email' => 'mine@example.com',
                'language' => 'ar',
            ])
            ->assertSessionHasNoErrors();

        // Taking another user's email must fail.
        $this->actingAs($user)
            ->from(route('public.profile'))
            ->put(route('public.profile.update'), [
                'name' => 'Mine',
                'email' => 'taken@example.com',
                'language' => 'ar',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_user_can_change_password_with_correct_current_password(): void
    {
        $user = User::factory()->create(['password' => 'OldSecret1']);

        $response = $this->actingAs($user)->put(route('public.profile.password'), [
            'current_password' => 'OldSecret1',
            'password' => 'NewSecret1',
            'password_confirmation' => 'NewSecret1',
        ]);

        $response->assertRedirect(route('public.profile').'#settings');
        $response->assertSessionHas('toast');

        $this->assertTrue(
            Hash::check('NewSecret1', $user->fresh()->password),
        );
    }

    public function test_password_change_rejects_wrong_current_password(): void
    {
        $user = User::factory()->create(['password' => 'OldSecret1']);

        $this->actingAs($user)
            ->from(route('public.profile'))
            ->put(route('public.profile.password'), [
                'current_password' => 'WrongSecret1',
                'password' => 'NewSecret1',
                'password_confirmation' => 'NewSecret1',
            ])
            ->assertSessionHasErrors('current_password');
    }

    // ---------------------------------------------------------------------
    // Favorites
    // ---------------------------------------------------------------------

    public function test_favorites_index_requires_authentication(): void
    {
        $this->get(route('public.favorites'))->assertRedirect();
        $this->assertGuest();
    }

    public function test_favorite_store_requires_authentication(): void
    {
        $eventId = $this->makeSeerahEvent('guest-event');

        $this->postJson(route('public.favorites.store'), [
            'type' => 'seerah',
            'id' => $eventId,
        ])->assertUnauthorized();
    }

    public function test_user_can_add_list_and_remove_a_favorite(): void
    {
        $user = User::factory()->create();
        $eventId = $this->makeSeerahEvent('my-event');

        // Add
        $this->actingAs($user)
            ->postJson(route('public.favorites.store'), ['type' => 'seerah', 'id' => $eventId])
            ->assertCreated()
            ->assertJson(['success' => true, 'favorited' => true]);

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'favorable_type' => SeerahEvent::class,
            'favorable_id' => $eventId,
        ]);

        // Adding again is idempotent (200, not a duplicate row).
        $this->actingAs($user)
            ->postJson(route('public.favorites.store'), ['type' => 'seerah', 'id' => $eventId])
            ->assertOk();

        $this->assertSame(1, Favorite::where('user_id', $user->id)->count());

        // List
        $this->actingAs($user)
            ->get(route('public.favorites'))
            ->assertOk()
            ->assertViewIs('public.favorites.index');

        // Remove
        $this->actingAs($user)
            ->deleteJson(route('public.favorites.destroy'), ['type' => 'seerah', 'id' => $eventId])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'favorable_id' => $eventId,
        ]);
    }

    public function test_a_favorite_whose_seerah_event_was_deleted_does_not_break_the_page(): void
    {
        $user = User::factory()->create();
        $eventId = $this->makeSeerahEvent('deleted-event');

        $this->actingAs($user)
            ->postJson(route('public.favorites.store'), ['type' => 'seerah', 'id' => $eventId])
            ->assertCreated();

        DB::table('seerah_events')->where('id', $eventId)->delete();

        $this->actingAs($user)->get(route('public.favorites'))->assertOk();
        $this->actingAs($user)->get(route('public.profile'))->assertOk();
    }

    public function test_store_validates_content_type(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('public.favorites.store'), ['type' => 'not-a-type', 'id' => 1])
            ->assertStatus(422)
            ->assertJsonValidationErrors('type');
    }

    public function test_store_returns_404_for_missing_content(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('public.favorites.store'), ['type' => 'seerah', 'id' => 999999])
            ->assertNotFound();
    }

    /**
     * IDOR: user B must never see or remove user A's favorite.
     */
    public function test_user_cannot_access_another_users_favorite(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $eventId = $this->makeSeerahEvent('a-event');

        // A bookmarks the event.
        $this->actingAs($userA)
            ->postJson(route('public.favorites.store'), ['type' => 'seerah', 'id' => $eventId])
            ->assertCreated();

        $favoriteId = Favorite::where('user_id', $userA->id)->value('id');
        $this->assertNotNull($favoriteId);

        // B's favorites list must be empty (cannot see A's bookmark).
        $this->actingAs($userB)
            ->get(route('public.favorites'))
            ->assertOk()
            ->assertViewHas('bookmarks', fn ($bookmarks) => $bookmarks->isEmpty());

        // B attempting to delete the same content removes nothing — A's row
        // survives (scoped by user_id).
        $this->actingAs($userB)
            ->deleteJson(route('public.favorites.destroy'), ['type' => 'seerah', 'id' => $eventId])
            ->assertOk();

        $this->assertDatabaseHas('favorites', [
            'id' => $favoriteId,
            'user_id' => $userA->id,
        ]);
    }

    // ---------------------------------------------------------------------
    // Reciter profiles (Quran) are bookmarkable
    // ---------------------------------------------------------------------

    public function test_user_can_favorite_a_reciter(): void
    {
        $reciter = QuranResource::factory()->chapterReciter()->create(['slug' => 'husary', 'name' => 'Al-Husary']);
        $reciter->syncTranslations(['name' => ['ar' => 'الحصري']]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('public.favorites.store'), ['type' => 'reciter', 'id' => $reciter->id])
            ->assertCreated();

        $this->assertDatabaseHas('favorites', ['user_id' => $user->id, 'favorable_id' => $reciter->id]);
        $this->actingAs($user)->get(route('public.favorites'))->assertOk()->assertSee('الحصري');
        $this->assertContains($reciter->id, app(FavoriteService::class)->favoritedContentIds($user, FavorableType::RECITER));
    }

    public function test_user_can_favorite_a_verse(): void
    {
        Chapter::factory()->number(1)->withVerses(2)->create();
        $verse = Verse::query()->where('verse_key', '1:2')->firstOrFail();
        $resource = QuranResource::factory()->translation('ar')->asDefault()->create();
        Translation::factory()->create(['resource_id' => $resource->id, 'verse_id' => $verse->id, 'text' => 'الحمد لله رب العالمين']);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('public.favorites.store'), ['type' => 'quran', 'id' => $verse->id])
            ->assertCreated();

        $this->actingAs($user)->get(route('public.favorites'))
            ->assertOk()
            ->assertSee('الحمد لله رب العالمين')
            ->assertSee(route('public.quran.surah', 1).'#verse-1:2', false);
    }
}
