<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Commands;

/**
 * Command to update a Surah
 *
 * Note: Surahs are fixed at 114, so we only support updates, not create/delete
 */
final readonly class UpdateSurahCommand
{
    /**
     * @param  array<int, int>|null  $pages
     */
    public function __construct(
        public int $id,
        public ?string $nameAr = null,
        public ?string $nameEn = null,
        public ?string $transliteration = null,
        public ?string $revelationPlace = null,
        public ?int $revelationOrder = null,
        public ?int $versesCount = null,
        public ?bool $bismillahPre = null,
        public ?array $pages = null,
    ) {}
}
