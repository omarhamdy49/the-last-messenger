<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Events;

use App\Domain\Shared\Events\DomainEvent;

/**
 * Event raised when a new book is created
 */
final class BookCreated extends DomainEvent
{
    public function __construct(
        public readonly int $bookId,
        public readonly int $collectionId,
        public readonly int $bookNumber,
        public readonly string $titleAr,
        public readonly ?string $titleEn,
    ) {
        parent::__construct();
    }

    public function getAggregateId(): string
    {
        return (string) $this->bookId;
    }

    public function getEventName(): string
    {
        return 'hadith.book.created';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'book_id' => $this->bookId,
            'collection_id' => $this->collectionId,
            'book_number' => $this->bookNumber,
            'title_ar' => $this->titleAr,
            'title_en' => $this->titleEn,
            'occurred_at' => $this->occurredAt->toIso8601String(),
        ];
    }
}
