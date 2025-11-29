<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Events;

use App\Domain\Shared\Events\DomainEvent;

/**
 * Event raised when hadiths are synced from external API
 */
final class HadithsSynced extends DomainEvent
{
    public function __construct(
        public readonly int $bookId,
        public readonly string $collectionName,
        public readonly string $bookNumber,
        public readonly int $syncedCount,
        public readonly int $failedCount = 0,
        public readonly bool $dryRun = false,
    ) {
        parent::__construct();
    }

    public function getAggregateId(): string
    {
        return (string) $this->bookId;
    }

    public function getEventName(): string
    {
        return 'hadith.synced';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'book_id' => $this->bookId,
            'collection_name' => $this->collectionName,
            'book_number' => $this->bookNumber,
            'synced_count' => $this->syncedCount,
            'failed_count' => $this->failedCount,
            'dry_run' => $this->dryRun,
            'occurred_at' => $this->occurredAt->toIso8601String(),
        ];
    }
}
