<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Exceptions;

use App\Domain\Shared\Exceptions\EntityNotFoundException;

/**
 * Hadith Collection Not Found Exception
 *
 * Thrown when a Hadith Collection cannot be found.
 */
class CollectionNotFoundException extends EntityNotFoundException
{
    /**
     * Create for collection ID
     */
    public static function withCollectionId(int $id): static
    {
        return static::withId('HadithCollection', $id);
    }

    /**
     * Create for collection slug
     */
    public static function withCollectionSlug(string $slug): static
    {
        return new static(
            message: "المجموعة بالمعرف {$slug} غير موجودة | Collection with slug {$slug} not found",
            context: [
                'entity_type' => 'HadithCollection',
                'slug' => $slug,
            ],
            code: 404
        );
    }

    /**
     * Create for collection abbreviation
     */
    public static function withAbbreviation(string $abbreviation): static
    {
        return new static(
            message: "المجموعة بالاختصار {$abbreviation} غير موجودة | Collection with abbreviation {$abbreviation} not found",
            context: [
                'entity_type' => 'HadithCollection',
                'abbreviation' => $abbreviation,
            ],
            code: 404
        );
    }
}
