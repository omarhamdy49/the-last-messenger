<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Shared\ValueObjects\ApiClientIdentity;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * `api.scope:quran` — the authenticated API client must hold the scope (or `*`).
 */
final class EnsureApiClientScope
{
    public function handle(Request $request, Closure $next, string $scope): Response
    {
        $client = $request->attributes->get(AuthenticateApiClient::ATTRIBUTE);

        if (! $client instanceof ApiClientIdentity || ! $client->hasScope($scope)) {
            return response()->json([
                'success' => false,
                'message' => __('api.insufficient_scope'),
                'type' => 'insufficient_scope',
            ], 403);
        }

        return $next($request);
    }
}
