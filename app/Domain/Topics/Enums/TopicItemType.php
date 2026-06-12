<?php

declare(strict_types=1);

namespace App\Domain\Topics\Enums;

/**
 * Topic Item Type Enum.
 *
 * The stable morph aliases stored in `topic_items.itemable_type`. Using short
 * aliases (not FQCNs) keeps the persisted value decoupled from class locations
 * and lets the Topics module avoid compile-time imports of other domains' model
 * classes — the alias↔class binding is registered at boot in TopicsServiceProvider
 * via Relation::enforceMorphMap() (the deptrac-safe pattern, mirroring FavorableRegistry).
 */
enum TopicItemType: string
{
    case VERSE = 'verse';
    case HADITH = 'hadith';
    case ARTICLE = 'article';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $t): string => $t->value, self::cases());
    }
}
