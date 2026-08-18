<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

/**
 * What the resolver decided about one piece of source text, and why.
 *
 * The reason is not decoration. Constitution X forbids summarising a
 * discrepancy away, so the backfill report has to distinguish "named no place
 * we know" from "named two places and we refused to guess" — they need
 * different editorial responses.
 */
final readonly class ResolutionOutcome
{
    public const MATCHED = 'matched';

    public const NO_MATCH = 'no-match';

    public const AMBIGUOUS = 'ambiguous';

    private function __construct(
        public ?int $locationId,
        public string $reason,
        public ?string $matchedAlias = null,
    ) {}

    public static function matched(int $locationId, string $alias): self
    {
        return new self($locationId, self::MATCHED, $alias);
    }

    public static function noMatch(): self
    {
        return new self(null, self::NO_MATCH);
    }

    public static function ambiguous(): self
    {
        return new self(null, self::AMBIGUOUS);
    }

    public function isMatched(): bool
    {
        return $this->locationId !== null;
    }
}
