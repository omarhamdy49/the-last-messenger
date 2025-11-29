<?php

declare(strict_types=1);

namespace App\Domain\Quran\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when Quran data is synced from external API
 */
final class QuranDataSynced
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param array<string, mixed> $results
     */
    public function __construct(
        public readonly array $results,
    ) {}
}
