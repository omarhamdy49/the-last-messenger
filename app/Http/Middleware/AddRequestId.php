<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AddRequestId
{
    /**
     * Attach a unique X-Request-Id to every response for distributed tracing, and push it
     * into the log context so every subsequent line — including framework exception
     * traces — carries it.
     *
     * The log-context part is the point. Setting the header alone meant the id appeared
     * only in LogApiRequest's one deliberate line, and nowhere in the production.ERROR
     * entries you actually need it in when correlating a user report to a request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $candidate = $request->header('X-Request-Id');
        $requestId = is_string($candidate)
            && preg_match('/\A[A-Za-z0-9][A-Za-z0-9._:-]{7,99}\z/', $candidate) === 1
                ? $candidate
                : (string) Str::uuid();
        $request->headers->set('X-Request-Id', $requestId);

        Log::withContext(['request_id' => $requestId]);

        $response = $next($request);
        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }
}
