<?php

declare(strict_types=1);

namespace App\Domain\Shared\Events;

use DateTimeImmutable;
use JsonSerializable;

/**
 * Base Domain Event
 *
 * All domain events should extend this class.
 * Domain events represent something that happened in the domain.
 */
abstract class DomainEvent implements JsonSerializable
{
    private readonly DateTimeImmutable $occurredAt;

    private readonly string $eventId;

    public function __construct()
    {
        $this->occurredAt = new DateTimeImmutable();
        $this->eventId = uniqid('evt_', true);
    }

    /**
     * Get when the event occurred
     */
    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    /**
     * Get unique event ID
     */
    public function eventId(): string
    {
        return $this->eventId;
    }

    /**
     * Get event name (class name by default)
     */
    public function eventName(): string
    {
        return static::class;
    }

    /**
     * Get event short name
     */
    public function shortName(): string
    {
        return class_basename(static::class);
    }

    /**
     * Get aggregate root ID (if applicable)
     */
    abstract public function aggregateId(): int|string;

    /**
     * Get aggregate type
     */
    abstract public function aggregateType(): string;

    /**
     * Convert to array for serialization
     *
     * @return array<string, mixed>
     */
    abstract public function toArray(): array;

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'event_id' => $this->eventId,
            'event_name' => $this->shortName(),
            'aggregate_type' => $this->aggregateType(),
            'aggregate_id' => $this->aggregateId(),
            'occurred_at' => $this->occurredAt->format('c'),
            'payload' => $this->toArray(),
        ];
    }
}
