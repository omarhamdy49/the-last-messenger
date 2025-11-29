<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Events;

use App\Domain\Shared\Events\DomainEvent;

/**
 * Event raised when a hadith is deleted
 */
final class HadithDeleted extends DomainEvent
{
    public function __construct(
        public readonly int $hadithId,
        public readonly int $bookId,
        public readonly string $hadithNumber,
        public readonly bool $forceDeleted = false,
    ) {
        parent::__construct();
    }

    public function getAggregateId(): string
    {
        return (string) $this->hadithId;
    }

    public function getEventName(): string
    {
        return 'hadith.deleted';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'hadith_id' => $this->hadithId,
            'book_id' => $this->bookId,
            'hadith_number' => $this->hadithNumber,
            'force_deleted' => $this->forceDeleted,
            'occurred_at' => $this->occurredAt->toIso8601String(),
        ];
    }
}
