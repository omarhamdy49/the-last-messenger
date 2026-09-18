<?php

declare(strict_types=1);

namespace App\Domain\Quran\Enums;

/**
 * Where a catalogue row came from: the QF catalogue, or created by an editor.
 */
enum ResourceSource: string
{
    case QF = 'qf';
    case MANUAL = 'manual';
}
