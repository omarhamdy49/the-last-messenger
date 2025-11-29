<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Events;

use App\Domain\Shared\Events\DomainEvent;

/**
 * Event raised when a new collection is created
 */
final class CollectionCreated extends DomainEvent
{
    public function __construct(
        public readonly int $collectionId,
        public readonly string $nameAr,
        public readonly ?string $nameEn,
        public readonly string $slug,
    ) {
        parent::__construct();
    }

    public function getAggregateId(): string
    {
        return (string) $this->collectionId;
    }

    public function getEventName(): string
    {
        return 'hadith.collection.created';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'collection_id' => $this->collectionId,
            'name_ar' => $this->nameAr,
            'name_en' => $this->nameEn,
            'slug' => $this->slug,
            'occurred_at' => $this->occurredAt->toIso8601String(),
        ];
    }
}
