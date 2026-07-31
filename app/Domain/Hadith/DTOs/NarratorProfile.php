<?php

declare(strict_types=1);

namespace App\Domain\Hadith\DTOs;

use App\Domain\Hadith\Models\HadithScholar;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class NarratorProfile
{
    public function __construct(
        public HadithScholar $narrator,
        public LengthAwarePaginator $hadiths,
    ) {}
}
