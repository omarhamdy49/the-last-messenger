<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Repositories;

use App\Domain\Hadith\Models\HadithScholar;
use App\Domain\Shared\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Hadith Scholar Repository Interface
 *
 * Contract for Hadith Scholar data access operations.
 */
interface ScholarRepositoryInterface extends RepositoryInterface
{
    /**
     * Get scholar by name
     */
    public function getByName(string $name, string $locale = 'ar'): ?HadithScholar;

    /**
     * Get all scholars ordered by era
     */
    public function getAllByEra(): Collection;

    /**
     * Search scholars
     */
    public function search(string $query, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get scholars with grading count
     */
    public function getWithGradingCount(): Collection;
}
