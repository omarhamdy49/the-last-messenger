<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminLoginRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Admin portal authentication.
 *
 * Separate from the public site auth (App\Http\Controllers\Site\AuthController):
 * the portal uses the dedicated `admin` session guard, so a public `web` session
 * never carries admin authentication. Credentials are verified WITHOUT logging
 * in; a session is created only for users holding an admin-tier role, so a
 * public/non-admin account can never obtain an admin session — not even briefly.
 *
 * This controller intentionally extends the base Controller (not AdminController),
 * because the login/logout endpoints must be reachable by guests.
 */
final class AdminAuthController extends Controller
{
    /** The session guard that backs the admin portal. */
    private const GUARD = 'admin';

    /**
     * Roles permitted to reach the admin portal. Kept in sync with
     * AdminAuthMiddleware, which is the route-level enforcement layer.
     *
     * @var list<string>
     */
    private const ADMIN_ROLES = ['super_admin', 'admin', 'editor'];

    public function showLogin(): View
    {
        return view('admin.auth.login');
    }

    public function login(AdminLoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();

        $user = User::query()
            ->where('email', $credentials['email'])
            ->first();

        // Verify the password against the admin guard's provider WITHOUT logging
        // in, so a non-admin (or wrong password) never gets an admin session.
        $provider = Auth::guard(self::GUARD)->getProvider();

        if ($user === null || ! $provider->validateCredentials($user, $credentials)) {
            return back()
                ->withErrors(['email' => __('auth.failed')])
                ->withInput($request->only('email'));
        }

        if (! $user->hasAnyRole(self::ADMIN_ROLES)) {
            return back()
                ->withErrors(['email' => __('admin.no_admin_access')])
                ->withInput($request->only('email'));
        }

        Auth::guard(self::GUARD)->login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        $user->updateLastLogin();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard(self::GUARD)->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
