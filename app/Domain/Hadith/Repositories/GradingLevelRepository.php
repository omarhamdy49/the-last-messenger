<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Repositories;

use App\Domain\Hadith\Models\HadithGradingLevel;
use App\Domain\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Hadith Grading Level Repository Implementation
 *
 * Data access layer for Hadith Grading Level entities.
 */
class GradingLevelRepository extends BaseRepository implements GradingLevelRepositoryInterface
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
        return new HadithGradingLevel();
    }

    /**
     * {@inheritdoc}
     */
    public function getByCode(string $code): ?HadithGradingLevel
    {
        return Cache::remember(
            "hadith_grading_level_code_{$code}",
            self::CACHE_TTL,
            fn () => $this->model
                ->where('code', $code)
                ->first()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getAllByRank(): Collection
    {
        return Cache::remember(
            'hadith_grading_levels_by_rank',
            self::CACHE_TTL,
            fn () => $this->model
                ->select(['id', 'grade_ar', 'grade_en', 'description', 'rank'])
                ->orderBy('rank')
                ->get()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getAllWithHadithCount(): Collection
    {
        return Cache::remember(
            'hadith_grading_levels_with_count',
            self::CACHE_TTL,
            fn () => $this->model
                ->select(['id', 'grade_ar', 'grade_en', 'description', 'rank'])
                ->withCount('hadiths')
                ->orderBy('rank')
                ->get()
        );
    }
}
