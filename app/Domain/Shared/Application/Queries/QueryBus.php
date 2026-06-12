<?php

declare(strict_types=1);

namespace App\Domain\Shared\Application\Queries;

use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

/**
 * Simple Query Bus Implementation
 *
 * Dispatches queries to their handlers using Laravel's container.
 */
final class QueryBus
{
    /**
     * @var array<class-string<Query>, class-string<QueryHandler>>
     */
    private array $handlers = [];

    public function __construct(
        private readonly Container $container
    ) {}

    /**
     * Register a handler for a query
     *
     * @param  class-string<Query>  $queryClass
     * @param  class-string<QueryHandler>  $handlerClass
     */
    public function register(string $queryClass, string $handlerClass): void
    {
        $this->handlers[$queryClass] = $handlerClass;
    }

    /**
     * Dispatch a query to its handler
     *
     * @return mixed The result from the handler
     *
     * @throws InvalidArgumentException If no handler is registered
     */
    public function dispatch(Query $query): mixed
    {
        $queryClass = $query::class;

        if (! isset($this->handlers[$queryClass])) {
            throw new InvalidArgumentException(
                "No handler registered for query: {$queryClass}"
            );
        }

        $handler = $this->container->make($this->handlers[$queryClass]);

        return $handler->handle($query);
    }

    /**
     * Check if a handler is registered for a query
     *
     * @param  class-string<Query>  $queryClass
     */
    public function hasHandler(string $queryClass): bool
    {
        return isset($this->handlers[$queryClass]);
    }
}
