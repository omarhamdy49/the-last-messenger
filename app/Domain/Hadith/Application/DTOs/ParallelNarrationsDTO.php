<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\DTOs;

/**
 * Parallel Narrations Data Transfer Object
 *
 * The base hadith plus every other hadith that shares the same normalized
 * Arabic text hash (text_hash) — i.e. the same narration cited in multiple
 * collections/books. Used by the public side-by-side comparison page.
 */
final readonly class ParallelNarrationsDTO
{
    /**
     * @param  array<int, HadithDetailDTO>  $parallels
     */
    public function __construct(
        public HadithDetailDTO $base,
        public array $parallels = [],
    ) {}
}
