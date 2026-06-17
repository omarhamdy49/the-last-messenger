<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Queries;

/**
 * A registered user's "Ask Quran" AI question.
 *
 * `geo` is an untrusted/advisory client snapshot (analytics only) — never used
 * for authorization, quota, or pricing.
 */
final readonly class AskQuranQuery
{
    /**
     * @param  array<string, mixed>|null  $geo
     */
    public function __construct(
        public int $userId,
        public string $question,
        public string $locale = 'ar',
        public ?array $geo = null,
        public bool $detailed = false,
    ) {}
}
