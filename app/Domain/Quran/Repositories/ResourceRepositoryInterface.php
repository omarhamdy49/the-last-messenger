<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

use App\Domain\Quran\DTOs\ReciterData;
use App\Domain\Quran\DTOs\ResourceData;
use App\Domain\Quran\Enums\ResourceKind;
use App\Domain\Quran\Models\QuranResource;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ResourceRepositoryInterface
{
    /**
     * @param  array{search?: string|null, language?: string|null, status?: string|null, source?: string|null}  $filters
     */
    public function paginateAdmin(ResourceKind $kind, array $filters, int $perPage): LengthAwarePaginator;

    /**
     * Active resources of a kind for readers and the API, ordered for pickers.
     *
     * @return Collection<int, QuranResource>
     */
    public function active(ResourceKind $kind, ?string $languageIso = null): Collection;

    /**
     * @param  list<int>  $ids
     * @return Collection<int, QuranResource>
     */
    public function activeByIds(ResourceKind $kind, array $ids): Collection;

    public function find(int $id): ?QuranResource;

    public function findOfKind(ResourceKind $kind, int $id): ?QuranResource;

    public function findBySlug(ResourceKind $kind, string $slug): ?QuranResource;

    public function findByQfId(ResourceKind $kind, int $qfId): ?QuranResource;

    public function create(ResourceKind $kind, ResourceData $data): QuranResource;

    public function update(int $id, ResourceData $data): QuranResource;

    /**
     * Manual rows are deleted with their content; QF rows are hidden (a later
     * catalogue import would otherwise bring them back).
     *
     * @return bool true when deleted, false when only hidden
     */
    public function delete(int $id): bool;

    /**
     * @param  'is_active'|'is_default'|'sync_enabled'  $field
     */
    public function toggle(int $id, string $field): QuranResource;

    /**
     * @param  list<int>  $orderedIds
     */
    public function reorder(ResourceKind $kind, array $orderedIds): void;

    /**
     * @return list<int>
     */
    public function importedQfIds(ResourceKind $kind): array;

    /**
     * Rows per chapter for a content resource (translation, tafsir, recitation,
     * chapter_info, chapter_reciter).
     *
     * @return array{total: int, by_chapter: array<int, int>}
     */
    public function coverage(QuranResource $resource): array;

    /**
     * @return array<string, int> kind value => active count
     */
    public function activeCounts(): array;

    /**
     * Write what a source returned for a reciter: name, style, qirat, info (when given)
     * and the localized names it has. Locales the source lacks keep their current name.
     */
    public function applyFetchedData(int $id, ReciterData $data): QuranResource;

    /**
     * @param  array<string, mixed>  $report
     */
    public function recordFetch(int $id, array $report): void;

    /**
     * Resources with sync enabled, oldest sync first (the sync dashboard's freshness list).
     *
     * @return Collection<int, QuranResource>
     */
    public function syncEnabled(): Collection;
}
