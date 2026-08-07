<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

use App\Domain\Quran\Models\Recitation;
use App\Domain\Quran\Models\Surah;
use Illuminate\Database\Eloquent\Collection;

/**
 * Recitation Repository Interface
 * Defines contract for Quran recitation (per-surah audio) data access.
 */
interface RecitationRepositoryInterface
{
    /**
     * Get all recitations for a reciter, keyed by surah_id.
     *
     * Selects only the columns the admin profile needs. Used together with
     * getAllSurahs() to merge surahs + recitations in PHP without N+1 queries.
     *
     * @return Collection<int, Recitation>
     */
    public function getByReciterKeyedBySurah(int $reciterId): Collection;

    /**
     * Get all 114 surahs ordered by surah number (id),
     * selecting only the columns the admin profile lists.
     *
     * @return Collection<int, Surah>
     */
    public function getAllSurahs(): Collection;

    /**
     * Per-surah recitation URLs keyed by reciter id, for the public surah-level
     * audio player. Cached.
     *
     * @return array<int, string>
     */
    public function getSurahAudioUrls(int $surahNumber): array;

    /**
     * Find a single recitation by id, or null when missing.
     */
    public function find(int $id): ?Recitation;

    /**
     * Create a recitation row.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Recitation;

    /**
     * Update a recitation row.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Recitation $recitation, array $data): bool;

    /**
     * Delete a recitation row.
     */
    public function delete(Recitation $recitation): bool;
}
