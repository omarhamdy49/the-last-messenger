<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Services;

use App\Domain\Hadith\DTOs\NarratorProfile;
use App\Domain\Hadith\Repositories\HadithRepositoryInterface;
use App\Domain\Hadith\Repositories\ScholarRepositoryInterface;

final readonly class NarratorProfileService
{
    public function __construct(
        private ScholarRepositoryInterface $scholars,
        private HadithRepositoryInterface $hadiths,
    ) {}

    public function find(string $slug): ?NarratorProfile
    {
        $narrator = $this->scholars->findPublicNarratorBySlug($slug);

        if ($narrator === null) {
            return null;
        }

        return new NarratorProfile(
            narrator: $narrator,
            hadiths: $this->hadiths->paginateForNarrator($narrator->id),
        );
    }
}
