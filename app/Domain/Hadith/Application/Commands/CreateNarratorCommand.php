<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Commands;

use App\Domain\Shared\Application\Commands\Command;

/**
 * Command to create a new Hadith Narrator
 */
final readonly class CreateNarratorCommand implements Command
{
    /**
     * @param string $nameAr Arabic name
     * @param string|null $nameEn English name
     * @param string|null $kuniya Kuniya (honorific name)
     * @param string|null $nasab Lineage
     * @param string|null $laqab Title/nickname
     * @param int|null $birthYear Birth year (Hijri)
     * @param int|null $deathYear Death year (Hijri)
     * @param string|null $biographyAr Arabic biography
     * @param string|null $biographyEn English biography
     * @param string|null $reliability Reliability rating
     */
    public function __construct(
        public string $nameAr,
        public ?string $nameEn = null,
        public ?string $kuniya = null,
        public ?string $nasab = null,
        public ?string $laqab = null,
        public ?int $birthYear = null,
        public ?int $deathYear = null,
        public ?string $biographyAr = null,
        public ?string $biographyEn = null,
        public ?string $reliability = null,
    ) {}
}
