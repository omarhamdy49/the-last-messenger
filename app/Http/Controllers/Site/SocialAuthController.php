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
 *  2. Same email — link the provider to the existing local account, but ONLY
 *     when the provider has attested the address (see providerVerifiedEmail).
 *     Without attestation this is a pre-auth account-takeover vector, so the
 *     link is refused rather than silently trusting the claimed email.
 *  3. Otherwise create a new account. email_verified_at is set immediately only
 *     when the provider attested the address; otherwise the standard Registered
 *     verification-email flow runs. The NOT NULL password column gets a random
 *     hashed value that is never disclosed (see the social-login users migration
 *     for why the column was not made nullable).
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

        // resolveUser refuses to hand over an existing account to a provider that
        // has not attested the email (account-takeover guard).
        if ($user === null) {
            return $this->failToLogin(__('auth_web.social_unverified_email'));
        }

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

    /**
     * Whether the provider positively attests ownership of the returned email.
     *
     * This is the single trust gate for every provider. Only a "yes" here may
     * auto-link a social identity to a pre-existing local (password) account or
     * mark a new account's email verified. Google exposes `verified_email`;
     * Facebook's Graph API exposes no equivalent flag, so it is never trusted to
     * attest — presenting a victim's (unverified) email must not take over their
     * account. A new provider that cannot attest simply falls through to the
     * standard email-verification flow.
     */
    private function providerVerifiedEmail(string $provider, ProviderUser $providerUser): bool
    {
        return $provider === 'google'
            && method_exists($providerUser, 'getRaw')
            && data_get($providerUser->getRaw(), 'verified_email') === true;
    }

    /**
     * Returns the resolved user, or null when linking to an existing local
     * account is refused because the provider has not attested the email.
     */
    private function resolveUser(string $provider, ProviderUser $providerUser): ?User
    {
        $providerId = (string) $providerUser->getId();
        $email = mb_strtolower(trim((string) $providerUser->getEmail()));
        $emailVerifiedByProvider = $this->providerVerifiedEmail($provider, $providerUser);

        $matched = User::query()
            ->where('provider', $provider)
            ->where('provider_id', $providerId)
            ->first();

        if ($matched !== null) {
            return $matched;
        }

        $byEmail = User::query()->where('email', $email)->first();

        if ($byEmail !== null) {
            // Account-takeover guard: never attach a social identity to an
            // existing account on an email the provider has not verified.
            if (! $emailVerifiedByProvider) {
                return null;
            }

            return $this->linkProvider($byEmail, $provider, $providerId);
        }

        return $this->createUser($provider, $providerUser, $email, $emailVerifiedByProvider);
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

    private function createUser(string $provider, ProviderUser $providerUser, string $email, bool $emailVerified): User
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

        // Not mass-assignable on purpose; only mark verified when the provider
        // actually attested the address. Otherwise Registered's verification
        // listener sends the confirmation email like a normal signup.
        if ($emailVerified) {
            $user->markEmailAsVerified();
        }

        $this->storeProviderAvatar($user, $providerUser->getAvatar());

        // For an attested email the verification-email listener no-ops (already
        // verified); other Registered listeners still run.
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
