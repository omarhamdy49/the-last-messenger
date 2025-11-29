<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Commands;

/**
 * Command to update a tafsir (verse interpretation)
 */
final readonly class UpdateTafsirCommand
{
    public function __construct(
        public int $id,
        public ?string $lang = null,
        public ?string $text = null,
        public ?bool $isActive = null,
    ) {}
}
