<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

use App\Domain\Quran\Enums\RevelationPlace;
use App\Domain\Shared\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

interface SurahRepositoryInterface extends RepositoryInterface
{
    /**
     * Get surah by number (1-114)
     *
     * @param int $number
     * @return \App\Domain\Quran\Models\Surah|null
     */
    public function getByNumber(int $number): ?\App\Domain\Quran\Models\Surah;

    /**
     * Get surahs by revelation place
     *
     * @param RevelationPlace|string $place
     * @return Collection
     */
    public function getByRevelationPlace(RevelationPlace|string $place): Collection;

    /**
     * Get surahs by Juz
     *
     * @param int $juz
     * @return Collection
     */
    public function getByJuz(int $juz): Collection;
}

