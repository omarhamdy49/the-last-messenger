<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards the admin/public boundary: public accounts share the `users` table and
 * `web` guard with admins, so these tests prove a public user can neither be
 * assigned an admin role on registration nor authenticate into the portal.
 */
class AdminAuthSeparationTest extends TestCase
{
    use RefreshDatabase;

    private const ADMIN_LOGIN = '/su/portal/v1/admin/login';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_public_registration_assigns_only_the_user_role(): void
    {
        $this->post(route('public.register.submit'), [
            'name' => 'Visitor',
            'email' => 'visitor@example.com',
            'password' => 'Secret123',
            'password_confirmation' => 'Secret123',
        ]);

        $user = User::where('email', 'visitor@example.com')->firstOrFail();

        $this->assertTrue($user->hasRole('user'));
        $this->assertFalse($user->hasAnyRole(['admin', 'super_admin', 'editor']));
    }

    public function test_public_user_is_redirected_from_admin_dashboard(): void
    {
        $user = $this->createUser(); // role-less public account

        $this->actingAs($user)
            ->get('/su/portal/v1/admin/dashboard')
            ->assertRedirect(route('login'));
    }

    public function test_public_user_with_user_role_cannot_reach_admin_dashboard(): void
    {
        $user = $this->createAdminUser([], 'user'); // seeds roles, assigns non-admin 'user'

        $this->actingAs($user)
            ->get('/su/portal/v1/admin/dashboard')
            ->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_authenticate_via_admin_login(): void
    {
        $this->seedRolesAndPermissions();
        $user = $this->createUser(['email' => 'public@example.com']);
        $user->assignRole('user');

        $response = $this->post(self::ADMIN_LOGIN, [
            'email' => 'public@example.com',
            'password' => 'password',
        ]);

        // No session is established for a non-admin, even with valid credentials.
        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_wrong_password_does_not_authenticate(): void
    {
        $this->createAdminUser(['email' => 'admin@example.com']);

        $response = $this->post(self::ADMIN_LOGIN, [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_admin_can_authenticate_via_admin_login(): void
    {
        $admin = $this->createAdminUser(['email' => 'admin@example.com']);

        $response = $this->post(self::ADMIN_LOGIN, [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_editor_can_authenticate_via_admin_login(): void
    {
        $editor = $this->createAdminUser(['email' => 'editor@example.com'], 'editor');

        $response = $this->post(self::ADMIN_LOGIN, [
            'email' => 'editor@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($editor);
    }

    public function test_admin_login_requires_email_and_password(): void
    {
        $response = $this->post(self::ADMIN_LOGIN, []);

        $response->assertSessionHasErrors(['email', 'password']);
        $this->assertGuest();
    }
}
