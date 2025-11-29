<?php

declare(strict_types=1);

namespace App\Domain\Quran\Events;

use App\Domain\Quran\Application\DTOs\VerseDTO;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a verse is updated
 */
final class VerseUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly VerseDTO $verse,
    ) {}
}
