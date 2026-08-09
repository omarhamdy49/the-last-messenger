<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AddRequestId
{
    /**
     * Attach a unique X-Request-Id to every response for distributed tracing.
     * If the client sends the header it is echoed back; otherwise a new UUID is generated.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $candidate = $request->header('X-Request-Id');
        $requestId = is_string($candidate)
            && preg_match('/\A[A-Za-z0-9][A-Za-z0-9._:-]{7,99}\z/', $candidate) === 1
                ? $candidate
                : (string) Str::uuid();
        $request->headers->set('X-Request-Id', $requestId);

        $response = $next($request);
        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }
}
