<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

/**
 * Export Module Enum
 * Defines available modules for export
 */
enum ExportModule: string
{
    case ARTICLES = 'articles';
    case HADITH = 'hadith';
    case MEDIA_PROGRAMS = 'media_programs';
    case MEDIA_EPISODES = 'media_episodes';
    case SEERAH_EVENTS = 'seerah_events';
    case SAHABA = 'sahaba';
    case QURAN_CHAPTERS = 'quran_chapters';
    case QURAN_VERSES = 'quran_verses';
    case QURAN_TRANSLATIONS = 'quran_translations';
    case QURAN_TAFSIRS = 'quran_tafsirs';
    case QURAN_RECITERS = 'quran_reciters';

    /**
     * Get all values as array
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get display name for module
     */
    public function getDisplayName(): string
    {
        return match ($this) {
            self::ARTICLES => 'Articles',
            self::HADITH => 'Hadith',
            self::MEDIA_PROGRAMS => 'Media Programs',
            self::MEDIA_EPISODES => 'Media Episodes',
            self::SEERAH_EVENTS => 'Seerah Events',
            self::SAHABA => 'Companions (Sahaba)',
            self::QURAN_CHAPTERS => 'Quran Chapters',
            self::QURAN_VERSES => 'Quran Verses',
            self::QURAN_TRANSLATIONS => 'Quran Translations',
            self::QURAN_TAFSIRS => 'Quran Tafsirs',
            self::QURAN_RECITERS => 'Quran Reciters',
        };
    }
}
