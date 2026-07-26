<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as ProviderUser;
use Laravel\Socialite\Facades\Socialite;

/**
 * Social login (Google / Facebook) for public site visitors, via Socialite.
 *
 * Session-based like AuthController (`web` guard). The provider is whitelisted
 * at the route level (`whereIn('provider', ...)`) so unknown providers 404.
 *
 * Account resolution order on callback:
 *  1. (provider, provider_id) match — returning social user, just sign in.
 *  2. Same email — link the provider to the existing local account. The
 *     provider attests the address, so an unverified email becomes verified.
 *  3. Otherwise create a new account. The provider verified the email, so
 *     email_verified_at is set immediately. The NOT NULL password column gets
 *     a random hashed value that is never disclosed (see the social-login
 *     users migration for why the column was not made nullable).
 */
final class SocialAuthController extends Controller
{
    public function __construct(private readonly UserRepository $users) {}

    public function redirect(Request $request, string $provider): RedirectResponse
    {
        if (! $this->isConfigured($provider)) {
            return $this->failToLogin(__('auth_web.social_unavailable'));
        }

        // Remember where to return after a successful social login (e.g. the page
        // that opened the login modal). Same-app relative paths only.
        $target = trim((string) $request->query('redirect', ''));
        if ($target !== '' && str_starts_with($target, '/') && ! str_starts_with($target, '//')) {
            $request->session()->put('url.intended', url($target));
        }

        return Socialite::driver($provider)->redirect();
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        if (! $this->isConfigured($provider)) {
            return $this->failToLogin(__('auth_web.social_unavailable'));
        }

        // The user cancelled the consent screen or the provider returned an
        // OAuth error (?error=access_denied...) — bail out before Socialite.
        if ($request->filled('error')) {
            return $this->failToLogin(__('auth_web.social_cancelled'));
        }

        try {
            $providerUser = Socialite::driver($provider)->user();
        } catch (\Throwable $exception) {
            Log::warning('Social login callback failed', [
                'provider' => $provider,
                'reason' => $exception->getMessage(),
            ]);

            return $this->failToLogin(__('auth_web.social_failed'));
        }

        // An email is mandatory to anchor the local account (Facebook can
        // omit it for phone-only accounts).
        if (! filled($providerUser->getEmail()) || ! filled($providerUser->getId())) {
            return $this->failToLogin(__('auth_web.social_no_email'));
        }

        $user = $this->resolveUser($provider, $providerUser);

        Auth::login($user, remember: true);
        $request->session()->regenerate();
        $user->updateLastLogin();

        return redirect()
            ->intended(route('public.home'))
            ->with('toast', __('auth_web.login_success'));
    }

    /**
     * A provider with no client id is not configured — its buttons are hidden
     * on the auth pages, and direct URL hits land back on the login page.
     */
    private function isConfigured(string $provider): bool
    {
        return filled(config("services.{$provider}.client_id"));
    }

    private function failToLogin(string $message): RedirectResponse
    {
        return redirect()
            ->route('public.login')
            ->withErrors(['social' => $message]);
    }

    private function resolveUser(string $provider, ProviderUser $providerUser): User
    {
        $providerId = (string) $providerUser->getId();
        $email = mb_strtolower(trim((string) $providerUser->getEmail()));

        $matched = User::query()
            ->where('provider', $provider)
            ->where('provider_id', $providerId)
            ->first();

        if ($matched !== null) {
            return $matched;
        }

        $byEmail = User::query()->where('email', $email)->first();

        if ($byEmail !== null) {
            return $this->linkProvider($byEmail, $provider, $providerId);
        }

        return $this->createUser($provider, $providerUser, $email);
    }

    /**
     * Attach the provider identity to an existing local account (same email).
     * The provider attests ownership of the address, so it counts as verified.
     */
    private function linkProvider(User $user, string $provider, string $providerId): User
    {
        $user->update(['provider' => $provider, 'provider_id' => $providerId]);

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return $user;
    }

    private function createUser(string $provider, ProviderUser $providerUser, string $email): User
    {
        $name = trim((string) ($providerUser->getName()
            ?: $providerUser->getNickname()
            ?: Str::before($email, '@')));

        $user = $this->users->createPublicAccount([
            'name' => Str::limit($name, 250, ''),
            'email' => $email,
            // NOT NULL column; never disclosed — provider is the only way in.
            'password' => Str::random(40),
            'language' => app()->getLocale(),
            'provider' => $provider,
            'provider_id' => (string) $providerUser->getId(),
            'last_login_at' => now(),
        ]);

        // Not mass-assignable on purpose; the provider verified the address.
        $user->markEmailAsVerified();

        $this->storeProviderAvatar($user, $providerUser->getAvatar());

        // The verification-email listener no-ops because the email is already
        // verified; other Registered listeners still run.
        event(new Registered($user));

        return $user;
    }

    /**
     * Best-effort copy of the provider avatar onto the public disk (the same
     * avatars/{id}/ location ProfileController uses). Login never fails on it.
     */
    private function storeProviderAvatar(User $user, ?string $avatarUrl): void
    {
        if (! filled($avatarUrl) || ! Str::startsWith($avatarUrl, 'https://')) {
            return;
        }

        try {
            $response = Http::timeout(5)->get($avatarUrl);

            if (! $response->successful() || $response->body() === '') {
                return;
            }

            $path = 'avatars/'.$user->getKey().'/'.Str::random(20).'.jpg';

            if (Storage::disk('public')->put($path, $response->body())) {
                $user->update(['avatar_path' => $path]);
            }
        } catch (\Throwable $exception) {
            Log::info('Provider avatar fetch skipped', [
                'user_id' => $user->getKey(),
                'reason' => $exception->getMessage(),
            ]);
        }
    }
}
