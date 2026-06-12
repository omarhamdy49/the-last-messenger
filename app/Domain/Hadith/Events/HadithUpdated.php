<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Events;

use App\Domain\Shared\Events\DomainEvent;

/**
 * Event raised when a hadith is updated
 */
final class HadithUpdated extends DomainEvent
{
    /**
     * @param  array<string, mixed>  $changedFields
     */
    public function __construct(
        public readonly int $hadithId,
        public readonly array $changedFields = [],
    ) {
        parent::__construct();
    }

    public function getAggregateId(): string
    {
        return (string) $this->hadithId;
    }

    public function getEventName(): string
    {
        return 'hadith.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'hadith_id' => $this->hadithId,
            'changed_fields' => $this->changedFields,
            'occurred_at' => $this->occurredAt->toIso8601String(),
        ];
    }
}
