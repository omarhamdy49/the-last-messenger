<?php

declare(strict_types=1);

namespace App\Domain\Shared\ValueObjects;

/**
 * The authenticated public-API application for one request: what the middleware,
 * scope check and per-client rate limiter need, without the model or its secret.
 */
final readonly class ApiClientIdentity
{
    /**
     * @param  list<string>  $scopes
     * @param  list<string>|null  $allowedOrigins
     */
    public function __construct(
        public int $id,
        public string $name,
        public array $scopes,
        public ?array $allowedOrigins,
        public int $rateLimitPerMinute,
    ) {}

    public function hasScope(string $scope): bool
    {
        return in_array('*', $this->scopes, true) || in_array($scope, $this->scopes, true);
    }

    /**
     * No Origin header (server-to-server, mobile) or no allow-list → allowed.
     * Otherwise the origin must match exactly.
     */
    public function allowsOrigin(?string $origin): bool
    {
        if ($origin === null || $origin === '' || $this->allowedOrigins === null || $this->allowedOrigins === []) {
            return true;
        }

        return in_array(rtrim($origin, '/'), array_map(static fn (string $o): string => rtrim($o, '/'), $this->allowedOrigins), true);
    }
}
