<?php

declare(strict_types=1);

namespace App\Utils;

use Exception;
use Throwable;

/**
 * HTTP Exception for request failures
 */
class HttpException extends Exception
{
    private int $statusCode;

    private ?HttpResponse $response;

    public function __construct(
        string $message,
        int $statusCode = 0,
        ?HttpResponse $response = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $previous);
        $this->statusCode = $statusCode;
        $this->response = $response;
    }

    /**
     * Get the HTTP status code
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Get the HTTP response
     */
    public function getResponse(): ?HttpResponse
    {
        return $this->response;
    }

    /**
     * Get the response body as array
     */
    public function getResponseBody(): ?array
    {
        return $this->response?->json();
    }

    /**
     * Check if the error was a client error (4xx)
     */
    public function isClientError(): bool
    {
        return $this->statusCode >= 400 && $this->statusCode < 500;
    }

    /**
     * Check if the error was a server error (5xx)
     */
    public function isServerError(): bool
    {
        return $this->statusCode >= 500;
    }

    /**
     * Check if it was a rate limit error
     */
    public function isRateLimited(): bool
    {
        return $this->statusCode === 429;
    }

    /**
     * Check if it was an authentication error
     */
    public function isAuthError(): bool
    {
        return $this->statusCode === 401 || $this->statusCode === 403;
    }

    /**
     * Check if resource was not found
     */
    public function isNotFound(): bool
    {
        return $this->statusCode === 404;
    }
}
