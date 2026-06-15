<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

use App\Domain\Quran\Models\TafsirResource;
use App\Domain\Quran\Models\VerseTafsir as Tafseer;
use App\Domain\Quran\Services\QuranCacheKeys;
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
     * Active tafsir editions (resources) ordered for the reader's source selector.
     * Cached — edition metadata changes rarely.
     *
     * @return Collection<int, TafsirResource>
     */
    public function getActiveResources(): Collection
    {
        return Cache::remember('quran_tafsir_resources_active', self::CACHE_TTL, fn () => TafsirResource::query()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('id')
            ->get(['id', 'slug', 'name', 'language']));
    }

    /**
     * One verse's tafsir text for a single edition, or null when absent.
     *
     * Tafsir is loaded per-verse on demand (only when a verse's accordion is
     * opened), so a verbose edition (e.g. al-Tabari) never ships a whole-surah
     * blob. The lookup hits the unique (verse_id, resource_id) index and is fast
     * enough to skip caching — which also keeps large tafsir text out of the cache.
     */
    public function getVerseText(int $verseId, int $resourceId): ?string
    {
        return Tafseer::query()
            ->where('verse_id', $verseId)
            ->where('resource_id', $resourceId)
            ->where('active', true)
            ->value('text');
    }

    /**
     * Get tafseer with filters and pagination
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginateWithFilters(int $perPage, array $filters): LengthAwarePaginator
    {
        $query = $this->model->query()->with(['verse.surah']);

        // Filter by surah - extract from verse_key or filter through verse.surah_id
        if (isset($filters['surah_id']) && $filters['surah_id']) {
            $query->whereHas('verse', function ($q) use ($filters) {
                $q->where('surah_id', $filters['surah_id']);
            });
        }

        // Filter by language
        if (isset($filters['lang']) && $filters['lang']) {
            $query->where('lang', $filters['lang']);
        }

        // Filter by status/active
        if (isset($filters['status']) && $filters['status']) {
            $query->where('active', $filters['status'] === 'active' || $filters['status'] === '1');
        }

        // Search in tafsir text
        if (isset($filters['search']) && $filters['search']) {
            $query->where('text', 'LIKE', '%'.$filters['search'].'%');
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'verse_key';
        $sortDir = $filters['sort_dir'] ?? 'asc';

        $allowedSorts = ['verse_key', 'lang', 'active', 'created_at', 'updated_at'];
        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->orderBy('verse_key', 'asc');
        }

        return $query->paginate($perPage);
    }

    /**
     * Get tafseer for a specific verse by verse key
     */
    public function getByVerseKey(string $verseKey, string $language = 'ar'): ?Model
    {
        return $this->model
            ->where('verse_key', $verseKey)
            ->where('lang', $language)
            ->active()
            ->with(['verse.surah'])
            ->first();
    }

    /**
     * Get all tafseer for a specific verse (all languages)
     *
     * @return Collection<int, Model>
     */
    public function getByVerseReference(string $verseReference): Collection
    {
        return Cache::remember(
            QuranCacheKeys::tafseerByVerseReference($verseReference),
            self::CACHE_TTL,
            fn () => $this->model
                ->where('verse_key', $verseReference)
                ->active()
                ->with(['verse.surah'])
                ->get()
        );
    }

    /**
     * Get tafseer by surah
     */
    public function getBySurah(int $surahId, int $perPage): LengthAwarePaginator
    {
        return $this->model
            ->whereHas('verse', function ($q) use ($surahId) {
                $q->where('surah_id', $surahId);
            })
            ->active()
            ->orderBy('verse_key')
            ->with(['verse.surah'])
            ->paginate($perPage);
    }

    /**
     * Get tafseer by verse and source (language)
     */
    public function getByVerseAndSource(string $verseReference, string $source): ?Model
    {
        return $this->model
            ->where('verse_key', $verseReference)
            ->where('lang', $source)
            ->active()
            ->with(['verse.surah'])
            ->first();
    }

    /**
     * Get tafseer by source (language) with pagination
     */
    public function getBySource(string $source, int $perPage): LengthAwarePaginator
    {
        return $this->model
            ->where('lang', $source)
            ->active()
            ->orderBy('verse_key')
            ->with(['verse.surah'])
            ->paginate($perPage);
    }

    /**
     * Get tafseer by language
     */
    public function getByLanguage(string $language, int $perPage): LengthAwarePaginator
    {
        return $this->model
            ->where('lang', $language)
            ->active()
            ->orderBy('verse_key')
            ->with(['verse.surah'])
            ->paginate($perPage);
    }

    /**
     * Search tafseer by query in text
     */
    public function search(string $query, int $perPage): LengthAwarePaginator
    {
        return $this->model
            ->where('text', 'LIKE', '%'.$query.'%')
            ->active()
            ->with(['verse.surah'])
            ->orderBy('verse_key')
            ->paginate($perPage);
    }

    /**
     * Check if duplicate exists (same verse + language combination)
     */
    public function checkDuplicate(string $verseKey, string $language, ?int $excludeId = null): bool
    {
        $query = $this->model
            ->where('verse_key', $verseKey)
            ->where('lang', $language);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Get available languages with tafsir content
     *
     * @return array<string>
     */
    public function getAvailableSources(): array
    {
        return Cache::remember(
            QuranCacheKeys::tafseerSources(),
            self::CACHE_TTL,
            fn () => $this->model
                ->active()
                ->distinct()
                ->pluck('lang')
                ->toArray()
        );
    }

    /**
     * Count unique surahs with tafsir content
     */
    public function countUniqueSurahs(): int
    {
        return Cache::remember(
            QuranCacheKeys::tafseerUniqueSurahs(),
            self::CACHE_TTL,
            fn () => $this->model
                ->active()
                ->distinct()
                ->join('quran_verses', 'quran_verse_tafsirs.verse_id', '=', 'quran_verses.id')
                ->count('quran_verses.surah_id')
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
            ->with(['verse.surah'])
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
        Cache::forget(QuranCacheKeys::tafseerSources());
        Cache::forget(QuranCacheKeys::tafseerUniqueSurahs());
        // Clear verse-specific caches would require knowing all verse references
        // In production, consider using cache tags
    }
}
