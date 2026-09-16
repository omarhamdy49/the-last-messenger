<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Domain\Shared\Services\LanguageService;
use App\Models\User;
use App\Notifications\Site\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Covers the profile security surface: avatar upload (B-5a), account deletion
 * (B-5b), persisted language preference (B-5c) and email verification (E-2).
 */
final class ProfileSecurityTest extends TestCase
{
    use RefreshDatabase;

    // ---------------------------------------------------------------------
    // B-5a — Avatar upload
    // ---------------------------------------------------------------------

    public function test_user_can_upload_an_avatar(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('public.profile.avatar'), [
            'avatar' => UploadedFile::fake()->image('photo.jpg', 200, 200),
        ]);

        $response->assertRedirect(route('public.profile'));
        $response->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertNotNull($user->avatar_path);
        $this->assertStringStartsWith('avatars/'.$user->id.'/', $user->avatar_path);
        // Server-generated hashed name — the client filename is never reused.
        $this->assertStringNotContainsString('photo', $user->avatar_path);
        Storage::disk('public')->assertExists($user->avatar_path);

        // The hero renders the uploaded image branch without errors.
        $this->actingAs($user)
            ->get(route('public.profile'))
            ->assertOk()
            ->assertSee($user->avatar_path);
    }

    public function test_replacing_avatar_deletes_the_previous_file(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('public.profile.avatar'), [
            'avatar' => UploadedFile::fake()->image('first.png', 200, 200),
        ]);
        $firstPath = $user->refresh()->avatar_path;

        $this->actingAs($user)->post(route('public.profile.avatar'), [
            'avatar' => UploadedFile::fake()->image('second.webp', 200, 200),
        ]);
        $secondPath = $user->refresh()->avatar_path;

        $this->assertNotSame($firstPath, $secondPath);
        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists($secondPath);
    }

    public function test_avatar_upload_rejects_non_image_files(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('public.profile'))
            ->post(route('public.profile.avatar'), [
                'avatar' => UploadedFile::fake()->create('malware.pdf', 100, 'application/pdf'),
            ]);

        $response->assertSessionHasErrors('avatar');
        $this->assertNull($user->refresh()->avatar_path);
    }

    public function test_avatar_upload_rejects_oversized_files(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('public.profile.avatar'), [
            // 3 MB > the 2 MB cap.
            'avatar' => UploadedFile::fake()->create('big.jpg', 3072, 'image/jpeg'),
        ]);

        $response->assertSessionHasErrors('avatar');
        $this->assertNull($user->refresh()->avatar_path);
    }

    public function test_avatar_upload_requires_authentication(): void
    {
        $this->post(route('public.profile.avatar'), [
            'avatar' => UploadedFile::fake()->image('photo.jpg'),
        ])->assertRedirect();

        $this->assertGuest();
    }

    // ---------------------------------------------------------------------
    // B-5b — Account deletion
    // ---------------------------------------------------------------------

    public function test_user_can_delete_their_account_with_correct_password(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['password' => 'Secret123']);

        $this->actingAs($user)->post(route('public.profile.avatar'), [
            'avatar' => UploadedFile::fake()->image('photo.jpg', 200, 200),
        ]);
        $avatarPath = $user->refresh()->avatar_path;

        $response = $this->actingAs($user)->delete(route('public.profile.destroy'), [
            'password' => 'Secret123',
        ]);

        $response->assertRedirect(route('public.home'));
        $response->assertSessionHas('toast');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        Storage::disk('public')->assertMissing($avatarPath);
    }

    public function test_account_deletion_rejects_wrong_password(): void
    {
        $user = User::factory()->create(['password' => 'Secret123']);

        $response = $this->actingAs($user)
            ->from(route('public.profile'))
            ->delete(route('public.profile.destroy'), [
                'password' => 'WrongPass1',
            ]);

        $response->assertSessionHasErrorsIn('deleteAccount', ['password']);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertAuthenticatedAs($user);
    }

    public function test_account_deletion_requires_authentication(): void
    {
        $this->delete(route('public.profile.destroy'), ['password' => 'x'])
            ->assertRedirect();

        $this->assertGuest();
    }

    // ---------------------------------------------------------------------
    // B-5c — Language preference persistence
    // ---------------------------------------------------------------------

    public function test_language_switch_persists_for_logged_in_users(): void
    {
        $user = User::factory()->create(['language' => 'en']);

        $this->actingAs($user)
            ->post(route('language.switch'), ['language' => 'tr'])
            ->assertRedirect();

        $this->assertSame('tr', $user->fresh()->language);

        // A later request honours the stored preference via SetLocale.
        $this->actingAs($user->fresh())->get(route('public.profile'))->assertOk();
        $this->assertSame('tr', app()->getLocale());
    }

    public function test_settings_language_select_persists_and_applies(): void
    {
        app(LanguageService::class)->updateSettings('fr', ['active' => true]);
        $user = User::factory()->create(['language' => 'en']);

        $this->actingAs($user)->put(route('public.profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'language' => 'fr',
        ])->assertRedirect(route('public.profile').'#settings');

        $this->assertSame('fr', $user->fresh()->language);

        $this->actingAs($user->fresh())->get(route('public.profile'))->assertOk();
        $this->assertSame('fr', app()->getLocale());
    }

    // ---------------------------------------------------------------------
    // E-2 — Email verification
    // ---------------------------------------------------------------------

    public function test_verification_email_is_sent_on_registration(): void
    {
        Notification::fake();

        $this->post(route('public.register.submit'), [
            'name' => 'New Visitor',
            'email' => 'fresh@example.com',
            'password' => 'Secret123',
            'password_confirmation' => 'Secret123',
        ])->assertRedirect(route('public.home'));

        $user = User::where('email', 'fresh@example.com')->firstOrFail();
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_signed_verification_link_marks_email_verified(): void
    {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute('public.verification.verify', Carbon::now()->addMinutes(60), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->actingAs($user)->get($url)->assertRedirect(route('public.profile'));

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_verification_link_with_invalid_signature_is_rejected(): void
    {
        $user = User::factory()->unverified()->create();

        $url = route('public.verification.verify', [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->actingAs($user)->get($url)->assertForbidden();
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_unverified_user_can_view_notice_page_and_resend(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        // The profile shows the unverified banner (browsing itself stays open).
        $this->actingAs($user)
            ->get(route('public.profile'))
            ->assertOk()
            ->assertSee(__('profile.email_unverified_banner'));

        $this->actingAs($user)
            ->get(route('public.verification.notice'))
            ->assertOk()
            ->assertViewIs('public.auth.verify-email');

        $this->actingAs($user)
            ->post(route('public.verification.send'))
            ->assertRedirect();

        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_verified_user_is_redirected_from_notice_and_resend_is_a_noop(): void
    {
        Notification::fake();
        $user = User::factory()->create(); // verified by default

        $this->actingAs($user)
            ->get(route('public.verification.notice'))
            ->assertRedirect(route('public.profile'));

        $this->actingAs($user)
            ->post(route('public.verification.send'))
            ->assertRedirect(route('public.profile'));

        Notification::assertNothingSent();
    }

    public function test_changing_email_resets_verification_and_resends_link(): void
    {
        Notification::fake();
        $user = User::factory()->create(['language' => 'en']); // verified by default

        $this->actingAs($user)->put(route('public.profile.update'), [
            'name' => $user->name,
            'email' => 'changed@example.com',
            'language' => 'en',
        ])->assertRedirect(route('public.profile').'#settings');

        $user->refresh();
        $this->assertSame('changed@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }
}
