<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Repositories;

use App\Domain\Hadith\Models\HadithNarrator;
use App\Domain\Shared\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Hadith Narrator Repository Interface
 *
 * Contract for Hadith Narrator data access operations.
 */
interface NarratorRepositoryInterface extends RepositoryInterface
{
    /**
     * Get narrator by name
     */
    public function getByName(string $name, string $locale = 'ar'): ?HadithNarrator;

    /**
     * Get all narrators with hadith count
     */
    public function getAllWithHadithCount(): Collection;

    /**
     * Search narrators
     */
    public function search(string $query, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get top narrators by hadith count
     */
    public function getTopByHadithCount(int $limit = 10): Collection;
}
