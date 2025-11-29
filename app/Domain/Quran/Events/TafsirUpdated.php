<?php

declare(strict_types=1);

namespace App\Domain\Quran\Events;

use App\Domain\Quran\Application\DTOs\TafsirDTO;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a tafsir is updated
 */
final class TafsirUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly TafsirDTO $tafsir,
    ) {}
}
