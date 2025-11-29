<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Repositories;

use App\Domain\Hadith\Models\HadithNarrator;
use App\Domain\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

/**
 * Hadith Narrator Repository Implementation
 *
 * Data access layer for Hadith Narrator entities.
 */
class NarratorRepository extends BaseRepository implements NarratorRepositoryInterface
{
    /**
     * Cache TTL in seconds (1 hour)
     */
    private const CACHE_TTL = 3600;

    /**
     * Get model instance
     */
    protected function getModel(): Model
    {
        return new HadithNarrator();
    }

    /**
     * {@inheritdoc}
     */
    public function getByName(string $name, string $locale = 'ar'): ?HadithNarrator
    {
        $nameField = 'name_' . $locale;

        return Cache::remember(
            "hadith_narrator_name_{$locale}_{$name}",
            self::CACHE_TTL,
            fn () => $this->model
                ->where($nameField, $name)
                ->first()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getAllWithHadithCount(): Collection
    {
        return Cache::remember(
            'hadith_narrators_with_count',
            self::CACHE_TTL,
            fn () => $this->model
                ->withCount('hadiths')
                ->orderBy('hadiths_count', 'desc')
                ->get()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function search(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where('name_ar', 'LIKE', "%{$query}%")
            ->orWhere('name_en', 'LIKE', "%{$query}%")
            ->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function getTopByHadithCount(int $limit = 10): Collection
    {
        return Cache::remember(
            "hadith_narrators_top_{$limit}",
            self::CACHE_TTL,
            fn () => $this->model
                ->withCount('hadiths')
                ->orderBy('hadiths_count', 'desc')
                ->limit($limit)
                ->get()
        );
    }
}
