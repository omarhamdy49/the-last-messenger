<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Events;

use App\Domain\Shared\Events\DomainEvent;

/**
 * Event raised when a new hadith is created
 */
final class HadithCreated extends DomainEvent
{
    public function __construct(
        public readonly int $hadithId,
        public readonly int $bookId,
        public readonly string $hadithNumber,
        public readonly ?string $gradingStatus = null,
    ) {
        parent::__construct();
    }

    public function getAggregateId(): string
    {
        return (string) $this->hadithId;
    }

    public function getEventName(): string
    {
        return 'hadith.created';
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
            'grading_status' => $this->gradingStatus,
            'occurred_at' => $this->occurredAt->toIso8601String(),
        ];
    }
}
