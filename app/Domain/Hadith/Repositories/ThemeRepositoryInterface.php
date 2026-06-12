<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Repositories;

use App\Domain\Hadith\Models\HadithTheme;
use App\Domain\Shared\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Hadith Theme Repository Interface
 *
 * Contract for Hadith Theme data access operations.
 */
interface ThemeRepositoryInterface extends RepositoryInterface
{
    /**
     * Get theme by slug
     */
    public function getBySlug(string $slug, string $locale = 'ar'): ?HadithTheme;

    /**
     * Get all themes with hadith count
     */
    public function getAllWithHadithCount(): Collection;

    /**
     * Get theme with hadiths
     */
    public function getWithHadiths(int $id): ?HadithTheme;

    /**
     * Search themes
     */
    public function search(string $query, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get top themes by hadith count
     */
    public function getTopByHadithCount(int $limit = 10): Collection;

    /**
     * Get all children IDs for a theme (recursive)
     *
     * @return array<int>
     */
    public function getChildrenIds(int $themeId): array;

    /**
     * Get paginated themes with filters
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginateWithFilters(
        int $perPage = 15,
        string $search = '',
        string $sortBy = 'sort_order',
        array $filters = []
    ): LengthAwarePaginator;
}
