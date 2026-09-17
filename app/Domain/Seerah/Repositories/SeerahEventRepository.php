<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Repositories;

use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Seerah\Services\SeerahCacheKeys;
use App\Domain\Shared\Helpers\FullTextSearchHelper;
use App\Domain\Shared\Repositories\BaseRepository;
use App\Domain\Shared\Services\PublicVisibilityRegistry;
use Illuminate\Database\Eloquent\Builder;
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
     * Columns a list row / card needs. FKs are included so the eager loads can
     * resolve without a second query per row.
     */
    private const LIST_COLUMNS = [
        'id', 'slug', 'event_type', 'sm_id', 'location_id', 'order', 'status', 'era',
        'thumb_url', 'cover_url', 'hijri_year', 'hijri_month', 'hijri_day', 'gregorian_date',
    ];

    public function __construct(
        private readonly PublicVisibilityRegistry $publicVisibility,
    ) {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function getModel(): Model
    {
        return new SeerahEvent;
    }

    /**
     * {@inheritdoc}
     */
    public function getPublicPage(string $slug, string $locale, int $relatedLimit = 3): ?array
    {
        $query = $this->model->newQuery()
            ->select([...self::LIST_COLUMNS, 'statistics'])
            ->with($this->publicDetailRelations($locale))
            ->where('slug', $slug);

        /** @var SeerahEvent|null $event */
        $event = $this->publicVisibility->apply($query, $locale)->first();

        if ($event === null) {
            return null;
        }

        $adjacent = $this->getAdjacentForPublic($event, $locale);

        return [
            'event' => $event,
            'prev' => $adjacent['prev'],
            'next' => $adjacent['next'],
            'related' => $this->getRelatedForPublic($event, $locale, $relatedLimit),
        ];
    }

    /**
     * Get events by type (type id or slug). Public read — published + ready translation.
     */
    public function getByType(int|string $type, int $perPage = 15): LengthAwarePaginator
    {
        return $this->publicList()->byType($type)->paginate($perPage);
    }

    /**
     * Get events by Hijri year. Public read — published + ready translation.
     */
    public function getByHijriYear(int $year, int $perPage = 15): LengthAwarePaginator
    {
        return $this->publicList()->byHijriYear($year)->paginate($perPage);
    }

    /**
     * Get events by Gregorian year. Public read — published + ready translation.
     */
    public function getByGregorianYear(int $year, int $perPage = 15): LengthAwarePaginator
    {
        return $this->publicList()->byGregorianYear($year)->paginate($perPage);
    }

    /**
     * Get events whose place has coordinates (for the map). Public read — published + ready translation.
     */
    public function getEventsWithLocation(): Collection
    {
        return Cache::remember(
            SeerahCacheKeys::eventsWithLocation(app()->getLocale()),
            self::CACHE_TTL,
            fn () => $this->publicList()->withLocation()->get()
        );
    }

    /**
     * {@inheritdoc}
     *
     * One query for the whole public directory — search, type, milestone, era
     * and dating-dispute compose instead of branching into separate methods.
     */
    public function paginatePublicFiltered(array $filters, string $locale, int $perPage = 12): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->select(self::LIST_COLUMNS)
            ->with($this->listRelations());

        if (! empty($filters['search'])) {
            $term = $filters['search'];
            $query->whereHas('translations', fn ($tq) => FullTextSearchHelper::apply($tq, ['title', 'description'], $term));
        }

        if (! empty($filters['type'])) {
            $query->byType($filters['type']);
        }

        if (! empty($filters['milestone'])) {
            $query->whereHas('milestone', fn ($q) => $q->where('slug', $filters['milestone']));
        }

        if (! empty($filters['era'])) {
            $query->byEra($filters['era']);
        }

        if (! empty($filters['disputed'])) {
            $this->whereDisputed($query, $locale);
        }

        return $this->publicVisibility->apply($query, $locale)
            ->chronological(($filters['sort'] ?? 'asc') === 'desc' ? 'desc' : 'asc')
            ->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function publicFilterFacets(string $locale): array
    {
        $query = $this->publicVisibility->apply($this->model->newQuery(), $locale);

        return [
            'disputed' => $this->whereDisputed($query, $locale)->count(),
        ];
    }

    /**
     * Search events via translation table. Public read — published + ready translation.
     * FULLTEXT index covers (title, description) on seerah_event_translations.
     */
    public function search(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $this->publicList()
            ->whereHas('translations', fn ($tq) => FullTextSearchHelper::apply($tq, ['title', 'description'], $query))
            ->paginate($perPage);
    }

    /**
     * Get events in chronological order. Public read — published + ready translation.
     */
    public function getChronological(int $perPage = 15): LengthAwarePaginator
    {
        return $this->publicList()->paginate($perPage);
    }

    /**
     * Paginate events for the admin index with whitelisted filters.
     * Admin path — NOT restricted to published so editors see drafts too.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->select(self::LIST_COLUMNS)
            // Milestone drives a column on the admin list; loading it here keeps
            // the page at a constant query count instead of 2 per row.
            ->with([
                ...$this->listRelations(),
                'milestone:id,slug,order,metadata',
                'milestone.translations:id,sm_id,locale,title',
            ]);

        if (! empty($filters['type'])) {
            $query->byType($filters['type']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['era'])) {
            $query->byEra($filters['era']);
        }

        if (! empty($filters['milestone'])) {
            $query->byMilestone((int) $filters['milestone']);
        }

        if (! empty($filters['location_id'])) {
            $query->where('location_id', (int) $filters['location_id']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->whereHas('translations', fn ($tq) => $tq->where('title', 'LIKE', "%{$search}%"))
                    ->orWhere('slug', 'LIKE', "%{$search}%");
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
     * {@inheritdoc}
     */
    public function getForExport(array $filters = []): Collection
    {
        $query = $this->model->newQuery()
            ->select(self::LIST_COLUMNS)
            ->with([
                'translations:id,event_id,locale,title,description',
                'type:id,slug,locale',
                'place:id,slug',
                'place.translations:id,location_id,locale,name',
            ]);

        if (! empty($filters['event_type'])) {
            $query->byType($filters['event_type']);
        }

        if (! empty($filters['start_date'])) {
            $query->where('gregorian_date', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->where('gregorian_date', '<=', $filters['end_date']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('translations', fn ($tq) => $tq->where('title', 'LIKE', "%{$search}%"));
        }

        return $query->chronological()->get();
    }

    /**
     * Find a publicly visible event by slug with its full detail relations (API show).
     */
    public function findBySlug(string $slug, array $columns = ['*'], array $relations = []): ?Model
    {
        $relations = empty($relations) ? $this->detailRelations() : $relations;
        $locale = app()->getLocale();

        return Cache::remember(
            SeerahCacheKeys::eventBySlug($slug, $locale),
            self::CACHE_TTL,
            fn () => $this->publicVisibility
                ->apply($this->model->newQuery()->with($relations)->where('slug', $slug), $locale)
                ->first($columns)
        );
    }

    /**
     * Publicly visible (published + ready in the current locale), chronological,
     * list-shaped query shared by the public reads.
     */
    private function publicList(): Builder
    {
        $query = $this->model->newQuery()
            ->select(self::LIST_COLUMNS)
            ->with($this->listRelations());

        return $this->publicVisibility->apply($query, app()->getLocale())->chronological();
    }

    private function whereDisputed(Builder $query, string $locale): Builder
    {
        return $query->whereHas('translations', fn ($q) => $q
            ->where('locale', $locale)
            ->whereNotNull('dispute_note')
            ->where('dispute_note', '<>', ''));
    }

    /**
     * Minimal relations for list/index endpoints.
     *
     * @return array<int, string>
     */
    private function listRelations(): array
    {
        return [
            // description included: public list cards render an excerpt.
            // dispute_note included: list cards carry the ⚠ dating-dispute marker.
            'translations:id,event_id,locale,title,description,dispute_note,is_ready',
            'type:id,slug,metadata,locale',
            'place:id,slug,latitude,longitude',
            'place.translations:id,location_id,locale,name',
        ];
    }

    /**
     * @return array<int|string, string|\Closure>
     */
    private function publicDetailRelations(string $locale): array
    {
        return [
            'translations' => fn ($relation) => $relation
                ->select(['id', 'event_id', 'locale', 'title', 'description', 'content', 'dispute_note', 'metadata', 'sources', 'seo_metadata', 'external_links', 'is_ready'])
                ->where('locale', $locale)
                ->where('is_ready', true),
            'type:id,slug,metadata,locale',
            // Milestone (الحقبة) — powers the breadcrumb + navigator on the event pages.
            'milestone:id,slug',
            'milestone.translations' => fn ($relation) => $relation
                ->select(['id', 'sm_id', 'locale', 'title'])
                ->where('locale', $locale),
            // Canonical place: the map coordinates and the localized place name.
            'place:id,slug,type,latitude,longitude',
            'place.translations' => fn ($relation) => $relation
                ->select(['id', 'location_id', 'locale', 'name'])
                ->whereIn('locale', array_unique([$locale, 'ar'])),
            'gallery:id,event_id,image,media_id,caption,order',
            'mediaLinks:id,event_id,media_id',
            'links:id,event_id,type,target_id,order',
        ];
    }

    /**
     * Full relations for the single-record API show endpoint.
     *
     * @return array<int, string>
     */
    private function detailRelations(): array
    {
        return [
            'translations:id,event_id,locale,title,description,content,dispute_note,metadata,sources,seo_metadata,external_links,is_ready',
            'type:id,slug,metadata,locale',
            'milestone:id,slug,order',
            'milestone.translations:id,sm_id,locale,title',
            'place:id,slug,latitude,longitude',
            'place.translations:id,location_id,locale,name',
            'gallery:id,event_id,image,media_id,caption,order',
            'mediaLinks:id,event_id,media_id',
            'links:id,event_id,type,target_id,order',
        ];
    }

    /**
     * @return array{prev: ?SeerahEvent, next: ?SeerahEvent}
     */
    private function getAdjacentForPublic(SeerahEvent $event, string $locale): array
    {
        $base = function () use ($event, $locale): Builder {
            $query = $this->model->newQuery()
                ->select(['id', 'slug', 'order', 'hijri_year', 'hijri_month', 'hijri_day'])
                ->with(['translations' => fn ($relation) => $relation
                    ->select(['id', 'event_id', 'locale', 'title', 'is_ready'])
                    ->where('locale', $locale)
                    ->where('is_ready', true),
                ])
                ->whereKeyNot($event->id);

            return $this->publicVisibility->apply($query, $locale);
        };

        $prevQuery = $base();
        $this->applyChronologyBoundary($prevQuery, $event, '<');

        $nextQuery = $base();
        $this->applyChronologyBoundary($nextQuery, $event, '>');

        return [
            'prev' => $prevQuery->chronological('desc')->first(),
            'next' => $nextQuery->chronological()->first(),
        ];
    }

    /**
     * Related events: the same milestone first, then the same era, each in
     * chronological order. With neither set there is nothing to relate by.
     */
    private function getRelatedForPublic(SeerahEvent $event, string $locale, int $limit): Collection
    {
        if ($event->sm_id === null && $event->era === null) {
            return new Collection;
        }

        $query = $this->model->newQuery()
            ->select(self::LIST_COLUMNS)
            ->with([
                'translations' => fn ($relation) => $relation
                    ->select(['id', 'event_id', 'locale', 'title', 'description', 'dispute_note', 'is_ready'])
                    ->where('locale', $locale)
                    ->where('is_ready', true),
                'type:id,slug,metadata,locale',
                'place:id,slug,latitude,longitude',
                'place.translations:id,location_id,locale,name',
            ])
            ->whereKeyNot($event->id)
            ->where(function (Builder $candidate) use ($event): void {
                if ($event->sm_id !== null) {
                    $candidate->orWhere('sm_id', $event->sm_id);
                }
                if ($event->era !== null) {
                    $candidate->orWhere('era', $event->era->value);
                }
            });

        if ($event->sm_id !== null) {
            $query->orderByRaw('CASE WHEN sm_id = ? THEN 0 ELSE 1 END', [$event->sm_id]);
        }

        $this->publicVisibility->apply($query, $locale);

        return $query->chronological()->limit($limit)->get();
    }

    private function applyChronologyBoundary(Builder $query, SeerahEvent $event, string $operator): void
    {
        $branches = 0;

        $query->where(function (Builder $boundaries) use ($event, $operator, &$branches): void {
            foreach (SeerahEvent::CHRONOLOGY_COLUMNS as $index => $column) {
                $value = $event->getAttribute($column);

                if ($operator === '<' && $value === null) {
                    continue;
                }

                $branches++;
                $boundaries->orWhere(function (Builder $branch) use ($event, $operator, $index, $column, $value): void {
                    foreach (array_slice(SeerahEvent::CHRONOLOGY_COLUMNS, 0, $index) as $equalColumn) {
                        $equalValue = $event->getAttribute($equalColumn);
                        $equalValue === null
                            ? $branch->whereNull($equalColumn)
                            : $branch->where($equalColumn, $equalValue);
                    }

                    if ($operator === '>' && $value === null) {
                        $branch->whereNotNull($column);

                        return;
                    }

                    if ($operator === '<') {
                        $branch->where(fn (Builder $comparison) => $comparison
                            ->whereNull($column)
                            ->orWhere($column, '<', $value));

                        return;
                    }

                    $branch->where($column, '>', $value);
                });
            }
        });

        if ($branches === 0) {
            $query->whereRaw('0 = 1');
        }
    }

    /**
     * {@inheritdoc}
     *
     * getIndexStats() counts every row whatever its status — that is the admin
     * header. The public filter pills must not, or drafts inflate them.
     */
    public function countPublicByCategory(string $locale): array
    {
        $query = $this->model->newQuery()
            ->leftJoin('seerah_event_types as t', 't.id', '=', 'seerah_events.event_type')
            ->selectRaw("COALESCE(t.slug, 'unknown') as type_slug, COUNT(*) as count")
            ->groupBy('type_slug');

        return $this->publicVisibility->apply($query, $locale)
            ->pluck('count', 'type_slug')
            ->map(fn ($count) => (int) $count)
            ->all();
    }

    /**
     * {@inheritdoc}
     */
    public function getIndexStats(): array
    {
        $rows = DB::table('seerah_events as e')
            ->leftJoin('seerah_event_types as t', 't.id', '=', 'e.event_type')
            ->selectRaw("COALESCE(t.slug, 'unknown') as type_slug, COUNT(*) as count")
            ->groupBy('type_slug')
            ->pluck('count', 'type_slug');

        // One extra aggregate rather than a second pass per status — the admin
        // list header shows published/draft alongside the per-type breakdown.
        $byStatus = DB::table('seerah_events')
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return [
            'total' => (int) $rows->sum(),
            'categories' => $rows->all(),
            'published' => (int) ($byStatus['published'] ?? 0),
            'draft' => (int) ($byStatus['draft'] ?? 0),
        ];
    }
}
