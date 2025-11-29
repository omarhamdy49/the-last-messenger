<?php

declare(strict_types=1);

namespace App\Domain\Shared\ValueObjects;

use Carbon\Carbon;
use InvalidArgumentException;
use JsonSerializable;

/**
 * Value Object for Date Ranges
 *
 * Supports both Hijri and Gregorian calendars.
 * Useful for Seerah events and historical dates.
 */
final readonly class DateRange implements JsonSerializable
{
    public function __construct(
        private ?Carbon $startDate,
        private ?Carbon $endDate,
        private ?string $hijriStart = null,
        private ?string $hijriEnd = null,
        private bool $approximate = false
    ) {
        $this->validate();
    }

    /**
     * Create from Gregorian dates
     */
    public static function fromGregorian(?string $start, ?string $end = null): self
    {
        return new self(
            startDate: $start ? Carbon::parse($start) : null,
            endDate: $end ? Carbon::parse($end) : null
        );
    }

    /**
     * Create from Hijri dates (store as strings with approximate Gregorian)
     */
    public static function fromHijri(string $start, ?string $end = null): self
    {
        return new self(
            startDate: null,
            endDate: null,
            hijriStart: $start,
            hijriEnd: $end,
            approximate: true
        );
    }

    /**
     * Create single date point
     */
    public static function point(Carbon|string $date): self
    {
        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);

        return new self(
            startDate: $carbon,
            endDate: $carbon
        );
    }

    /**
     * Create open-ended range (from date onwards)
     */
    public static function from(Carbon|string $date): self
    {
        return new self(
            startDate: $date instanceof Carbon ? $date : Carbon::parse($date),
            endDate: null
        );
    }

    /**
     * Create with approximate flag
     */
    public function asApproximate(): self
    {
        return new self(
            startDate: $this->startDate,
            endDate: $this->endDate,
            hijriStart: $this->hijriStart,
            hijriEnd: $this->hijriEnd,
            approximate: true
        );
    }

    /**
     * Get start date
     */
    public function start(): ?Carbon
    {
        return $this->startDate;
    }

    /**
     * Get end date
     */
    public function end(): ?Carbon
    {
        return $this->endDate;
    }

    /**
     * Get Hijri start date
     */
    public function hijriStart(): ?string
    {
        return $this->hijriStart;
    }

    /**
     * Get Hijri end date
     */
    public function hijriEnd(): ?string
    {
        return $this->hijriEnd;
    }

    /**
     * Check if date is approximate
     */
    public function isApproximate(): bool
    {
        return $this->approximate;
    }

    /**
     * Check if this is a single point in time
     */
    public function isPoint(): bool
    {
        if ($this->startDate === null || $this->endDate === null) {
            return false;
        }

        return $this->startDate->equalTo($this->endDate);
    }

    /**
     * Check if range is open-ended
     */
    public function isOpenEnded(): bool
    {
        return $this->startDate !== null && $this->endDate === null;
    }

    /**
     * Check if date falls within range
     */
    public function contains(Carbon $date): bool
    {
        if ($this->startDate !== null && $date->lt($this->startDate)) {
            return false;
        }

        if ($this->endDate !== null && $date->gt($this->endDate)) {
            return false;
        }

        return true;
    }

    /**
     * Get duration in days
     */
    public function durationInDays(): ?int
    {
        if ($this->startDate === null || $this->endDate === null) {
            return null;
        }

        return $this->startDate->diffInDays($this->endDate);
    }

    /**
     * Format for display
     */
    public function format(string $dateFormat = 'Y-m-d'): string
    {
        $prefix = $this->approximate ? '~' : '';

        if ($this->hijriStart !== null) {
            return $prefix . $this->hijriStart .
                ($this->hijriEnd ? ' - ' . $this->hijriEnd : '') .
                ' (هجري)';
        }

        if ($this->startDate === null) {
            return '';
        }

        if ($this->isPoint()) {
            return $prefix . $this->startDate->format($dateFormat);
        }

        if ($this->isOpenEnded()) {
            return $prefix . $this->startDate->format($dateFormat) . ' - present';
        }

        return $prefix . $this->startDate->format($dateFormat) .
            ' - ' . $this->endDate->format($dateFormat);
    }

    /**
     * Check equality
     */
    public function equals(self $other): bool
    {
        $startEquals = $this->startDate?->equalTo($other->startDate)
            ?? ($other->startDate === null);
        $endEquals = $this->endDate?->equalTo($other->endDate)
            ?? ($other->endDate === null);

        return $startEquals
            && $endEquals
            && $this->hijriStart === $other->hijriStart
            && $this->hijriEnd === $other->hijriEnd;
    }

    /**
     * Validate range
     *
     * @throws InvalidArgumentException
     */
    private function validate(): void
    {
        if ($this->startDate !== null && $this->endDate !== null) {
            if ($this->startDate->gt($this->endDate)) {
                throw new InvalidArgumentException('Start date must be before or equal to end date');
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'start' => $this->startDate?->toIso8601String(),
            'end' => $this->endDate?->toIso8601String(),
            'hijri_start' => $this->hijriStart,
            'hijri_end' => $this->hijriEnd,
            'approximate' => $this->approximate,
            'formatted' => $this->format(),
        ];
    }
}
