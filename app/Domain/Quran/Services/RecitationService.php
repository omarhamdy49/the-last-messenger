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
    public function getReciterSurahs(int $reciterId, ?int $sourceId = null): Collection
    {
        $surahs = $this->recitationRepository->getAllSurahs();
        $recitations = $this->recitationRepository->getByReciterKeyedBySurah($reciterId, $sourceId);

        return $surahs->map(fn ($surah) => [
            'surah_id' => $surah->id,
            // quran_surahs.id is the canonical mushaf number; revelation_order is chronology.
            'number' => $surah->id,
            'name_ar' => $surah->name_ar,
            'name_en' => $surah->name_en,
            'transliteration' => $surah->transliteration,
            'verses_count' => $surah->verses_count,
            'recitation' => $recitations->get($surah->id),
        ])->values();
    }

    /**
     * Filter + page the 114-row surah list for the admin recording page.
     *
     * ponytail: filters in memory over the fixed 114 rows (2 queries); move to SQL if
     * the list ever stops being bounded.
     *
     * @param  'all'|'with_audio'|'missing'  $status
     * @return array{rows: Collection, total: int, with_audio: int, missing: int, next_page: int|null}
     */
    public function paginateReciterSurahs(int $reciterId, ?int $sourceId, ?string $term, string $status, int $page, int $perPage): array
    {
        $rows = $this->getReciterSurahs($reciterId, $sourceId);
        $term = trim((string) $term);

        if ($term !== '') {
            $needle = mb_strtolower($term);
            $rows = $rows->filter(fn (array $row) => ctype_digit($term)
                ? $row['number'] === (int) $term
                : str_contains(mb_strtolower($row['name_ar'].' '.$row['name_en'].' '.$row['transliteration']), $needle));
        }

        $withAudio = $rows->whereNotNull('recitation')->count();
        $missing = $rows->count() - $withAudio;

        $rows = match ($status) {
            'with_audio' => $rows->whereNotNull('recitation'),
            'missing' => $rows->whereNull('recitation'),
            default => $rows,
        };

        $total = $rows->count();

        return [
            'rows' => $rows->forPage($page, $perPage)->values(),
            'total' => $total,
            'with_audio' => $withAudio,
            'missing' => $missing,
            'next_page' => $page * $perPage < $total ? $page + 1 : null,
        ];
    }

    /**
     * Coverage figures for the recording hero: surahs with audio and their total length.
     *
     * @return array{surahs: int, with_audio: int, duration_seconds: int}
     */
    public function coverageStats(int $reciterId): array
    {
        $rows = $this->getReciterSurahs($reciterId);
        $recitations = $rows->pluck('recitation')->filter();

        return [
            'surahs' => $rows->count(),
            'with_audio' => $recitations->count(),
            'duration_seconds' => (int) $recitations->sum('duration_seconds'),
        ];
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
