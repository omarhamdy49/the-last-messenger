<?php

declare(strict_types=1);

namespace App\Domain\Shared\Application\Commands;

use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

/**
 * Simple Command Bus Implementation
 *
 * Dispatches commands to their handlers using Laravel's container.
 */
final class CommandBus
{
    /**
     * @var array<class-string<Command>, class-string<CommandHandler>>
     */
    private array $handlers = [];

    public function __construct(
        private readonly Container $container
    ) {}

    /**
     * Register a handler for a command
     *
     * @param  class-string<Command>  $commandClass
     * @param  class-string<CommandHandler>  $handlerClass
     */
    public function register(string $commandClass, string $handlerClass): void
    {
        $this->handlers[$commandClass] = $handlerClass;
    }

    /**
     * Dispatch a command to its handler
     *
     * @return mixed The result from the handler
     *
     * @throws InvalidArgumentException If no handler is registered
     */
    public function dispatch(Command $command): mixed
    {
        $commandClass = $command::class;

        if (! isset($this->handlers[$commandClass])) {
            throw new InvalidArgumentException(
                "No handler registered for command: {$commandClass}"
            );
        }

        $handler = $this->container->make($this->handlers[$commandClass]);

        return $handler->handle($command);
    }

    /**
     * Check if a handler is registered for a command
     *
     * @param  class-string<Command>  $commandClass
     */
    public function hasHandler(string $commandClass): bool
    {
        return isset($this->handlers[$commandClass]);
    }
}
