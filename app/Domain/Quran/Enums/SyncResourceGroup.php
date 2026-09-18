<?php

declare(strict_types=1);

namespace App\Domain\Quran\Enums;

/**
 * QF Content Sync resource groups (`group:id` in the resources filter).
 */
enum SyncResourceGroup: string
{
    case ARTICLES = 'articles';
    case CHAPTER_RECITATIONS = 'chapter_recitations';
    case MUSHAFS = 'mushafs';
    case RECITATIONS = 'recitations';
    case TAFSIRS = 'tafsirs';
    case TRANSLATIONS = 'translations';
    case WORD_BY_WORD_TRANSLATIONS = 'word_by_word_translations';
    case WORD_BY_WORD_TRANSLITERATIONS = 'word_by_word_transliterations';

    /**
     * The catalogue kind this group's resource ids refer to. Mushafs live in
     * quran_mushafs and articles in their own table, so they map to none.
     */
    public function resourceKind(): ?ResourceKind
    {
        return match ($this) {
            self::TRANSLATIONS => ResourceKind::TRANSLATION,
            self::TAFSIRS => ResourceKind::TAFSIR,
            self::RECITATIONS => ResourceKind::RECITATION,
            self::CHAPTER_RECITATIONS => ResourceKind::CHAPTER_RECITER,
            self::WORD_BY_WORD_TRANSLATIONS => ResourceKind::WORD_TRANSLATION,
            self::WORD_BY_WORD_TRANSLITERATIONS => ResourceKind::WORD_TRANSLITERATION,
            self::MUSHAFS, self::ARTICLES => null,
        };
    }
}
