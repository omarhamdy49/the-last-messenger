<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

use App\Domain\Quran\Models\RevelationContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Revelation Context (Asbab al-Nuzul) Repository Interface
 */
interface RevelationContextRepositoryInterface
{
    /**
     * Paginate revelation contexts with filters.
     *
     * @param array{
     *     period?: string|null,
     *     surah_id?: int|null,
     *     search?: string|null,
     *     sort?: string|null,
     *     active_only?: bool
     * } $filters
     */
    public function paginateWithFilters(array $filters, int $perPage): LengthAwarePaginator;

    /**
     * Get a revelation context by ID with translations and surah loaded.
     */
    public function getById(int $id): ?RevelationContext;

    /**
     * Create a revelation context with its translations (transactional).
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string, array<string, string|null>>  $translations  field => locale => value
     */
    public function createWithTranslations(array $attributes, array $translations): RevelationContext;

    /**
     * Update a revelation context and its translations (transactional).
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string, array<string, string|null>>  $translations  field => locale => value
     */
    public function updateWithTranslations(RevelationContext $context, array $attributes, array $translations): bool;

    /**
     * Delete a revelation context (translations cascade).
     */
    public function deleteContext(RevelationContext $context): bool;

    /**
     * Bulk-fetch Arabic verse excerpts (first verse of each range) in ONE query.
     *
     * @param  array<int, array{surah_id: int, verse_number: int}>  $verseRefs
     * @return array<string, string> keyed by "surahId:verseNumber"
     */
    public function getArabicExcerptsForContexts(array $verseRefs): array;

    /**
     * Aggregate stats for the admin index (single query).
     *
     * @return array{total: int, makki: int, madani: int, active: int}
     */
    public function getStats(): array;
}
