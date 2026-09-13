<?php

declare(strict_types=1);

namespace App\Domain\Shared\Support;

use Illuminate\Http\Request;

/**
 * Stable per-visitor fingerprint for dedupe keys: the user id when
 * authenticated, the session id for web guests, IP + user agent for
 * sessionless callers (token API). Hashed so no raw identifier lands in
 * a cache key or an event row.
 */
final class VisitorFingerprint
{
    public static function for(Request $request, ?int $userId): string
    {
        if ($userId !== null) {
            return sha1('u'.$userId);
        }

        $sessionId = $request->hasSession() ? $request->session()->getId() : null;

        return sha1($sessionId ?: $request->ip().'|'.(string) $request->userAgent());
    }
}
