<?php

declare(strict_types=1);

namespace App\Domain\Quran\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a tafsir is deleted
 */
final class TafsirDeleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $tafsirId,
    ) {}
}
