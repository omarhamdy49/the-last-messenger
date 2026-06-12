<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Domain\Shared\Services\FavoriteService;
use App\Domain\Shared\Services\InteractionService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\DeleteAccountRequest;
use App\Http\Requests\Site\UpdatePasswordRequest;
use App\Http\Requests\Site\UpdateProfileRequest;
use App\Http\Requests\Site\UploadAvatarRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Authenticated visitor profile dashboard (session/web guard).
 *
 * Renders the tabbed dashboard (overview stats, recent activity, bookmarks,
 * progress, settings) and handles profile + password updates. Every action
 * operates exclusively on the acting user — there is no path to another user's
 * data, so there is no IDOR surface here.
 */
final class ProfileController extends Controller
{
    public function __construct(
        private readonly InteractionService $interactions,
        private readonly FavoriteService $favorites,
    ) {}

    /**
     * Profile dashboard with real engagement stats, recent activity and a first
     * page of bookmarks. Each data source degrades to empty rather than failing
     * the whole page.
     */
    public function show(): View
    {
        $user = request()->user();
        $locale = App::getLocale();

        $engagement = $this->safe(
            fn (): array => $this->interactions->getUserEngagementHistory($user),
            [],
        );

        $activity = $this->safe(
            fn () => $this->interactions->getUserActivity($user, 15),
            collect(),
        );

        $bookmarkCounts = $this->safe(
            fn (): array => $this->favorites->countsForUser($user),
            ['all' => 0],
        );

        $recentBookmarks = $this->safe(
            fn () => $this->favorites
                ->paginateForUser($user, null, 6, $locale)
                ->getCollection(),
            collect(),
        );

        return view('public.profile.index', [
            'user' => $user,
            'engagement' => $engagement,
            'activity' => $activity,
            'bookmarkCounts' => $bookmarkCounts,
            'recentBookmarks' => $recentBookmarks,
            'favoritesUrl' => route('public.favorites'),
            'exportAvailable' => false,
        ]);
    }

    /**
     * Update the authenticated user's name, email and preferred language.
     */
    public function updateProfile(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $email = $request->string('email')->lower()->trim()->value();
        $emailChanged = $email !== $user->email;

        $user->fill([
            'name' => $request->string('name')->trim()->value(),
            'email' => $email,
            'language' => $request->validated('language'),
        ]);

        if ($emailChanged) {
            // A new address has not been verified yet — reset the flag so the
            // unverified banner (and a fresh link) apply to the new email.
            // Assigned directly: email_verified_at is deliberately not fillable.
            $user->email_verified_at = null;
        }

        $user->save();

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
        }

        return redirect()
            ->route('public.profile')
            ->with('toast', __('profile.updated'))
            ->withFragment('settings');
    }

    /**
     * Upload (or replace) the authenticated user's avatar.
     *
     * The file is stored on the `public` disk under avatars/{user_id}/ with a
     * server-generated hashed name (the client filename is never trusted), and
     * the previous file is removed so orphans never accumulate.
     */
    public function updateAvatar(UploadAvatarRequest $request): RedirectResponse
    {
        $user = $request->user();
        $previousPath = $user->avatar_path;

        $storedPath = $request->file('avatar')->store(
            'avatars/'.$user->getKey(),
            'public',
        );

        if ($storedPath === false) {
            return redirect()
                ->route('public.profile')
                ->withErrors(['avatar' => __('profile.avatar_failed')])
                ->withFragment('settings');
        }

        $user->update(['avatar_path' => $storedPath]);

        if ($previousPath !== null && $previousPath !== $storedPath) {
            Storage::disk('public')->delete($previousPath);
        }

        return redirect()
            ->route('public.profile')
            ->with('toast', __('profile.avatar_updated'));
    }

    /**
     * Permanently delete the authenticated user's account (password re-checked
     * by the FormRequest), then invalidate the session and say goodbye.
     */
    public function destroy(DeleteAccountRequest $request): RedirectResponse
    {
        $user = $request->user();
        $avatarPath = $user->avatar_path;

        Auth::guard('web')->logout();

        // No user repository/service exists yet, so the transaction lives here.
        // Related rows (favorites, user_interactions, engagement metrics) are
        // removed by ON DELETE CASCADE foreign keys.
        DB::transaction(function () use ($user): void {
            $user->tokens()->delete(); // Sanctum API tokens have no FK cascade.
            $user->delete();
        });

        // Remove stored files only after the DB delete committed.
        if ($avatarPath !== null) {
            Storage::disk('public')->delete($avatarPath);
        }
        Storage::disk('public')->deleteDirectory('avatars/'.$user->getKey());

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('public.home')
            ->with('toast', __('profile.account_deleted'));
    }

    /**
     * Change the authenticated user's password (current password verified by
     * the FormRequest rule).
     */
    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->update([
            'password' => $request->string('password')->value(),
        ]);

        return redirect()
            ->route('public.profile')
            ->with('toast', __('profile.password_updated'))
            ->withFragment('settings');
    }

    /**
     * Run a data resolver, returning a safe fallback if it throws so a single
     * failing widget never takes down the whole dashboard.
     *
     * @template T
     *
     * @param  callable(): T  $resolver
     * @param  T  $fallback
     * @return T
     */
    private function safe(callable $resolver, mixed $fallback): mixed
    {
        try {
            return $resolver();
        } catch (Throwable $e) {
            report($e);

            return $fallback;
        }
    }
}
