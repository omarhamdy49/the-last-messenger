<?php

declare(strict_types=1);

namespace App\Domain\Shared\Events;

/**
 * Event Dispatcher Interface
 *
 * Contract for dispatching domain events.
 * Implementation can use Laravel's event system.
 */
interface EventDispatcherInterface
{
    /**
     * Dispatch a single domain event
     */
    public function dispatch(DomainEvent $event): void;

    /**
     * Dispatch multiple domain events
     *
     * @param DomainEvent[] $events
     */
    public function dispatchAll(array $events): void;
}
