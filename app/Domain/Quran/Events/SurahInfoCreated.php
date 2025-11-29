<?php

declare(strict_types=1);

namespace App\Domain\Quran\Events;

use App\Domain\Quran\Application\DTOs\SurahInfoDTO;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when Surah info is created
 */
final class SurahInfoCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly SurahInfoDTO $surahInfo,
    ) {}
}
