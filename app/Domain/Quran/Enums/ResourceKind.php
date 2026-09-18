<?php

declare(strict_types=1);

namespace App\Domain\Quran\Enums;

/**
 * Every kind of catalogue resource in quran_resources.
 *
 * `recitation` (ayah-by-ayah) and `chapter_reciter` (one file per chapter) are two
 * separate QF id spaces and must never be interchanged.
 */
enum ResourceKind: string
{
    case TRANSLATION = 'translation';
    case TAFSIR = 'tafsir';
    case CHAPTER_INFO = 'chapter_info';
    case VERSE_MEDIA = 'verse_media';
    case RECITATION = 'recitation';
    case CHAPTER_RECITER = 'chapter_reciter';
    case WORD_TRANSLATION = 'word_translation';
    case WORD_TRANSLITERATION = 'word_transliteration';

    /**
     * The QF Content Sync group that carries this kind's rows, if any.
     */
    public function syncGroup(): ?SyncResourceGroup
    {
        return match ($this) {
            self::TRANSLATION => SyncResourceGroup::TRANSLATIONS,
            self::TAFSIR => SyncResourceGroup::TAFSIRS,
            self::RECITATION => SyncResourceGroup::RECITATIONS,
            self::CHAPTER_RECITER => SyncResourceGroup::CHAPTER_RECITATIONS,
            self::WORD_TRANSLATION => SyncResourceGroup::WORD_BY_WORD_TRANSLATIONS,
            self::WORD_TRANSLITERATION => SyncResourceGroup::WORD_BY_WORD_TRANSLITERATIONS,
            self::CHAPTER_INFO, self::VERSE_MEDIA => null,
        };
    }

    /**
     * QF catalogue endpoint listing resources of this kind, if one exists.
     */
    public function qfCataloguePath(): ?string
    {
        return match ($this) {
            self::TRANSLATION => 'resources/translations',
            self::TAFSIR => 'resources/tafsirs',
            self::CHAPTER_INFO => 'resources/chapter_infos',
            self::VERSE_MEDIA => 'resources/verse_media',
            self::RECITATION => 'resources/recitations',
            self::CHAPTER_RECITER => 'resources/chapter_reciters',
            self::WORD_TRANSLATION, self::WORD_TRANSLITERATION => null,
        };
    }

    /**
     * JSON key holding the list in the catalogue response.
     */
    public function qfCatalogueKey(): ?string
    {
        return match ($this) {
            self::TRANSLATION => 'translations',
            self::TAFSIR => 'tafsirs',
            self::CHAPTER_INFO => 'chapter_infos',
            self::VERSE_MEDIA => 'verse_media',
            self::RECITATION => 'recitations',
            self::CHAPTER_RECITER => 'reciters',
            self::WORD_TRANSLATION, self::WORD_TRANSLITERATION => null,
        };
    }

    /**
     * Kinds with an `/resources/{kind}/{id}/info` endpoint.
     */
    public function hasQfInfo(): bool
    {
        return in_array($this, [self::TRANSLATION, self::TAFSIR, self::RECITATION], true);
    }

    public function isReciter(): bool
    {
        return $this === self::RECITATION || $this === self::CHAPTER_RECITER;
    }

    public function isWordGloss(): bool
    {
        return $this === self::WORD_TRANSLATION || $this === self::WORD_TRANSLITERATION;
    }

    /**
     * URL segment used by admin/API routes (`translations`, `chapter_reciters`, …).
     */
    public function routeSegment(): string
    {
        return match ($this) {
            self::VERSE_MEDIA => 'verse_media',
            default => $this->value.'s',
        };
    }

    public static function fromRouteSegment(string $segment): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->routeSegment() === $segment) {
                return $case;
            }
        }

        return null;
    }

    public function label(?string $locale = null): string
    {
        return (string) __('quran.resource_kinds.'.$this->value, [], $locale);
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
