<?php

declare(strict_types=1);

namespace App\Domain\Media\Enums;

use App\Domain\Media\Models\LiveChannel;
use App\Domain\Media\Models\MediaEpisode;
use App\Domain\Media\Models\MediaProgram;

/**
 * What kind of media item a view event points at.
 * Stored as the string value in media_view_events.viewable_type.
 */
enum MediaViewableType: string
{
    case Program = 'program';
    case Episode = 'episode';
    case LiveChannel = 'live_channel';

    /**
     * @return class-string<MediaProgram|MediaEpisode|LiveChannel>
     */
    public function modelClass(): string
    {
        return match ($this) {
            self::Program => MediaProgram::class,
            self::Episode => MediaEpisode::class,
            self::LiveChannel => LiveChannel::class,
        };
    }
}
