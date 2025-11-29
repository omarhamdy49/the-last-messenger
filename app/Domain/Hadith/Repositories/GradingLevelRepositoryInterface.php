<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Repositories;

use App\Domain\Hadith\Models\HadithGradingLevel;
use App\Domain\Shared\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Hadith Grading Level Repository Interface
 *
 * Contract for Hadith Grading Level data access operations.
 */
interface GradingLevelRepositoryInterface extends RepositoryInterface
{
    /**
     * Get grading level by code
     */
    public function getByCode(string $code): ?HadithGradingLevel;

    /**
     * Get all grading levels ordered by rank
     */
    public function getAllByRank(): Collection;

    /**
     * Get grading level with hadith count
     */
    public function getAllWithHadithCount(): Collection;
}
