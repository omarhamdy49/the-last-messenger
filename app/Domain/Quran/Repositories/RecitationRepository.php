<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

use App\Domain\Quran\Models\Recitation;
use App\Domain\Quran\Models\Surah;
use App\Domain\Quran\Services\QuranCacheKeys;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Recitation Repository
 * Handles data access for per-surah reciter audio recordings.
 */
class RecitationRepository implements RecitationRepositoryInterface
{
    /**
     * Get all recitations for a reciter, keyed by surah_id.
     *
     * One query, explicit column selection. The caller merges this against
     * getAllSurahs() in PHP so the 114-row surah list never triggers a
     * per-surah query.
     *
     * @return Collection<int, Recitation>
     */
    public function getByReciterKeyedBySurah(int $reciterId): Collection
    {
        return Recitation::query()
            ->select([
                'id',
                'reciter_id',
                'surah_id',
                'audio_url',
                'format',
                'quality',
                'duration_seconds',
                'file_size_kb',
            ])
            ->where('reciter_id', $reciterId)
            ->get()
            ->keyBy('surah_id');
    }

    /**
     * Get all 114 surahs in mushaf order (id), selecting only the
     * columns the admin profile lists.
     *
     * @return Collection<int, Surah>
     */
    public function getAllSurahs(): Collection
    {
        return Surah::query()
            ->select([
                'id',
                'name_ar',
                'name_en',
                'transliteration',
                'revelation_order',
                'verses_count',
            ])
            ->orderBy('id')
            ->get();
    }

    /**
     * Per-surah recitation URLs keyed by reciter id, for the surah-level audio
     * player. Cached for a day — recitations change rarely.
     *
     * @return array<int, string>
     */
    public function getSurahAudioUrls(int $surahNumber): array
    {
        return Cache::remember(QuranCacheKeys::publicSurahAudioUrls($surahNumber), 86400, fn (): array => Recitation::query()
            ->where('surah_id', $surahNumber)
            ->select(['reciter_id', 'audio_url'])
            ->get()
            ->pluck('audio_url', 'reciter_id')
            ->all());
    }

    /**
     * Find a single recitation by id, or null when missing.
     */
    public function find(int $id): ?Recitation
    {
        return Recitation::query()
            ->select([
                'id',
                'reciter_id',
                'surah_id',
                'audio_url',
                'format',
                'quality',
                'duration_seconds',
                'file_size_kb',
            ])
            ->find($id);
    }

    /**
     * Create a recitation row.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Recitation
    {
        return Recitation::create($data);
    }

    /**
     * Update a recitation row.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Recitation $recitation, array $data): bool
    {
        return $recitation->update($data);
    }

    /**
     * Delete a recitation row.
     */
    public function delete(Recitation $recitation): bool
    {
        return (bool) $recitation->delete();
    }
}
