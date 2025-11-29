<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Commands;

/**
 * Command to update a verse (ayah)
 */
final readonly class UpdateVerseCommand
{
    public function __construct(
        public int $id,
        public ?int $juzNumber = null,
        public ?int $hizbNumber = null,
        public ?int $rubElHizbNumber = null,
        public ?int $rukuNumber = null,
        public ?int $manzilNumber = null,
        public ?int $sajdahNumber = null,
        public ?int $pageNumber = null,
    ) {}
}
