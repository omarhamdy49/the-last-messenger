<?php

declare(strict_types=1);

namespace App\Domain\Content\Enums;

enum HomeSectionKey: string
{
    case HERO = 'hero';
    case HERO_CARDS = 'hero_cards';
    case SAHABA_CAROUSEL = 'sahaba_carousel';
    case SEAL_BAND = 'seal_band';
    case DAILY = 'daily';
    case EXPLORE = 'explore';
    case FEATURED_CONTENT = 'featured_content';
    case CTA = 'cta';
}
