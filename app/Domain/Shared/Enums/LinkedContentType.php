<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

use App\Domain\Shared\Contracts\ContentReferenceLookup;

/**
 * Content from another module that a record can link to.
 *
 * The stored value is this short key, never a class name, so a linking module
 * (e.g. Seerah) holds ids only and never imports the target module. Resolution
 * to titles/URLs goes through {@see ContentReferenceLookup}.
 */
enum LinkedContentType: string
{
    case VERSE = 'verse';
    case HADITH = 'hadith';
    case MUSLIM = 'muslim';
    case ARTICLE = 'article';
    case EPISODE = 'episode';
    case TAG = 'tag';
    case CATEGORY = 'category';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $type): string => $type->value, self::cases());
    }
}
