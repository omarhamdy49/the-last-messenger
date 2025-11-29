<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Events;

use App\Domain\Shared\Events\DomainEvent;

/**
 * Event raised when hadiths are imported from file
 */
final class HadithsImported extends DomainEvent
{
    public function __construct(
        public readonly int $collectionId,
        public readonly string $filePath,
        public readonly int $importedCount,
        public readonly int $skippedCount = 0,
        public readonly int $failedCount = 0,
    ) {
        parent::__construct();
    }

    public function getAggregateId(): string
    {
        return (string) $this->collectionId;
    }

    public function getEventName(): string
    {
        return 'hadith.imported';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'collection_id' => $this->collectionId,
            'file_path' => $this->filePath,
            'imported_count' => $this->importedCount,
            'skipped_count' => $this->skippedCount,
            'failed_count' => $this->failedCount,
            'occurred_at' => $this->occurredAt->toIso8601String(),
        ];
    }
}
