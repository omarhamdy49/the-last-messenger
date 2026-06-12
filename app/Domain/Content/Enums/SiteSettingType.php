<?php

declare(strict_types=1);

namespace App\Domain\Content\Enums;

/**
 * Value types a site setting can hold.
 *
 * Translatable types (TEXT / TEXTAREA / RICHTEXT) keep their per-locale value
 * in `site_setting_translations`; the rest store a single value in
 * `site_settings.value`.
 */
enum SiteSettingType: string
{
    case TEXT = 'text';
    case TEXTAREA = 'textarea';
    case RICHTEXT = 'richtext';
    case IMAGE = 'image';
    case URL = 'url';
    case BOOL = 'bool';

    /**
     * All type values.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
