<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\Site\LoginRequest;
use App\Http\Requests\Site\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

/**
 * Session-based (cookie) authentication for public site visitors.
 *
 * Distinct from the Sanctum token API at /api/v1/auth/* (untouched) and from
 * the admin portal login. Uses the default `web` guard. New accounts get no
 * admin roles, so they cannot reach the admin portal.
 */
final class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('public.auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->only(['email', 'password']);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => __('auth_web.failed')])
                ->withInput($request->only('email', 'remember'));
        }

        $request->session()->regenerate();
        Auth::user()->updateLastLogin();

        return redirect()
            ->intended($this->safeRedirectTarget($request) ?? route('public.home'))
            ->with('toast', __('auth_web.login_success'));
    }

    /**
     * A same-app relative redirect target from the login form, or null. Guards
     * against open-redirects: only in-app absolute paths (e.g. "/quran/ask") pass.
     */
    private function safeRedirectTarget(LoginRequest $request): ?string
    {
        $target = trim((string) $request->input('redirect', ''));

        if ($target === '' || ! str_starts_with($target, '/') || str_starts_with($target, '//')) {
            return null;
        }

        return $target;
    }

    public function showRegister(): View
    {
        return view('public.auth.register');
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        $user = User::create([
            'name' => $request->string('name')->trim()->value(),
            'email' => $request->string('email')->lower()->trim()->value(),
            'password' => $request->string('password')->value(),
            'language' => app()->getLocale(),
            'last_login_at' => now(),
        ]);

        // Public visitors get the non-privileged `user` role explicitly (never an
        // admin-tier role), consistent with the Sanctum API registration.
        // findOrCreate keeps this safe if role seeding has not run yet.
        Role::findOrCreate('user', 'web');
        $user->assignRole('user');

        // Dispatches the framework listener that emails the verification link
        // (User implements MustVerifyEmail). Browsing is NOT gated on it.
        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('public.home')
            ->with('toast', __('auth_web.register_success'));
    }

    /**
     * Email-verification notice page (link sent at registration).
     */
    public function showVerificationNotice(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('public.profile');
        }

        return view('public.auth.verify-email');
    }

    /**
     * Mark the email verified from the temporary signed link (the framework
     * request authorizes the id + email hash against the acting user).
     */
    public function verifyEmail(EmailVerificationRequest $request): RedirectResponse
    {
        if (! $request->user()->hasVerifiedEmail()) {
            $request->fulfill();
        }

        return redirect()
            ->route('public.profile')
            ->with('toast', __('auth_web.verification_done'));
    }

    /**
     * Resend the verification link (throttled at the route).
     */
    public function resendVerificationEmail(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('public.profile');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('toast', __('auth_web.verification_sent'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('public.home')
            ->with('toast', __('auth_web.logout_success'));
    }
}
