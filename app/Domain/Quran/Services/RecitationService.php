<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use App\Domain\Quran\Models\Recitation;
use App\Domain\Quran\Repositories\RecitationRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

/**
 * Recitation Service
 * Business logic for managing a reciter's per-surah audio recordings.
 */
class RecitationService
{
    /**
     * Constructor
     */
    public function __construct(
        protected RecitationRepositoryInterface $recitationRepository
    ) {}

    /**
     * Build the full list of 114 surahs merged with the reciter's recitation
     * (when one exists) for the admin profile.
     *
     * Loads all surahs once and all the reciter's recitations once, then merges
     * in PHP — no per-surah query.
     *
     * @return Collection<int, array{
     *     surah_id: int,
     *     number: int,
     *     name_ar: string,
     *     name_en: string,
     *     transliteration: string|null,
     *     verses_count: int,
     *     recitation: Recitation|null
     * }>
     */
    public function getReciterSurahs(int $reciterId): Collection
    {
        $surahs = $this->recitationRepository->getAllSurahs();
        $recitations = $this->recitationRepository->getByReciterKeyedBySurah($reciterId);

        return $surahs->map(fn ($surah) => [
            'surah_id' => $surah->id,
            'number' => $surah->revelation_order,
            'name_ar' => $surah->name_ar,
            'name_en' => $surah->name_en,
            'transliteration' => $surah->transliteration,
            'verses_count' => $surah->verses_count,
            'recitation' => $recitations->get($surah->id),
        ])->values();
    }

    /**
     * Number of surahs that already have a recitation for this reciter.
     */
    public function countWithAudio(int $reciterId): int
    {
        return $this->recitationRepository->getByReciterKeyedBySurah($reciterId)->count();
    }

    /**
     * Create a new recitation for a reciter.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Recitation
    {
        return $this->recitationRepository->create($data);
    }

    /**
     * Update an existing recitation, asserting it belongs to the given reciter
     * (object-level ownership check — prevents IDOR across reciters).
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ModelNotFoundException
     */
    public function update(int $reciterId, int $recitationId, array $data): Recitation
    {
        $recitation = $this->findForReciterOrFail($reciterId, $recitationId);

        $this->recitationRepository->update($recitation, $data);

        return $recitation->refresh();
    }

    /**
     * Delete a recitation, asserting it belongs to the given reciter.
     *
     * @throws ModelNotFoundException
     */
    public function delete(int $reciterId, int $recitationId): bool
    {
        $recitation = $this->findForReciterOrFail($reciterId, $recitationId);

        return $this->recitationRepository->delete($recitation);
    }

    /**
     * Resolve a recitation that must belong to the given reciter, or throw.
     *
     * Exposed so the controller can capture old values for the audit log
     * before mutating.
     *
     * @throws ModelNotFoundException
     */
    public function findForReciter(int $reciterId, int $recitationId): Recitation
    {
        return $this->findForReciterOrFail($reciterId, $recitationId);
    }

    /**
     * Resolve a recitation that must belong to the given reciter.
     *
     * @throws ModelNotFoundException
     */
    private function findForReciterOrFail(int $reciterId, int $recitationId): Recitation
    {
        $recitation = $this->recitationRepository->find($recitationId);

        if ($recitation === null || $recitation->reciter_id !== $reciterId) {
            throw (new ModelNotFoundException)->setModel(Recitation::class, $recitationId);
        }

        return $recitation;
    }
}
