<?php

declare(strict_types=1);

namespace App\Domain\Quran\Import\Support;

use Closure;

/**
 * Keeps an importer under a requests-per-second ceiling and computes retry delays.
 * Shared by the QF client and the mp3quran fetcher. The sleeper is injectable so
 * tests do not actually wait.
 */
final class Throttle
{
    private float $lastCallAt = 0.0;

    /** @var Closure(int): void */
    private Closure $sleeper;

    public function __construct(private readonly int $perSecond, ?Closure $sleeper = null)
    {
        $this->sleeper = $sleeper ?? static function (int $microseconds): void {
            usleep($microseconds);
        };
    }

    public function wait(): void
    {
        if ($this->perSecond <= 0) {
            return;
        }

        $interval = 1 / $this->perSecond;
        $elapsed = microtime(true) - $this->lastCallAt;
        if ($this->lastCallAt > 0 && $elapsed < $interval) {
            ($this->sleeper)((int) (($interval - $elapsed) * 1_000_000));
        }

        $this->lastCallAt = microtime(true);
    }

    /**
     * Sleep before retry attempt `$attempt` (1-based): honour Retry-After when the
     * server sent one, else exponential backoff with jitter, capped at 30 s.
     */
    public function backoff(int $attempt, ?string $retryAfter): void
    {
        if ($retryAfter !== null && is_numeric($retryAfter)) {
            $seconds = min(30.0, max(0.0, (float) $retryAfter));
        } else {
            $seconds = min(30.0, (2 ** $attempt) + random_int(0, 1000) / 1000);
        }

        if ($seconds > 0) {
            ($this->sleeper)((int) ($seconds * 1_000_000));
        }
    }
}
