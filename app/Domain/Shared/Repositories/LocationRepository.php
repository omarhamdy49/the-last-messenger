<?php

declare(strict_types=1);

namespace App\Domain\Shared\Repositories;

use App\Domain\Shared\DTOs\LocationData;
use App\Domain\Shared\Models\Location;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Location Repository
 *
 * Constitution II: every list query carries an explicit select, and eager loads
 * name their columns. The vocabulary is bounded (~55 rows) and cached, so
 * resolving 7,000 source rows against it costs zero extra queries.
 */
class LocationRepository extends BaseRepository implements LocationRepositoryInterface
{
    /**
     * Columns every read needs. `parent_id` is included because the tree render
     * and the depth walk both need it, and omitting a foreign key from the
     * parent select is the classic way to reintroduce N+1.
     *
     * @var array<int, string>
     */
    private const LIST_COLUMNS = [
        'id', 'slug', 'type', 'parent_id', 'depth',
        'latitude', 'longitude', 'aliases', 'is_active', 'display_order',
    ];

    private const TRANSLATION_EAGER = 'translations:id,location_id,locale,name';

    protected function getModel(): Model
    {
        return new Location;
    }

    protected function getCachePrefix(): string
    {
        return 'location';
    }

    public function paginateFiltered(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        return $this->filtered($filters)
            ->with([self::TRANSLATION_EAGER, 'parent:id,slug'])
            ->orderBy('depth')
            ->orderBy('display_order')
            ->orderBy('slug')
            ->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function filtered(array $filters): Builder
    {
        $query = Location::query()->select(self::LIST_COLUMNS);

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (array_key_exists('parent_id', $filters) && $filters['parent_id'] !== null) {
            $query->where('parent_id', (int) $filters['parent_id']);
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        if (array_key_exists('has_coordinates', $filters) && $filters['has_coordinates'] !== null) {
            $filters['has_coordinates']
                ? $query->whereNotNull('latitude')->whereNotNull('longitude')
                : $query->where(fn (Builder $q) => $q->whereNull('latitude')->orWhereNull('longitude'));
        }

        if (! empty($filters['q'])) {
            $term = (string) $filters['q'];

            $query->where(function (Builder $q) use ($term): void {
                $q->whereHas('translations', fn (Builder $t) => $t->where('name', 'LIKE', "%{$term}%"))
                    ->orWhere('slug', 'LIKE', "%{$term}%")
                    ->orWhereJsonContains('aliases', $term);
            });
        }

        return $query;
    }

    public function vocabulary(): Collection
    {
        return Cache::remember(
            $this->getCacheKey('vocabulary'),
            $this->cacheTTL,
            fn (): Collection => Location::query()
                ->select(self::LIST_COLUMNS)
                ->where('is_active', true)
                ->with(self::TRANSLATION_EAGER)
                ->orderBy('id')
                ->get()
        );
    }

    public function search(string $term, int $limit = 20): Collection
    {
        $term = trim($term);

        if ($term === '') {
            return new Collection;
        }

        return Location::query()
            ->select(self::LIST_COLUMNS)
            ->where('is_active', true)
            ->where(function (Builder $q) use ($term): void {
                $q->whereHas('translations', fn (Builder $t) => $t->where('name', 'LIKE', "%{$term}%"))
                    ->orWhere('slug', 'LIKE', "%{$term}%")
                    ->orWhereJsonContains('aliases', $term);
            })
            ->with([self::TRANSLATION_EAGER, 'parent:id,slug'])
            ->orderBy('depth')
            ->orderBy('display_order')
            ->limit($limit)
            ->get();
    }

    public function tree(): Collection
    {
        return Cache::remember(
            $this->getCacheKey('tree'),
            $this->cacheTTL,
            fn (): Collection => Location::query()
                ->select(self::LIST_COLUMNS)
                ->with([self::TRANSLATION_EAGER, 'parent:id,slug'])
                ->orderBy('depth')
                ->orderBy('display_order')
                ->orderBy('slug')
                ->get()
        );
    }

    public function findBySlug(string $slug): ?Location
    {
        return Location::query()
            ->select(self::LIST_COLUMNS)
            ->where('slug', $slug)
            ->with(self::TRANSLATION_EAGER)
            ->first();
    }

    public function findByAlias(string $alias, ?int $exceptId = null): ?Location
    {
        return Location::query()
            ->select(self::LIST_COLUMNS)
            ->whereJsonContains('aliases', $alias)
            ->when($exceptId !== null, fn (Builder $q) => $q->whereKeyNot($exceptId))
            ->with(self::TRANSLATION_EAGER)
            ->first();
    }

    /**
     * Constitution I: the multi-step write lives inside the repository, not the
     * service and not the controller.
     */
    public function createWithTranslations(LocationData $data): Location
    {
        $location = DB::transaction(function () use ($data): Location {
            $location = Location::create($this->attributes($data));
            $location->syncTranslations($data->translations);

            return $location;
        });

        $this->clearCache();

        return $location->load(self::TRANSLATION_EAGER);
    }

    public function updateWithTranslations(int $id, LocationData $data): Location
    {
        $location = DB::transaction(function () use ($id, $data): Location {
            $location = Location::query()->findOrFail($id);
            $location->fill($this->attributes($data))->save();
            $location->syncTranslations($data->translations);

            return $location;
        });

        $this->clearCache();

        return $location->load(self::TRANSLATION_EAGER);
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(LocationData $data): array
    {
        return [
            'slug' => $data->slug,
            'type' => $data->type->value,
            'parent_id' => $data->parentId,
            'latitude' => $data->latitude,
            'longitude' => $data->longitude,
            'aliases' => $data->aliases,
            'is_active' => $data->isActive,
            'display_order' => $data->displayOrder,
        ];
    }

    /**
     * Bump the versioned key. Never Cache::flush() — it wipes other modules'
     * caches and the rate limiter.
     */
    public function clearCache(): void
    {
        parent::clearCache();
    }
}
