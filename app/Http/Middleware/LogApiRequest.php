<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogApiRequest
{
    /**
     * Log structured metadata about every API request after the response is built.
     * Never logs request body (may contain passwords) or Authorization header values.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        $duration = round((microtime(true) - $startTime) * 1000, 2);
        $status = $response->getStatusCode();

        $level = match (true) {
            $status >= 500 => 'error',
            $status >= 400 => 'warning',
            default => 'info',
        };

        Log::channel('api')->{$level}('api.request', [
            'method' => $request->method(),
            'path' => $request->path(),
            'status' => $status,
            'duration_ms' => $duration,
            'user_id' => $request->user()?->id,
            'ip' => $request->ip(),
            'request_id' => $request->header('X-Request-Id'),
        ]);

        return $response;
    }
}
