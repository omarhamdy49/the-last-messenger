<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

use App\Domain\Quran\Enums\SectionType;
use App\Domain\Quran\Models\Section;
use Illuminate\Support\Collection;

interface SectionRepositoryInterface
{
    /**
     * @return Collection<int, Section>
     */
    public function ofType(SectionType $type): Collection;

    public function find(SectionType $type, int $number): ?Section;

    /**
     * @return array<string, int> type value => count
     */
    public function counts(): array;
}
