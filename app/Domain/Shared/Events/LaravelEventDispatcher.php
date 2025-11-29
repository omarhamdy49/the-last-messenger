<?php

declare(strict_types=1);

namespace App\Domain\Shared\Events;

use Illuminate\Contracts\Events\Dispatcher;

/**
 * Laravel Event Dispatcher Implementation
 *
 * Bridges domain events to Laravel's event system.
 */
final class LaravelEventDispatcher implements EventDispatcherInterface
{
    public function __construct(
        private readonly Dispatcher $dispatcher
    ) {}

    /**
     * {@inheritdoc}
     */
    public function dispatch(DomainEvent $event): void
    {
        $this->dispatcher->dispatch($event);
    }

    /**
     * {@inheritdoc}
     */
    public function dispatchAll(array $events): void
    {
        foreach ($events as $event) {
            $this->dispatch($event);
        }
    }
}
