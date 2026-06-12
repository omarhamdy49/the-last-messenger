<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

final class SocialAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Providers are "configured" for these tests; the unconfigured
        // behavior is asserted explicitly where needed.
        config([
            'services.google.client_id' => 'test-google-client-id',
            'services.google.client_secret' => 'test-google-secret',
            'services.facebook.client_id' => 'test-facebook-client-id',
            'services.facebook.client_secret' => 'test-facebook-secret',
        ]);
    }

    private function mockProviderUser(
        string $provider = 'google',
        string $id = 'provider-id-123',
        string $email = 'social@example.com',
        ?string $name = 'Social Visitor',
    ): void {
        $socialUser = (new SocialiteUser)->map([
            'id' => $id,
            'name' => $name,
            'nickname' => null,
            'email' => $email,
            'avatar' => null,
        ]);

        $driver = Mockery::mock(Provider::class);
        $driver->shouldReceive('user')->andReturn($socialUser);

        Socialite::shouldReceive('driver')->with($provider)->andReturn($driver);
    }

    public function test_redirect_route_sends_guest_to_the_provider(): void
    {
        $driver = Mockery::mock(Provider::class);
        $driver->shouldReceive('redirect')
            ->andReturn(redirect()->away('https://accounts.google.com/o/oauth2/auth'));

        Socialite::shouldReceive('driver')->with('google')->andReturn($driver);

        $this->get(route('public.social.redirect', 'google'))
            ->assertRedirect('https://accounts.google.com/o/oauth2/auth');
    }

    public function test_callback_creates_a_verified_user_and_logs_in(): void
    {
        $this->mockProviderUser(id: 'g-1001', email: 'NewSocial@Example.com');

        $response = $this->get(route('public.social.callback', 'google'));

        $response->assertRedirect(route('public.home'));
        $this->assertAuthenticated();

        $user = User::where('email', 'newsocial@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('google', $user->provider);
        $this->assertSame('g-1001', $user->provider_id);
        $this->assertSame('Social Visitor', $user->name);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNotEmpty($user->password);
    }

    public function test_callback_links_provider_to_existing_user_by_email(): void
    {
        $existing = User::factory()->create([
            'email' => 'member@example.com',
            'email_verified_at' => null,
        ]);

        $this->mockProviderUser(id: 'g-2002', email: 'member@example.com');

        $this->get(route('public.social.callback', 'google'))
            ->assertRedirect(route('public.home'));

        $this->assertAuthenticatedAs($existing);
        $this->assertSame(1, User::count());

        $existing->refresh();
        $this->assertSame('google', $existing->provider);
        $this->assertSame('g-2002', $existing->provider_id);
        // The provider attests the address, so linking verifies it.
        $this->assertNotNull($existing->email_verified_at);
    }

    public function test_second_login_matches_by_provider_id_before_email(): void
    {
        $existing = User::factory()->create([
            'email' => 'original@example.com',
            'provider' => 'google',
            'provider_id' => 'g-3003',
        ]);

        // Same provider id, different (changed) provider email.
        $this->mockProviderUser(id: 'g-3003', email: 'renamed@example.com');

        $this->get(route('public.social.callback', 'google'))
            ->assertRedirect(route('public.home'));

        $this->assertAuthenticatedAs($existing);
        $this->assertSame(1, User::count());
        $this->assertDatabaseMissing('users', ['email' => 'renamed@example.com']);
    }

    public function test_invalid_provider_returns_404(): void
    {
        $this->get('/auth/twitter/redirect')->assertNotFound();
        $this->get('/auth/twitter/callback')->assertNotFound();
    }

    public function test_callback_error_param_redirects_to_login_with_message(): void
    {
        $response = $this->get(route('public.social.callback', 'google').'?error=access_denied');

        $response->assertRedirect(route('public.login'));
        $response->assertSessionHasErrors('social');
        $this->assertGuest();
        $this->assertSame(0, User::count());
    }

    public function test_callback_failure_inside_socialite_never_500s(): void
    {
        $driver = Mockery::mock(Provider::class);
        $driver->shouldReceive('user')->andThrow(new \RuntimeException('state mismatch'));

        Socialite::shouldReceive('driver')->with('google')->andReturn($driver);

        $response = $this->get(route('public.social.callback', 'google'));

        $response->assertRedirect(route('public.login'));
        $response->assertSessionHasErrors('social');
        $this->assertGuest();
    }

    public function test_callback_without_provider_email_is_rejected(): void
    {
        $this->mockProviderUser(id: 'fb-1', email: '');

        $response = $this->get(route('public.social.callback', 'google'));

        $response->assertRedirect(route('public.login'));
        $response->assertSessionHasErrors('social');
        $this->assertGuest();
        $this->assertSame(0, User::count());
    }

    public function test_unconfigured_provider_redirects_to_login(): void
    {
        config(['services.facebook.client_id' => null]);

        $response = $this->get(route('public.social.redirect', 'facebook'));

        $response->assertRedirect(route('public.login'));
        $response->assertSessionHasErrors('social');
    }

    public function test_authenticated_users_are_redirected_away(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('public.social.redirect', 'google'))
            ->assertRedirect();
    }

    public function test_login_page_shows_social_buttons_only_when_configured(): void
    {
        $this->get(route('public.login'))
            ->assertOk()
            ->assertSee(route('public.social.redirect', 'google'), false)
            ->assertSee(route('public.social.redirect', 'facebook'), false);

        config([
            'services.google.client_id' => null,
            'services.facebook.client_id' => null,
        ]);

        $this->get(route('public.login'))
            ->assertOk()
            ->assertDontSee('/auth/google/redirect')
            ->assertDontSee('/auth/facebook/redirect');
    }

    public function test_register_page_shows_social_buttons_only_when_configured(): void
    {
        $this->get(route('public.register'))
            ->assertOk()
            ->assertSee(route('public.social.redirect', 'google'), false);

        config([
            'services.google.client_id' => null,
            'services.facebook.client_id' => null,
        ]);

        $this->get(route('public.register'))
            ->assertOk()
            ->assertDontSee('/auth/google/redirect');
    }
}
