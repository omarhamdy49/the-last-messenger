<?php

declare(strict_types=1);

namespace App\Domain\Quran\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a reciter is deleted
 */
final class ReciterDeleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $reciterId,
    ) {}
}
