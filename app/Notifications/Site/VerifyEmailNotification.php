<?php

declare(strict_types=1);

namespace App\Notifications\Site;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

/**
 * Email verification link for public-site visitors.
 *
 * Identical to the framework notification except the temporary signed URL is
 * built against the `public.verification.verify` route name — this app names
 * its public auth routes `public.*` and never registers the framework default
 * `verification.verify`.
 */
final class VerifyEmailNotification extends VerifyEmail
{
    /**
     * @param  User  $notifiable
     */
    protected function verificationUrl($notifiable): string
    {
        return URL::temporarySignedRoute(
            'public.verification.verify',
            Carbon::now()->addMinutes(config('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ],
        );
    }
}
