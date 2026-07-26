<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class AuthWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_login_page(): void
    {
        $this->get(route('public.login'))->assertOk();
    }

    public function test_guest_can_view_register_page(): void
    {
        $this->get(route('public.register'))->assertOk();
    }

    public function test_a_visitor_can_register(): void
    {
        $response = $this->post(route('public.register.submit'), [
            'name' => 'Test Visitor',
            'email' => 'visitor@example.com',
            'password' => 'Secret123',
            'password_confirmation' => 'Secret123',
        ]);

        $response->assertRedirect(route('public.home'));
        $this->assertAuthenticated();

        $user = User::where('email', 'visitor@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('Secret123', $user->password));
        $this->assertTrue($user->hasRole('user'));
        $this->assertFalse($user->hasAnyRole(['super_admin', 'admin', 'editor']));
    }

    public function test_registration_requires_strong_confirmed_password(): void
    {
        $response = $this->from(route('public.register'))->post(route('public.register.submit'), [
            'name' => 'Test Visitor',
            'email' => 'weak@example.com',
            'password' => 'weak',
            'password_confirmation' => 'nope',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'weak@example.com']);
    }

    public function test_a_visitor_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'member@example.com',
            'password' => 'Secret123',
        ]);

        $response = $this->post(route('public.login.submit'), [
            'email' => 'member@example.com',
            'password' => 'Secret123',
        ]);

        $response->assertRedirect(route('public.home'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_bad_credentials(): void
    {
        User::factory()->create([
            'email' => 'member@example.com',
            'password' => 'Secret123',
        ]);

        $response = $this->from(route('public.login'))->post(route('public.login.submit'), [
            'email' => 'member@example.com',
            'password' => 'WrongPass1',
        ]);

        $response->assertRedirect(route('public.login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_validation_rejects_missing_fields(): void
    {
        $response = $this->from(route('public.login'))->post(route('public.login.submit'), [
            'email' => 'not-an-email',
        ]);

        $response->assertSessionHasErrors(['email', 'password']);
        $this->assertGuest();
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('public.logout'));

        $response->assertRedirect(route('public.home'));
        $this->assertGuest();
    }

    public function test_logout_requires_authentication(): void
    {
        // The `auth` middleware redirects unauthenticated POSTs to the named `login` route.
        $this->post(route('public.logout'))->assertRedirect();
        $this->assertGuest();
    }

    public function test_login_page_redirects_authenticated_users_away(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('public.login'))->assertRedirect();
    }

    public function test_register_page_redirects_authenticated_users_away(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('public.register'))->assertRedirect();
    }
}
