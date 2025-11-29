<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exceptions;

use Exception;
use Throwable;

/**
 * Base Domain Exception
 *
 * All domain-specific exceptions should extend this class.
 * Provides context for domain-level errors.
 */
class DomainException extends Exception
{
    /**
     * @var array<string, mixed>
     */
    protected array $context = [];

    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        string $message = '',
        array $context = [],
        int $code = 0,
        ?Throwable $previous = null
    ) {
        $this->context = $context;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get exception context
     *
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Add context data
     *
     * @return $this
     */
    public function withContext(string $key, mixed $value): static
    {
        $this->context[$key] = $value;

        return $this;
    }

    /**
     * Get domain name (derived from namespace)
     */
    public function getDomain(): string
    {
        $namespace = static::class;
        $parts = explode('\\', $namespace);

        // Find "Domain" in namespace and get next part
        $domainIndex = array_search('Domain', $parts, true);
        if ($domainIndex !== false && isset($parts[$domainIndex + 1])) {
            return $parts[$domainIndex + 1];
        }

        return 'Unknown';
    }

    /**
     * Convert to array for logging/API response
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'domain' => $this->getDomain(),
            'exception' => class_basename(static::class),
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'context' => $this->context,
        ];
    }
}
