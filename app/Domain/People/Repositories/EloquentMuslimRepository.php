<?php

declare(strict_types=1);

namespace App\Domain\People\Repositories;

use App\Domain\People\DTOs\MuslimListFilters;
use App\Domain\People\Models\Muslim;
use App\Domain\Shared\Support\ArabicNormalizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;

/**
 * All database access for people. Multi-step writes are wrapped in
 * DB::transaction() HERE, never in a controller or service (constitution I).
 */
final class EloquentMuslimRepository implements MuslimRepositoryInterface
{
    /** Columns for list rendering — never `SELECT *` (constitution II). */
    private const LIST_COLUMNS = [
        'id', 'slug', 'thumb', 'name_ar', 'name_en', 'kunya',
        'gender', 'birth_year_hijri', 'death_year_hijri', 'generation', 'is_published',
    ];

    public function __construct(private readonly ArabicNormalizer $normalizer) {}

    public function paginate(MuslimListFilters $filters): LengthAwarePaginator
    {
        $query = Muslim::query()->select(self::LIST_COLUMNS);

        if ($filters->publishedOnly) {
            $query->published();
        }

        if ($filters->roleSlug !== null) {
            $roleSlug = $filters->roleSlug;
            $featured = $filters->featured;

            $query->whereHas('roles', function (Builder $q) use ($roleSlug, $featured): void {
                $q->where('islamic_roles.slug', $roleSlug);
                if ($featured !== null) {
                    $q->where('muslims_roles.is_featured', $featured);
                }
            });
        }

        if ($filters->gender !== null) {
            $query->where('gender', $filters->gender->value);
        }

        if ($filters->search !== null && $filters->search !== '') {
            $this->applyNameSearch($query, $filters->search);
        }

        if ($filters->excludeId !== null) {
            $query->whereKeyNot($filters->excludeId);
        }

        return $query
            ->with(['roles:id,slug,name_ar,name_en'])
            ->when(
                $filters->latestFirst,
                fn (Builder $q) => $q->orderByDesc('id'),
                fn (Builder $q) => $q->orderBy('id'),
            )
            ->paginate($filters->perPage)
            ->withQueryString();
    }

    public function findBySlug(string $slug, bool $publishedOnly = true): ?Muslim
    {
        $query = Muslim::query()->where('slug', $slug);

        if ($publishedOnly) {
            $query->published();
        }

        return $query
            ->with([
                'roles:id,slug,name_ar,name_en',
                'father:id,slug,name_ar,name_en',
                'mother:id,slug,name_ar,name_en',
            ])
            ->first();
    }

    public function findById(int $id): ?Muslim
    {
        return Muslim::query()->whereKey($id)->first();
    }

    public function search(string $term, int $limit = 20, bool $publishedOnly = true): Collection
    {
        $query = Muslim::query()->select(self::LIST_COLUMNS);

        if ($publishedOnly) {
            $query->published();
        }

        $this->applyNameSearch($query, $term);

        return $query->limit($limit)->get();
    }

    public function pickerSearch(string $term, ?int $excludeId = null, int $limit = 20): Collection
    {
        // publishedOnly is deliberately FALSE here: an unpublished person is
        // still a valid ancestor, narrator, or compiler. Publication governs
        // whether a profile renders, not whether a person may be referenced.
        $query = Muslim::query()->select(['id', 'slug', 'name_ar', 'name_en', 'is_published']);

        // Substring LIKE rather than FULLTEXT, deliberately, for two reasons:
        //
        // 1. InnoDB FULLTEXT is not transaction-visible — a person created and
        //    not yet committed is invisible to MATCH(). An editor who adds a
        //    father and immediately searches for him would not find him.
        // 2. Editors search by any word in a name ("عمر" inside
        //    "عبد الله بن عمر"), which an anchored LIKE would miss.
        //
        // Constitution II's ban on leading-wildcard LIKE targets hot paths.
        // This is an admin-only autocomplete, capped at $limit rows, not a
        // public read path. Public search still uses FULLTEXT.
        $normalized = $this->normalizer->normalize($term);

        if ($normalized === null || $normalized === '') {
            return new Collection;
        }

        $query->where(function (Builder $q) use ($normalized): void {
            $q->where('search_name_ar', 'like', '%'.$normalized.'%')
                ->orWhere('search_name_en', 'like', '%'.$normalized.'%');
        });

        if ($excludeId !== null) {
            $query->whereKeyNot($excludeId);
        }

        return $query->limit($limit)->get();
    }

