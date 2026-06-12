<?php

declare(strict_types=1);

namespace App\Domain\Content\Enums;

/**
 * Menu locations rendered by the public site chrome.
 *
 * - HEADER          → main navigation in the site header
 * - FOOTER_QUICK    → "Quick Links" footer column (about/contact/privacy/terms)
 * - FOOTER_MODULES  → "Modules" footer column (seerah/hadith/media/genealogy)
 * - FOOTER_SOCIAL   → social icon row in the footer
 */
enum MenuLocation: string
{
    case HEADER = 'header';
    case FOOTER_QUICK = 'footer_quick';
    case FOOTER_MODULES = 'footer_modules';
    case FOOTER_SOCIAL = 'footer_social';

    /**
     * All location values.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
