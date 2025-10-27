<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Repositories;

use App\Domain\Hadith\Enums\HadithGrade;
use App\Domain\Shared\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Hadith Repository Interface
 * Defines specific operations for Hadith repository
 */
interface HadithRepositoryInterface extends RepositoryInterface
{
    /**
     * Get hadith by book
     *
     * @param int $bookId
     * @param int $perPage
     * @param array<string> $relations
     * @return LengthAwarePaginator
     */
    public function getByBook(int $bookId, int $perPage = 15, array $relations = []): LengthAwarePaginator;

    /**
     * Get hadith by chapter
     *
     * @param int $chapterId
     * @param int $perPage
     * @param array<string> $relations
     * @return LengthAwarePaginator
     */
    public function getByChapter(int $chapterId, int $perPage = 15, array $relations = []): LengthAwarePaginator;

    /**
     * Get hadith by grade
     *
     * @param HadithGrade $grade
     * @param int $perPage
     * @param array<string> $relations
     * @return LengthAwarePaginator
     */
    public function getByGrade(HadithGrade $grade, int $perPage = 15, array $relations = []): LengthAwarePaginator;

    /**
     * Get authentic hadith (sahih)
     *
     * @param int $perPage
     * @param array<string> $relations
     * @return LengthAwarePaginator
     */
    public function getSahih(int $perPage = 15, array $relations = []): LengthAwarePaginator;

    /**
     * Get reliable hadith (sahih or hasan)
     *
     * @param int $perPage
     * @param array<string> $relations
     * @return LengthAwarePaginator
     */
    public function getReliable(int $perPage = 15, array $relations = []): LengthAwarePaginator;

    /**
     * Search hadith
     *
     * @param string $query
     * @param int $perPage
     * @param array<string> $relations
     * @return LengthAwarePaginator
     */
    public function search(string $query, int $perPage = 15, array $relations = []): LengthAwarePaginator;

    /**
     * Find hadith by book and number
     *
     * @param int $bookId
     * @param int $hadithNumber
     * @param array<string> $relations
     * @return \App\Domain\Hadith\Models\Hadith|null
     */
    public function findByBookAndNumber(int $bookId, int $hadithNumber, array $relations = []): ?\App\Domain\Hadith\Models\Hadith;

    /**
     * Increment hadith views
     *
     * @param int $id
     * @return bool
     */
    public function incrementViews(int $id): bool;

    /**
     * Get hadith with narrator chain
     *
     * @param int $id
     * @return \App\Domain\Hadith\Models\Hadith|null
     */
    public function getWithNarrators(int $id): ?\App\Domain\Hadith\Models\Hadith;
}

