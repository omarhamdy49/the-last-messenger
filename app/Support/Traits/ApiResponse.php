<?php

declare(strict_types=1);

namespace App\Support\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * API Response Trait
 * Provides consistent API response formatting per the project envelope standard.
 *
 * Success envelope:
 *   { "success": true, "data": ..., "message": "...", "meta": {...} }
 *
 * Error envelope:
 *   { "success": false, "error": { "code": "...", "message": "...", "details": {...} } }
 */
trait ApiResponse
{
    /**
     * Success response
     */
    protected function successResponse(mixed $data = null, string $message = 'Success', int $code = Response::HTTP_OK): JsonResponse
    {
        $payload = [
            'success' => true,
            'data'    => $data,
        ];

        if ($message !== '') {
            $payload['message'] = $message;
        }

        return response()->json($payload, $code);
    }

    /**
     * Error response
     *
     * @param  array<string, mixed>  $details  Field-level or contextual details (optional)
     */
    protected function errorResponse(
        string $message = 'Error',
        int $code = Response::HTTP_BAD_REQUEST,
        string $errorCode = '',
        array $details = [],
    ): JsonResponse {
        $error = ['message' => $message];

        if ($errorCode !== '') {
            $error['code'] = $errorCode;
        }

        if ($details !== []) {
            $error['details'] = $details;
        }

        return response()->json(['success' => false, 'error' => $error], $code);
    }

    /**
     * Paginated response
     */
    protected function paginatedResponse(LengthAwarePaginator $paginator, string $message = 'Success'): JsonResponse
    {
        $payload = [
            'success' => true,
            'data'    => $paginator->items(),
            'meta'    => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
            ],
            'links'   => [
                'first' => $paginator->url(1),
                'last'  => $paginator->url($paginator->lastPage()),
                'prev'  => $paginator->previousPageUrl(),
                'next'  => $paginator->nextPageUrl(),
            ],
        ];

        if ($message !== '') {
            $payload['message'] = $message;
        }

        return response()->json($payload);
    }

    /**
     * Created response (HTTP 201)
     */
    protected function createdResponse(mixed $data = null, string $message = 'Resource created successfully'): JsonResponse
    {
        return $this->successResponse($data, $message, Response::HTTP_CREATED);
    }

    /**
     * No content response (HTTP 204)
     */
    protected function noContentResponse(): JsonResponse
    {
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Not found response (HTTP 404)
     */
    protected function notFoundResponse(string $message = 'Resource not found'): JsonResponse
    {
        return $this->errorResponse($message, Response::HTTP_NOT_FOUND, 'NOT_FOUND');
    }

    /**
     * Unauthorized response (HTTP 401)
     */
    protected function unauthorizedResponse(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->errorResponse($message, Response::HTTP_UNAUTHORIZED, 'UNAUTHORIZED');
    }

    /**
     * Forbidden response (HTTP 403)
     */
    protected function forbiddenResponse(string $message = 'Forbidden'): JsonResponse
    {
        return $this->errorResponse($message, Response::HTTP_FORBIDDEN, 'FORBIDDEN');
    }

    /**
     * Validation error response (HTTP 422)
     *
     * @param  array<string, mixed>  $errors  Field-level validation messages
     */
    protected function validationErrorResponse(array $errors, string $message = 'Validation failed'): JsonResponse
    {
        return $this->errorResponse($message, Response::HTTP_UNPROCESSABLE_ENTITY, 'VALIDATION_ERROR', $errors);
    }

    /**
     * Server error response (HTTP 500)
     */
    protected function serverErrorResponse(string $message = 'Internal server error'): JsonResponse
    {
        return $this->errorResponse($message, Response::HTTP_INTERNAL_SERVER_ERROR, 'SERVER_ERROR');
    }

    // -------------------------------------------------------------------------
    // Short-form aliases used in some controllers
    // -------------------------------------------------------------------------

    /**
     * Alias for successResponse
     */
    protected function success(mixed $data = null, string $message = 'Success', int $code = Response::HTTP_OK): JsonResponse
    {
        return $this->successResponse($data, $message, $code);
    }

    /**
     * Alias for notFoundResponse
     */
    protected function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return $this->notFoundResponse($message);
    }

    /**
     * Alias for errorResponse (short form, no error-code or details)
     */
    protected function error(string $message = 'Error', int $code = Response::HTTP_BAD_REQUEST): JsonResponse
    {
        return $this->errorResponse($message, $code);
    }
}
