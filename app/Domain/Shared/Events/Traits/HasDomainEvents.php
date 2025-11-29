<?php

declare(strict_types=1);

namespace App\Domain\Shared\Events\Traits;

use App\Domain\Shared\Events\DomainEvent;

/**
 * Has Domain Events Trait
 *
 * Add this trait to aggregate roots to enable domain event recording.
 * Events are collected and can be dispatched after persistence.
 */
trait HasDomainEvents
{
    /**
     * @var DomainEvent[]
     */
    private array $domainEvents = [];

    /**
     * Record a domain event
     */
    protected function recordEvent(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }

    /**
     * Raise (alias for record) a domain event
     */
    protected function raise(DomainEvent $event): void
    {
        $this->recordEvent($event);
    }

    /**
     * Get all recorded domain events
     *
     * @return DomainEvent[]
     */
    public function getDomainEvents(): array
    {
        return $this->domainEvents;
    }

    /**
     * Pull all domain events (get and clear)
     *
     * @return DomainEvent[]
     */
    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }

    /**
     * Clear all recorded domain events
     */
    public function clearDomainEvents(): void
    {
        $this->domainEvents = [];
    }

    /**
     * Check if there are pending domain events
     */
    public function hasDomainEvents(): bool
    {
        return count($this->domainEvents) > 0;
    }
}
