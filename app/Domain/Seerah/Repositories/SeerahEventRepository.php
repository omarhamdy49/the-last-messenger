<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Repositories;

use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Shared\Helpers\FullTextSearchHelper;
use App\Domain\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SeerahEventRepository extends BaseRepository implements SeerahEventRepositoryInterface
{
    /**
     * Cache TTL in seconds (30 minutes)
     */
    private const CACHE_TTL = 1800;

    /**
     * {@inheritdoc}
     */
    protected function getModel(): Model
    {
        return new SeerahEvent;
    }

    /**
     * Get events by type (type id or slug). Public read — published only.
     */
    public function getByType(int|string $type, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->with($this->listRelations())
            ->published()
            ->byType($type)
            ->chronological()
            ->paginate($perPage);
    }

    /**
     * Get events by Hijri year. Public read — published only.
     */
    public function getByHijriYear(int $year, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->with($this->listRelations())
            ->published()
            ->byHijriYear($year)
            ->chronological()
            ->paginate($perPage);
    }

    /**
     * Get events by Gregorian year. Public read — published only.
     */
    public function getByGregorianYear(int $year, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->with($this->listRelations())
            ->published()
            ->byGregorianYear($year)
            ->chronological()
            ->paginate($perPage);
    }

    /**
     * Get featured events. Public read — published only.
     */
    public function getFeatured(int $limit = 10): Collection
    {
        return Cache::remember(
            "seerah_featured_{$limit}",
            self::CACHE_TTL,
            fn () => $this->model
                ->with($this->listRelations())
                ->published()
                ->featured()
                ->chronological()
                ->limit($limit)
                ->get()
        );
    }

    /**
     * Get events with location (for map). Public read — published only.
     */
    public function getEventsWithLocation(): Collection
    {
        return Cache::remember(
            'seerah_events_with_location',
            self::CACHE_TTL,
            fn () => $this->model
                ->with([
                    'translations:id,seerah_event_id,locale,title',
                    'type:id,slug,icon,color,gradient',
                    'type.translations:id,seerah_event_type_id,locale,name',
                ])
                ->published()
                ->withLocation()
                ->chronological()
                ->get(['id', 'slug', 'location', 'latitude', 'longitude', 'seerah_event_type_id', 'hijri_year'])
        );
    }

    /**
     * Search events via translation table. Public read — published only.
     * FULLTEXT index covers (title, description) on seerah_event_translations.
     * 'location' on seerah_events has no FULLTEXT index and keeps LIKE.
     */
    public function search(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->with($this->listRelations())
            ->published()
            ->where(function ($q) use ($query) {
                $q->whereHas('translations', function ($tq) use ($query) {
                    FullTextSearchHelper::apply($tq, ['title', 'description'], $query);
                })
                    ->orWhere('location', 'LIKE', "%{$query}%");
            })
            ->chronological()
            ->paginate($perPage);
    }

    /**
     * Get events in chronological order. Public read — published only.
     */
    public function getChronological(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->with($this->listRelations())
            ->published()
            ->chronological()
            ->paginate($perPage);
    }

    /**
     * Paginate events for the admin index with whitelisted filters.
     * Admin path — NOT restricted to published so editors see drafts too.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model
            ->with($this->listRelations())
            ->select([
                'id', 'slug', 'seerah_event_type_id', 'importance', 'status',
                'hijri_year', 'location', 'is_featured', 'display_order',
                'hijri_month', 'hijri_day',
            ]);

        if (! empty($filters['type'])) {
            $query->byType($filters['type']);
        }

        if (! empty($filters['importance'])) {
            $query->where('importance', $filters['importance']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->whereHas('translations', function ($tq) use ($search) {
                    $tq->where('title', 'LIKE', "%{$search}%");
                })->orWhere('location', 'LIKE', "%{$search}%");
            });
        }

        return $query->chronological()->paginate($perPage)->withQueryString();
    }

    /**
     * Bulk-update publication status in a single query.
     *
     * @param  array<int>  $ids
     */
    public function bulkUpdateStatus(array $ids, string $status): int
    {
        $affected = DB::transaction(
            fn () => $this->model->newQuery()->whereIn('id', $ids)->update(['status' => $status])
        );

        $this->clearCache();

        return $affected;
    }

    /**
     * Find by slug with relations
     */
    public function findBySlug(string $slug, array $columns = ['*'], array $relations = []): ?Model
    {
        $relations = empty($relations) ? $this->detailRelations() : $relations;

        return Cache::remember(
            "seerah_event_slug_{$slug}",
            self::CACHE_TTL,
            fn () => $this->model
                ->with($relations)
                ->published()
                ->where('slug', $slug)
                ->first($columns)
        );
    }

    /**
     * Minimal relations for list/index endpoints.
     * Loads only the translation titles needed to render a list row.
     * Avoids the 5 article sub-relations and the participants join entirely.
     *
     * @return array<int, string>
     */
    private function listRelations(): array
    {
        return [
            'translations:id,seerah_event_id,locale,title',
            'type:id,slug,icon,color,gradient',
            'type.translations:id,seerah_event_type_id,locale,name',
        ];
    }

    /**
     * Full relations for single-record detail/show endpoints.
     * Includes all article sub-relations, participant data (+translations),
     * and the ordered inner event timeline items (+translations).
     *
     * @return array<int|string, string|\Closure>
     */
    private function detailRelations(): array
    {
        return [
            'translations:id,seerah_event_id,locale,title,description,content,is_ready',
            'type:id,slug,icon,color,gradient',
            'type.translations:id,seerah_event_type_id,locale,name',
            'participants:id,event_id,participant_type,participant_id,role',
            'participants.translations:id,seerah_participant_id,locale,name,description',
            'timelineItems' => fn ($query) => $query
                ->select(['id', 'seerah_event_id', 'display_order'])
                ->orderBy('display_order'),
            'timelineItems.translations:id,seerah_event_timeline_item_id,locale,title,description',
            'article:id,status,thumb,category_id,writer_id,read_time,views,is_featured,is_pinned,hijri_date,created_at,updated_at',
            'article.locales:id,article_id,locale,title,slug,excerpt,is_ready',
            'article.category:id,slug,icon,color',
            'article.category.translations:id,category_id,locale,name',
            'article.categories:id,slug,icon,color',
            'article.categories.translations:id,category_id,locale,name',
            'article.tags:id,slug,type',
            'article.tags.translations:id,tag_id,locale,name',
            'article.mediaItems:id,filename,path,mime_type,type,alt_text',
        ];
    }

    /**
     * Alias for detailRelations() — kept for backward compatibility with any
     * BaseRepository or external callers that reference defaultRelations().
     *
     * @return array<int, string>
     */
    private function defaultRelations(): array
    {
        return $this->detailRelations();
    }

    /**
     * Clear cache for event
     */
    public function clearCacheForModel(int $id): void
    {
        $event = $this->find($id);
        if ($event) {
            Cache::forget("seerah_event_slug_{$event->slug}");
            Cache::forget('seerah_featured_10');
            Cache::forget('seerah_events_with_location');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getIndexStats(): array
    {
        $rows = DB::table('seerah_events as e')
            ->leftJoin('seerah_event_types as t', 't.id', '=', 'e.seerah_event_type_id')
            ->selectRaw("COALESCE(t.slug, 'unknown') as type_slug, COUNT(*) as count")
            ->groupBy('type_slug')
            ->pluck('count', 'type_slug');

        return [
            'total' => (int) $rows->sum(),
            'categories' => $rows->all(),
        ];
    }
}
