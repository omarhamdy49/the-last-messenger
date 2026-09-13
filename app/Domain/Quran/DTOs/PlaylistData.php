<?php

declare(strict_types=1);

namespace App\Domain\Quran\DTOs;

final readonly class PlaylistData
{
    /**
     * @param  array<int, int|null>  $items  surahId => recordingId (null = needs a reciter)
     */
    public function __construct(
        public string $name,
        public ?string $description,
        public string $scope,
        public array $items,
        public bool $isPublic,
    ) {}
}
