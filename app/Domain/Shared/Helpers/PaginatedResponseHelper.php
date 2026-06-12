<?php

declare(strict_types=1);

namespace App\Domain\Shared\Helpers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

/**
 * PaginatedResponseHelper
 *
 * Builds the standard project pagination envelope as a static utility,
 * matching the format produced by App\Support\Traits\ApiResponse::paginatedResponse().
 *
 * Intended for use in contexts that cannot mix in the ApiResponse trait —
 * e.g. service classes, command/query handlers, or test assertions.
 * Controllers should continue using $this->paginatedResponse() from the trait.
 *
 * Envelope shape:
 * {
 *   "success": true,
 *   "data":    <items from the paginator>,
 *   "meta":    { current_page, last_page, per_page, total, from, to },
 *   "links":   { first, last, prev, next },
 *   "message": "<message>"   // omitted when empty string
 * }
 */
final class PaginatedResponseHelper
{
    /**
     * Build the pagination envelope array.
     *
     * Returns the raw array so callers can merge extra keys before turning it
     * into a JsonResponse, or inspect it directly in tests.
     *
     * @param  LengthAwarePaginator<mixed>  $paginator
     * @param  string  $message  Included in the envelope only when non-empty.
     * @param  array<string, mixed>  $extra  Optional keys merged into the top-level array.
     * @return array<string, mixed>
     */
    public static function build(
        LengthAwarePaginator $paginator,
        string $message = 'Success',
        array $extra = [],
    ): array {
        $payload = [
            'success' => true,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ];

        if ($message !== '') {
            $payload['message'] = $message;
        }

        if ($extra !== []) {
            $payload = array_merge($payload, $extra);
        }

        return $payload;
    }

    /**
     * Build the pagination envelope and wrap it in a JsonResponse.
     *
     * Mirrors ApiResponse::paginatedResponse() exactly.
     * Use this when a non-controller class needs to return a JsonResponse directly.
     *
     * @param  LengthAwarePaginator<mixed>  $paginator
     * @param  array<string, mixed>  $extra
     * @param  int  $status  HTTP status code (default 200).
     */
    public static function response(
        LengthAwarePaginator $paginator,
        string $message = 'Success',
        array $extra = [],
        int $status = 200,
    ): JsonResponse {
        return response()->json(
            self::build($paginator, $message, $extra),
            $status,
        );
    }
}
