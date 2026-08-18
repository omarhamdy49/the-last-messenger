<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

use App\Domain\Shared\Services\FavorableRegistry;

/**
 * Favorable Type Enum.
 *
 * The single source of truth for which content types a visitor may bookmark.
 * Client requests reference content by these short, stable slugs — the raw
 * Eloquent class name is NEVER accepted from the client (arbitrary-class
 * instantiation / mass-assignment guard).
 *
 * This enum intentionally holds NO references to concrete model classes: the
 * Shared layer must not import domain modules or app models (deptrac). The
 * slug↔class mapping lives in {@see FavorableRegistry},
 * which is populated at boot from a provider.
 */
enum FavorableType: string
{
    case ARTICLE = 'article';
    case HADITH = 'hadith';
    case QURAN = 'quran';
    case SEERAH = 'seerah';
    case MEDIA = 'media';

    /**
     * The Lucide icon name used to represent this type in the UI.
     */
    public function icon(): string
    {
        return match ($this) {
            self::ARTICLE => 'file-text',
            self::HADITH => 'book-open-text',
            self::QURAN => 'book-marked',
            self::SEERAH => 'map-pin',
            self::MEDIA => 'monitor-play',
        };
    }

    /**
     * Translation key (under the `profile` namespace) for this type's label.
     */
    public function labelKey(): string
    {
        return 'profile.type_'.$this->value;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $t): string => $t->value, self::cases());
    }
}
