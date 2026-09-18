<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

use App\Domain\Quran\DTOs\ReciterData;
use App\Domain\Quran\DTOs\ResourceData;
use App\Domain\Quran\Enums\ExternalSource;
use App\Domain\Quran\Enums\ResourceKind;
use App\Domain\Quran\Enums\ResourceSource;
use App\Domain\Quran\Models\QuranResource;
use App\Domain\Quran\Services\QuranCacheKeys;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class EloquentResourceRepository implements ResourceRepositoryInterface
{
    private const COLUMNS = [
        'id', 'kind', 'parent_id', 'qf_id', 'source', 'external_source', 'external_ref_id', 'slug', 'name', 'author_name', 'language_iso',
        'style', 'qirat', 'image', 'is_active', 'is_default', 'display_order', 'sync_enabled', 'store_audio_locally',
        'records_count', 'last_synced_at', 'last_fetched_at', 'metadata',
    ];

    private const TOGGLEABLE = ['is_active', 'is_default', 'sync_enabled'];

    public function paginateAdmin(ResourceKind $kind, array $filters, int $perPage): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));

        return QuranResource::query()
            ->select(self::COLUMNS)
            ->with('translations:id,resource_id,locale,name')
            ->ofKind($kind)
            ->when($search !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->where('name', 'like', '%'.mb_substr($search, 0, 100).'%')
                ->orWhere('author_name', 'like', '%'.mb_substr($search, 0, 100).'%')
                ->orWhere('slug', $search)
                ->orWhereHas('translations', fn (Builder $t) => $t->where('name', 'like', '%'.mb_substr($search, 0, 100).'%'))))
            ->when(($filters['language'] ?? '') !== '', fn (Builder $q) => $q->where('language_iso', $filters['language']))
            ->when(($filters['status'] ?? '') === 'active', fn (Builder $q) => $q->where('is_active', true))
            ->when(($filters['status'] ?? '') === 'inactive', fn (Builder $q) => $q->where('is_active', false))
            ->when(($filters['status'] ?? '') === 'sync', fn (Builder $q) => $q->where('sync_enabled', true))
            ->when(in_array($filters['source'] ?? '', ['qf', 'manual'], true), fn (Builder $q) => $q->where('source', $filters['source']))
            ->orderByDesc('is_active')
            ->orderBy('display_order')
            ->orderBy('name')
            ->paginate(min(100, max(1, $perPage)))
            ->withQueryString();
    }

    public function active(ResourceKind $kind, ?string $languageIso = null): Collection
    {
        $all = Cache::remember(QuranCacheKeys::resources($kind, 'all'), 3600, static fn (): Collection => QuranResource::query()
            ->select(self::COLUMNS)
            ->with('translations:id,resource_id,locale,name')
            ->ofKind($kind)
            ->active()
            ->orderByDesc('is_default')
            ->orderBy('display_order')
            ->orderBy('name')
            ->get());

        return $languageIso === null ? $all : $all->where('language_iso', $languageIso)->values();
    }

    public function activeByIds(ResourceKind $kind, array $ids): Collection
    {
        $ids = array_map('intval', $ids);

        return $this->active($kind)->whereIn('id', $ids)->values();
    }

    public function find(int $id): ?QuranResource
    {
        return QuranResource::query()->with('translations:id,resource_id,locale,name,description')->find($id);
    }

    public function recordingsOf(int $parentId): Collection
    {
        return QuranResource::query()
            ->select(self::COLUMNS)
            ->with('translations:id,resource_id,locale,name')
            ->where('parent_id', $parentId)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();
    }

    public function topLevel(ResourceKind $kind, int $exceptId): Collection
    {
        return QuranResource::query()
            ->select(['id', 'kind', 'parent_id', 'name', 'slug', 'style', 'qirat', 'is_active'])
            ->with('translations:id,resource_id,locale,name')
            ->ofKind($kind)
            ->whereNull('parent_id')
            ->whereKeyNot($exceptId)
            ->orderBy('name')
            ->get();
    }

    public function setParent(int $childId, ?int $parentId): QuranResource
    {
        return DB::transaction(function () use ($childId, $parentId): QuranResource {
            $child = QuranResource::query()->lockForUpdate()->findOrFail($childId);

            if ($parentId !== null) {
                // One level only: whatever was filed under the child moves up with it.
                QuranResource::query()->where('parent_id', $child->id)->update(['parent_id' => $parentId]);
            }

            $child->forceFill(['parent_id' => $parentId])->save();

            return $child;
        });
    }

    public function linkedTo(ExternalSource $source): Collection
    {
        return QuranResource::query()
            ->select(['id', 'kind', 'name', 'slug', 'external_source', 'external_ref_id', 'records_count', 'is_active'])
            ->where('external_source', $source->value)
            ->whereNotNull('external_ref_id')
            ->get()
            ->keyBy(static fn (QuranResource $r): string => (string) $r->external_ref_id);
    }

    public function linkSource(int $id, ExternalSource $source, string $refId): QuranResource
    {
        $resource = QuranResource::query()->findOrFail($id);
        $resource->forceFill(['external_source' => $source->value, 'external_ref_id' => $refId])->save();

        return $resource;
    }

    public function findOfKind(ResourceKind $kind, int $id): ?QuranResource
    {
        return QuranResource::query()->with('translations:id,resource_id,locale,name,description')->ofKind($kind)->find($id);
    }

    public function findBySlug(ResourceKind $kind, string $slug): ?QuranResource
    {
        return QuranResource::query()->select(self::COLUMNS)->with('translations:id,resource_id,locale,name')
            ->ofKind($kind)->where('slug', $slug)->first();
    }

    public function findByQfId(ResourceKind $kind, int $qfId): ?QuranResource
    {
        return QuranResource::query()->select(self::COLUMNS)->ofKind($kind)->where('qf_id', $qfId)->first();
    }

    public function create(ResourceKind $kind, ResourceData $data): QuranResource
    {
        return DB::transaction(function () use ($kind, $data): QuranResource {
            $resource = new QuranResource(['kind' => $kind, 'source' => ResourceSource::MANUAL]);
            $this->fill($resource, $data);
            $resource->save();
            $this->syncNames($resource, $data);
            $this->enforceSingleDefault($resource);

            return $resource;
        });
    }

    public function update(int $id, ResourceData $data): QuranResource
    {
        return DB::transaction(function () use ($id, $data): QuranResource {
            $resource = QuranResource::query()->findOrFail($id);
            $this->fill($resource, $data);
            $resource->save();
            $this->syncNames($resource, $data);
            $this->enforceSingleDefault($resource);

            return $resource;
        });
    }

    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id): bool {
            $resource = QuranResource::query()->findOrFail($id);

            if ($resource->isFromQf()) {
                $metadata = $resource->metadata ?? [];
                $metadata['hidden_by_admin'] = true;
                $resource->forceFill(['is_active' => false, 'is_default' => false, 'sync_enabled' => false, 'metadata' => $metadata])->save();

                return false;
            }

            // FK cascades remove its translations, tafsirs, audio and glosses.
            $resource->delete();

            return true;
        });
    }

    public function toggle(int $id, string $field): QuranResource
    {
        if (! in_array($field, self::TOGGLEABLE, true)) {
            throw new InvalidArgumentException("{$field} is not toggleable");
        }

        return DB::transaction(function () use ($id, $field): QuranResource {
            $resource = QuranResource::query()->findOrFail($id);
            $value = ! $resource->getAttribute($field);

            $attributes = [$field => $value];
            if ($field === 'is_default' && $value) {
                $attributes['is_active'] = true;
            }
            if ($field === 'is_active' && ! $value) {
                $attributes['is_default'] = false;
            }
            if ($field === 'sync_enabled' && $value && $resource->qf_id === null) {
                throw new InvalidArgumentException('Only Quran Foundation resources can be synced.');
            }

            $resource->forceFill($attributes)->save();
            $this->enforceSingleDefault($resource);

            return $resource;
        });
    }

    public function reorder(ResourceKind $kind, array $orderedIds): void
    {
        DB::transaction(function () use ($kind, $orderedIds): void {
            foreach (array_values($orderedIds) as $position => $id) {
                DB::table('quran_resources')->where('kind', $kind->value)->where('id', (int) $id)->update(['display_order' => $position]);
            }
        });
    }

    public function importedQfIds(ResourceKind $kind): array
    {
        return DB::table('quran_resources')->where('kind', $kind->value)->whereNotNull('qf_id')->pluck('qf_id')
            ->map(static fn (mixed $v): int => (int) $v)->all();
    }

    public function coverage(QuranResource $resource): array
    {
        [$table, $join] = match ($resource->kind) {
            ResourceKind::TRANSLATION => ['quran_translations', 'verse'],
            ResourceKind::TAFSIR => ['quran_tafsirs', 'verse'],
            ResourceKind::RECITATION => ['quran_audio_files', 'verse'],
            ResourceKind::CHAPTER_INFO => ['quran_chapter_infos', 'chapter'],
            ResourceKind::CHAPTER_RECITER => ['quran_chapter_audio_files', 'chapter'],
            default => [null, null],
        };

        if ($table === null) {
            return ['total' => (int) DB::table('quran_word_glosses')->where('resource_id', $resource->id)->count(), 'by_chapter' => []];
        }

        $query = $join === 'verse'
            ? DB::table($table.' as t')->join('quran_verses as v', 'v.id', '=', 't.verse_id')->selectRaw('v.chapter_id as chapter, COUNT(*) as n')->groupBy('v.chapter_id')
            : DB::table($table.' as t')->selectRaw('t.chapter_id as chapter, COUNT(*) as n')->groupBy('t.chapter_id');

        $byChapter = $query->where('t.resource_id', $resource->id)->pluck('n', 'chapter')
            ->map(static fn (mixed $v): int => (int) $v)->all();

        return ['total' => array_sum($byChapter), 'by_chapter' => $byChapter];
    }

    public function applyFetchedData(int $id, ReciterData $data): QuranResource
    {
        return DB::transaction(function () use ($id, $data): QuranResource {
            $resource = QuranResource::query()->findOrFail($id);
            $resource->fill(array_filter([
                'name' => mb_substr($data->name, 0, 200),
                'style' => $data->style,
                'qirat' => $data->qirat === null ? null : mb_substr($data->qirat, 0, 40),
                'info' => $data->info,
                'image' => $data->image,
            ], static fn (mixed $v): bool => $v !== null))->save();

            if ($data->names !== []) {
                $resource->syncTranslations(['name' => array_map(static fn (string $n): string => mb_substr($n, 0, 200), $data->names)]);
            }

            return $resource;
        });
    }

    public function recordFetch(int $id, array $report): void
    {
        QuranResource::query()->whereKey($id)->update([
            'last_fetched_at' => now(),
            'last_fetch_report' => json_encode($report, JSON_UNESCAPED_UNICODE),
        ]);
    }

    public function syncEnabled(): Collection
    {
        return QuranResource::query()
            ->select(self::COLUMNS)
            ->syncEnabled()
            ->orderByRaw('last_synced_at IS NOT NULL')
            ->orderBy('last_synced_at')
            ->get();
    }

    public function activeCounts(): array
    {
        return DB::table('quran_resources')->where('is_active', true)->selectRaw('kind, COUNT(*) as n')->groupBy('kind')
            ->pluck('n', 'kind')->map(static fn (mixed $v): int => (int) $v)->all();
    }

    private function fill(QuranResource $resource, ResourceData $data): void
    {
        $resource->fill([
            'slug' => $data->slug,
            'name' => $data->name,
            'author_name' => $data->authorName,
            'language_iso' => $data->languageIso,
            'info' => $data->info,
            'is_active' => $data->isActive || $data->isDefault,
            'is_default' => $data->isDefault,
            'display_order' => $data->displayOrder,
            // Only QF resources can be synced.
            'sync_enabled' => $data->syncEnabled && $resource->qf_id !== null,
        ]);

        if ($resource->kind->isReciter()) {
            $resource->fill([
                'style' => $data->style,
                'qirat' => $data->qirat,
                'external_source' => $data->externalSource,
                'external_ref_id' => $data->externalSource !== null ? $data->externalRefId : null,
                'store_audio_locally' => $data->storeAudioLocally,
            ]);
        }

        if ($data->removeImage) {
            $resource->image = null;
        } elseif ($data->imagePath !== null) {
            $resource->image = $data->imagePath;
        }
    }

    private function syncNames(QuranResource $resource, ResourceData $data): void
    {
        $names = array_filter($data->nameTranslations, static fn (?string $v): bool => $v !== null && trim($v) !== '');
        $descriptions = array_filter($data->descriptionTranslations, static fn (?string $v): bool => $v !== null && trim($v) !== '');

        foreach (array_keys($descriptions) as $locale) {
            // A description needs a name row to live on.
            $names[$locale] ??= $data->name;
        }

        if ($names !== []) {
            $resource->syncTranslations(['name' => $names, 'description' => $descriptions]);
        }
    }

    /**
     * At most one default per kind and language.
     */
    private function enforceSingleDefault(QuranResource $resource): void
    {
        if (! $resource->is_default) {
            return;
        }

        QuranResource::query()
            ->ofKind($resource->kind)
            ->whereKeyNot($resource->id)
            ->where(fn (Builder $q) => $resource->language_iso === null ? $q->whereNull('language_iso') : $q->where('language_iso', $resource->language_iso))
            ->update(['is_default' => false]);
    }
}
