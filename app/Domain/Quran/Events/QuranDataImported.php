<?php

declare(strict_types=1);

namespace App\Domain\Quran\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when Quran data is imported from JSON
 */
final class QuranDataImported
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<string, mixed>  $results
     */
    public function __construct(
        public readonly string $type,
        public readonly string $filePath,
        public readonly array $results,
    ) {}
}
