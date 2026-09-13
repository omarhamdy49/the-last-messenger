<?php

declare(strict_types=1);

namespace App\Domain\Quran\DTOs;

final readonly class AssignmentResult
{
    /**
     * @param  array<int, int>  $items  surahId => recordingId
     * @param  list<int>  $uncovered  surah ids none of the chosen people cover
     * @param  array<int, array<int, list<int>>>  $alternatives  surahId => profileId => recording ids that cover it
     */
    public function __construct(
        public array $items,
        public array $uncovered,
        public array $alternatives,
    ) {}

    /**
     * @return array{items: array<int, int>, uncovered: list<int>, alternatives: array<int, array<int, list<int>>>}
     */
    public function toArray(): array
    {
        return ['items' => $this->items, 'uncovered' => $this->uncovered, 'alternatives' => $this->alternatives];
    }
}
