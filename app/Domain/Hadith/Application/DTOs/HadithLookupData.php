<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\DTOs;

/**
 * Hadith Lookup Data DTO
 *
 * Carries the minimal write-side payload used by HadithDeduplicationService
 * when creating a new Hadith record via getOrCreateByText().
 *
 * Only the fields that Hadith::create() actually needs are included here;
 * text_hash is intentionally excluded because the service derives and
 * appends it from the arabic text before persisting.
 */
final class HadithLookupData
{
    public function __construct(
        public readonly int $bookId,
        public readonly ?string $hadithNumber,
        public readonly ?string $gradingStatus,
    ) {}

    /**
     * Convert to the array shape expected by Hadith::create().
     *
     * text_hash is NOT included here; the caller appends it before persisting.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'book_id' => $this->bookId,
            'hadith_number' => $this->hadithNumber,
            'grading_status' => $this->gradingStatus,
        ];
    }
}
