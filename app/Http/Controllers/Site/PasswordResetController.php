<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\Site\ForgotPasswordRequest;
use App\Http\Requests\Site\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * Public-site forgot-password / password-reset flow (session/web guard).
 *
 * Uses Laravel's `users` password broker (config/auth.php). Reset links are
 * delivered via the default ResetPassword notification; with MAIL_MAILER=log
 * they land in storage/logs/laravel.log. Distinct from the Sanctum token API
 * and the admin portal, both of which are untouched.
 */
final class PasswordResetController extends Controller
{
    public function showLinkRequest(): View
    {
        return view('public.auth.forgot-password');
    }

    public function sendResetLink(ForgotPasswordRequest $request): RedirectResponse
    {
        $status = Password::sendResetLink(
            ['email' => $request->string('email')->lower()->trim()->value()]
        );

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('toast', __($status));
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => __($status)]);
    }

    public function showReset(Request $request, string $token): View
    {
        return view('public.auth.reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request): RedirectResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()
                ->route('public.login')
                ->with('toast', __($status));
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => __($status)]);
    }
}
