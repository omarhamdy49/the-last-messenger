<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use App\Domain\Quran\DTOs\ResourceData;
use App\Domain\Quran\Enums\ResourceKind;
use App\Domain\Quran\Import\QuranFoundation\Importers\CatalogueImporter;
use App\Domain\Quran\Models\QuranResource;
use App\Domain\Quran\Repositories\ResourceRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * The resource catalogue: translations, tafsirs, chapter-info sources, verse media,
 * ayah recitations, chapter reciters and word-by-word resources.
 */
final class ResourceService
{
    public function __construct(
        private readonly ResourceRepositoryInterface $resources,
        private readonly CatalogueImporter $catalogue,
    ) {}

    /**
     * @param  array{search?: string|null, language?: string|null, status?: string|null, source?: string|null}  $filters
     */
    public function adminIndex(ResourceKind $kind, array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->resources->paginateAdmin($kind, $filters, $perPage);
    }

    /**
     * @return Collection<int, QuranResource>
     */
    public function active(ResourceKind $kind, ?string $languageIso = null): Collection
    {
        return $this->resources->active($kind, $languageIso);
    }

    public function find(int $id): ?QuranResource
    {
        return $this->resources->find($id);
    }

    public function findOfKind(ResourceKind $kind, int $id): ?QuranResource
    {
        return $this->resources->findOfKind($kind, $id);
    }

    public function findBySlug(ResourceKind $kind, string $slug): ?QuranResource
    {
        return $this->resources->findBySlug($kind, $slug);
    }

    public function create(ResourceKind $kind, ResourceData $data): QuranResource
    {
        return $this->afterWrite($this->resources->create($kind, $data));
    }

    public function update(int $id, ResourceData $data): QuranResource
    {
        return $this->afterWrite($this->resources->update($id, $data));
    }

    public function delete(int $id): bool
    {
        $deleted = $this->resources->delete($id);
        QuranCacheKeys::flushResources();
        QuranCacheKeys::flushReciters();

        return $deleted;
    }

    /**
     * @param  'is_active'|'is_default'|'sync_enabled'  $field
     */
    public function toggle(int $id, string $field): QuranResource
    {
        return $this->afterWrite($this->resources->toggle($id, $field));
    }

    /**
     * @param  list<int>  $orderedIds
     */
    public function reorder(ResourceKind $kind, array $orderedIds): void
    {
        $this->resources->reorder($kind, $orderedIds);
        QuranCacheKeys::flushResources();
    }

    /**
     * QF catalogue rows of a kind not imported yet (live call to QF).
     *
     * @return list<array{qf_id: int, name: string, author_name: ?string, language: ?string}>
     */
    public function qfCatalogue(ResourceKind $kind): array
    {
        return $this->catalogue->preview($kind);
    }

    /**
     * Make sure a QF resource exists locally (imports the kind's catalogue, enabling
     * nothing) and return it.
     */
    public function importFromQf(ResourceKind $kind, int $qfId): QuranResource
    {
        $existing = $this->resources->findByQfId($kind, $qfId);
        if ($existing !== null) {
            return $existing;
        }

        $report = $this->catalogue->run([$kind], withInfo: true, discover: false);
        $resource = $this->resources->findByQfId($kind, $qfId);

        if ($resource === null) {
            $reason = $report->failed[0]['reason'] ?? 'not in the Quran Foundation catalogue';
            throw new RuntimeException("QF {$kind->value} {$qfId}: {$reason}");
        }

        return $resource;
    }

    /**
     * @return array{total: int, by_chapter: array<int, int>}
     */
    public function coverage(QuranResource $resource): array
    {
        return $this->resources->coverage($resource);
    }

    /**
     * @return array<string, int>
     */
    public function activeCounts(): array
    {
        return $this->resources->activeCounts();
    }

    /**
     * @return Collection<int, QuranResource>
     */
    public function syncEnabled(): Collection
    {
        return $this->resources->syncEnabled();
    }

    private function afterWrite(QuranResource $resource): QuranResource
    {
        QuranCacheKeys::flushResources();
        if ($resource->kind->isReciter()) {
            QuranCacheKeys::flushReciters();
        }

        return $resource;
    }
}