    /**
     * Two separate MATCH branches, never MATCH(search_name_ar, search_name_en):
     * MySQL requires the column list to exactly equal an index, and these are
     * two single-column FULLTEXT indexes.
     *
     * The query is folded with the SAME normalizer used to write the columns.
     * If the two ever diverge, diacritic-insensitive matching fails in one
     * direction only, silently.
     */
    private function applyNameSearch(Builder $query, string $term): void
    {
        $normalized = $this->normalizer->normalize($term);

        if ($normalized === null || $normalized === '') {
            return;
        }

        $query->where(function (Builder $q) use ($normalized): void {
            $q->whereRaw('MATCH(search_name_ar) AGAINST (? IN BOOLEAN MODE)', [$normalized])
                ->orWhereRaw('MATCH(search_name_en) AGAINST (? IN BOOLEAN MODE)', [$normalized]);
        });
    }

    /**
     * Ancestor ids walking father_id/mother_id upward, depth-capped.
     * Used by the cycle guard before a parent is assigned.
     *
     * @return SupportCollection<int, int>
     */
    public function ancestorIds(int $muslimId, int $maxDepth): SupportCollection
    {
        $rows = DB::select(
            <<<'SQL'
            WITH RECURSIVE ancestors (id, depth) AS (
                SELECT m.id, 0 FROM muslims m WHERE m.id = ?
                UNION ALL
                SELECT parent.id, a.depth + 1
                FROM ancestors a
                JOIN muslims child ON child.id = a.id
                JOIN muslims parent ON parent.id IN (child.father_id, child.mother_id)
                WHERE a.depth < ?
            )
            SELECT DISTINCT id FROM ancestors WHERE id <> ?
            SQL,
            [$muslimId, $maxDepth, $muslimId]
        );

        return new SupportCollection(array_map(static fn (object $row): int => (int) $row->id, $rows));
    }

    /** @param array<int, array{role_id: int, is_featured?: bool, sort_order?: int}> $roles */
    public function syncRoles(Muslim $muslim, array $roles): void
    {
        $payload = [];
        foreach ($roles as $role) {
            $payload[$role['role_id']] = [
                'is_featured' => $role['is_featured'] ?? false,
                'sort_order' => $role['sort_order'] ?? 0,
            ];
        }

        $muslim->roles()->sync($payload);
    }

    public function create(array $attributes, array $roles = []): Muslim
    {
        return DB::transaction(function () use ($attributes, $roles): Muslim {
            $muslim = Muslim::create($attributes);

            if ($roles !== []) {
                $this->syncRoles($muslim, $roles);
            }

            return $muslim;
        });
    }

    public function update(Muslim $muslim, array $attributes, ?array $roles = null): Muslim
    {
        return DB::transaction(function () use ($muslim, $attributes, $roles): Muslim {
            $muslim->update($attributes);

            if ($roles !== null) {
                $this->syncRoles($muslim, $roles);
            }

            return $muslim->refresh();
        });
    }

    public function delete(Muslim $muslim): void
    {
        $muslim->delete();
    }

    public function bulkDelete(array $ids): int
    {
        if ($ids === []) {
            return 0;
        }

        return DB::transaction(fn (): int => Muslim::query()->whereIn('id', $ids)->delete());
    }

    public function bulkSetPublished(array $ids, bool $published): int
    {
        if ($ids === []) {
            return 0;
        }

        return Muslim::query()->whereIn('id', $ids)->update(['is_published' => $published]);
    }

    /**
     * Featured status lives on the muslims_roles pivot, and the admin list has no
     * role in scope — so this flips the flag for every role a selected person
     * holds, which is what the bulk action on that screen means.
     */
    public function bulkSetFeatured(array $ids, bool $featured): int
    {
        if ($ids === []) {
            return 0;
        }

        return DB::table('muslims_roles')
            ->whereIn('muslim_id', $ids)
            ->update(['is_featured' => $featured]);
    }
}
