<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

use App\Domain\Quran\Models\VerseTafsir as Tafseer;
use App\Domain\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Tafseer Repository
 */
class TafseerRepository extends BaseRepository implements TafseerRepositoryInterface
{
    /**
     * Cache TTL in seconds (30 minutes)
     */
    private const CACHE_TTL = 1800;

    /**
     * Get model instance
     */
    protected function getModel(): Model
    {
        return new Tafseer;
    }

    /**
     * Get tafseer with filters and pagination
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginateWithFilters(int $perPage, array $filters): LengthAwarePaginator
    {
        $query = $this->model->query()->with(['surah']);

        // Filter by surah
        if (isset($filters['surah_id']) && $filters['surah_id']) {
            $query->where('surah_id', $filters['surah_id']);
        }

        // Filter by source
        if (isset($filters['source']) && $filters['source']) {
            $query->where('source', $filters['source']);
        }

        // Filter by status
        if (isset($filters['status']) && $filters['status']) {
            $query->where('status', $filters['status']);
        }

        // Search
        if (isset($filters['search']) && $filters['search']) {
            $query->search($filters['search']);
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'verse_reference';
        $sortDir = $filters['sort_dir'] ?? 'asc';
        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($perPage);
    }

    /**
     * Get tafseer by verse reference and source
     */
    public function getByVerseAndSource(string $verseReference, string $source): ?Model
    {
        return $this->model
            ->where('verse_reference', $verseReference)
            ->where('source', $source)
            ->first();
    }

    /**
     * Get all tafseer for a specific verse
     *
     * @return Collection<int, Model>
     */
    public function getByVerseReference(string $verseReference): Collection
    {
        return Cache::remember(
            "tafseer_verse_{$verseReference}",
            self::CACHE_TTL,
            fn () => $this->model
                ->where('verse_reference', $verseReference)
                ->published()
                ->with(['surah'])
                ->get()
        );
    }

    /**
     * Get tafseer by surah
     */
    public function getBySurah(int $surahId, int $perPage): LengthAwarePaginator
    {
        return $this->model
            ->where('surah_id', $surahId)
            ->published()
            ->orderBy('ayah_number')
            ->with(['surah'])
            ->paginate($perPage);
    }

    /**
     * Get tafseer by source
     */
    public function getBySource(string $source, int $perPage): LengthAwarePaginator
    {
        return $this->model
            ->where('source', $source)
            ->published()
            ->orderBy('verse_reference')
            ->with(['surah'])
            ->paginate($perPage);
    }

    /**
     * Search tafseer by query
     */
    public function search(string $query, int $perPage): LengthAwarePaginator
    {
        return $this->model
            ->search($query)
            ->with(['surah'])
            ->paginate($perPage);
    }

    /**
     * Check if duplicate exists (same verse + source)
     */
    public function checkDuplicate(string $verseReference, string $source, ?int $excludeId = null): bool
    {
        $query = $this->model
            ->where('verse_reference', $verseReference)
            ->where('source', $source);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Get available sources
     *
     * @return array<string>
     */
    public function getAvailableSources(): array
    {
        return Cache::remember(
            'tafseer_sources',
            self::CACHE_TTL,
            fn () => $this->model
                ->distinct()
                ->pluck('source')
                ->toArray()
        );
    }

    /**
     * Count unique surahs covered
     */
    public function countUniqueSurahs(): int
    {
        return Cache::remember(
            'tafseer_unique_surahs',
            self::CACHE_TTL,
            fn () => $this->model
                ->distinct()
                ->whereNotNull('surah_id')
                ->count('surah_id')
        );
    }

    /**
     * Bulk delete by IDs
     *
     * @param  array<int>  $ids
     */
    public function bulkDelete(array $ids): int
    {
        $deleted = $this->model->whereIn('id', $ids)->delete();
        $this->clearCache();

        return $deleted;
    }

    /**
     * Get tafseer with verse and surah details
     */
    public function getWithDetails(int $id): ?Model
    {
        return $this->model
            ->with(['surah'])
            ->find($id);
    }

    /**
     * Create tafseer
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Model
    {
        $tafseer = $this->model->create($data);
        $this->clearCache();

        return $tafseer;
    }

    /**
     * Update tafseer
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): bool
    {
        $updated = $this->model->where('id', $id)->update($data);
        if ($updated) {
            $this->clearCache();
        }

        return $updated;
    }

    /**
     * Delete tafseer
     */
    public function delete(int $id): bool
    {
        $deleted = $this->model->where('id', $id)->delete();
        if ($deleted) {
            $this->clearCache();
        }

        return $deleted;
    }

    /**
     * Clear all tafseer caches
     */
    public function clearCache(): void
    {
        Cache::forget('tafseer_sources');
        Cache::forget('tafseer_unique_surahs');
        // Clear verse-specific caches would require knowing all verse references
        // In production, consider using cache tags
    }
}
