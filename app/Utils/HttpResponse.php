<?php

declare(strict_types=1);

namespace App\Utils;

use Illuminate\Support\Collection;
use Psr\Http\Message\ResponseInterface;

/**
 * HTTP Response wrapper with helper methods
 */
class HttpResponse
{
    private ResponseInterface $response;

    private mixed $decodedBody = null;

    private bool $bodyDecoded = false;

    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    /**
     * Get the status code
     */
    public function getStatusCode(): int
    {
        return $this->response->getStatusCode();
    }

    /**
     * Check if response was successful (2xx)
     */
    public function isSuccessful(): bool
    {
        $code = $this->getStatusCode();

        return $code >= 200 && $code < 300;
    }

    /**
     * Check if response was a client error (4xx)
     */
    public function isClientError(): bool
    {
        $code = $this->getStatusCode();

        return $code >= 400 && $code < 500;
    }

    /**
     * Check if response was a server error (5xx)
     */
    public function isServerError(): bool
    {
        $code = $this->getStatusCode();

        return $code >= 500;
    }

    /**
     * Check if response was a redirect (3xx)
     */
    public function isRedirect(): bool
    {
        $code = $this->getStatusCode();

        return $code >= 300 && $code < 400;
    }

    /**
     * Get the raw response body
     */
    public function getBody(): string
    {
        return (string) $this->response->getBody();
    }

    /**
     * Get the response body as JSON (decoded to array)
     */
    public function json(): array
    {
        if (! $this->bodyDecoded) {
            $this->decodedBody = json_decode($this->getBody(), true) ?? [];
            $this->bodyDecoded = true;
        }

        return $this->decodedBody;
    }

    /**
     * Get the response body as object
     */
    public function object(): ?object
    {
        return json_decode($this->getBody());
    }

    /**
     * Get a specific value from JSON response
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->json(), $key, $default);
    }

    /**
     * Get a response header
     */
    public function getHeader(string $name): ?string
    {
        $values = $this->response->getHeader($name);

        return $values[0] ?? null;
    }

    /**
     * Get all response headers
     */
    public function getHeaders(): array
    {
        return $this->response->getHeaders();
    }

    /**
     * Check if a header exists
     */
    public function hasHeader(string $name): bool
    {
        return $this->response->hasHeader($name);
    }

    /**
     * Get the reason phrase
     */
    public function getReasonPhrase(): string
    {
        return $this->response->getReasonPhrase();
    }

    /**
     * Get the underlying PSR response
     */
    public function getPsrResponse(): ResponseInterface
    {
        return $this->response;
    }

    /**
     * Throw an exception if the response failed
     */
    public function throw(): self
    {
        if (! $this->isSuccessful()) {
            throw new HttpException(
                message: $this->getReasonPhrase() ?: 'HTTP request failed',
                statusCode: $this->getStatusCode(),
                response: $this
            );
        }

        return $this;
    }

    /**
     * Execute callback if successful
     */
    public function onSuccess(callable $callback): self
    {
        if ($this->isSuccessful()) {
            $callback($this);
        }

        return $this;
    }

    /**
     * Execute callback if failed
     */
    public function onError(callable $callback): self
    {
        if (! $this->isSuccessful()) {
            $callback($this);
        }

        return $this;
    }

    /**
     * Collect data from response and return
     */
    public function collect(?string $key = null): Collection
    {
        $data = $key ? $this->get($key, []) : $this->json();

        return collect($data);
    }
}
