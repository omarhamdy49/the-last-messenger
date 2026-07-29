<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Events;

use App\Domain\Shared\Events\DomainEvent;

/**
 * Event raised when a hadith's grading is updated
 */
final class HadithGradingUpdated extends DomainEvent
{
    public function __construct(
        public readonly int $hadithId,
        public readonly string $previousGrading,
        public readonly string $newGrading,
        public readonly ?int $scholarId = null,
    ) {
        parent::__construct();
    }

    public function getAggregateId(): string
    {
        return (string) $this->hadithId;
    }

    public function getEventName(): string
    {
        return 'hadith.grading_updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'hadith_id' => $this->hadithId,
            'previous_grading' => $this->previousGrading,
            'new_grading' => $this->newGrading,
            'scholar_id' => $this->scholarId,
            'occurred_at' => $this->occurredAt->toIso8601String(),
        ];
    }
}
