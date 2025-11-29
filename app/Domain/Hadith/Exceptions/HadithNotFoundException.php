<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Exceptions;

use App\Domain\Shared\Exceptions\EntityNotFoundException;

/**
 * Hadith Not Found Exception
 *
 * Thrown when a Hadith entity cannot be found.
 */
class HadithNotFoundException extends EntityNotFoundException
{
    /**
     * Create for hadith ID
     */
    public static function withHadithId(int $id): static
    {
        return static::withId('Hadith', $id);
    }

    /**
     * Create for book and hadith number
     */
    public static function withBookAndNumber(int $bookId, string $hadithNumber): static
    {
        return new static(
            message: "الحديث رقم {$hadithNumber} في الكتاب {$bookId} غير موجود | Hadith #{$hadithNumber} in book {$bookId} not found",
            context: [
                'entity_type' => 'Hadith',
                'book_id' => $bookId,
                'hadith_number' => $hadithNumber,
            ],
            code: 404
        );
    }
}
