<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

final class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_forgot_password_page(): void
    {
        $this->get(route('password.request'))->assertOk();
    }

    public function test_guest_can_view_reset_password_page(): void
    {
        $this->get(route('password.reset', ['token' => 'faketoken']))->assertOk();
    }

    public function test_a_visitor_can_request_a_reset_link(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'member@example.com']);

        $response = $this->post(route('password.email'), [
            'email' => 'member@example.com',
        ]);

        $response->assertSessionHasNoErrors();
        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_link_request_requires_a_known_email(): void
    {
        $response = $this->from(route('password.request'))->post(route('password.email'), [
            'email' => 'nobody@example.com',
        ]);

        $response->assertRedirect(route('password.request'));
        $response->assertSessionHasErrors('email');
    }

    public function test_a_visitor_can_reset_their_password_with_a_valid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'member@example.com',
            'password' => 'OldPass123',
        ]);

        $token = Password::broker()->createToken($user);

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'member@example.com',
            'password' => 'NewPass123',
            'password_confirmation' => 'NewPass123',
        ]);

        $response->assertRedirect(route('public.login'));
        $response->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('NewPass123', $user->fresh()->password));
    }

    public function test_reset_fails_with_an_invalid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'member@example.com',
            'password' => 'OldPass123',
        ]);

        $response = $this->from(route('password.reset', ['token' => 'invalid']))
            ->post(route('password.update'), [
                'token' => 'invalid-token',
                'email' => 'member@example.com',
                'password' => 'NewPass123',
                'password_confirmation' => 'NewPass123',
            ]);

        $response->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('OldPass123', $user->fresh()->password));
    }

    public function test_reset_requires_a_strong_confirmed_password(): void
    {
        $user = User::factory()->create(['email' => 'member@example.com']);
        $token = Password::broker()->createToken($user);

        $response = $this->from(route('password.reset', ['token' => $token]))
            ->post(route('password.update'), [
                'token' => $token,
                'email' => 'member@example.com',
                'password' => 'weak',
                'password_confirmation' => 'nope',
            ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_reset_link_requests_are_throttled(): void
    {
        $user = User::factory()->create(['email' => 'member@example.com']);

        // The route carries throttle:5,1 — the 6th attempt within a minute is rejected.
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('password.email'), ['email' => $user->email]);
        }

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertStatus(429);
    }
}
