<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

use Illuminate\Support\Collection;

interface QuranExportRepositoryInterface
{
    /**
     * Rows for one export module: quran_chapters | quran_verses | quran_translations |
     * quran_tafsirs | quran_reciters. Filters: `chapter` (verses/rows), `resource_id` (rows).
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, object>
     */
    public function rows(string $module, array $filters): Collection;
}
