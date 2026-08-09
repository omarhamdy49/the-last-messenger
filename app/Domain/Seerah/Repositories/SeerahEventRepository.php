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
    private const CHRONOLOGY_COLUMNS = ['hijri_year', 'hijri_month', 'hijri_day', 'display_order'];

    /**
     * Cache TTL in seconds (30 minutes)
     */
    private const CACHE_TTL = 1800;

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
            ->select([
                'id', 'slug', 'hijri_date', 'hijri_year', 'hijri_month', 'hijri_day',
                'gregorian_year', 'seerah_event_type_id', 'phase', 'era', 'location',
                'latitude', 'longitude', 'featured_image', 'gallery', 'related_article_id',
                'is_pivotal', 'is_featured', 'views_count', 'statistics', 'display_order',
            ])
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
            SeerahCacheKeys::featured($limit),
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
            SeerahCacheKeys::eventsWithLocation(),
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
     * {@inheritdoc}
     */
    public function getForExport(array $filters = []): Collection
    {
        $query = $this->model->newQuery()->with($this->exportRelations());

        if (! empty($filters['event_type'])) {
            $query->byType($filters['event_type']);
        }

        if (! empty($filters['start_date'])) {
            $query->whereDate('gregorian_date', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->whereDate('gregorian_date', '<=', $filters['end_date']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->whereHas('translations', fn ($tq) => $tq->where('title', 'LIKE', "%{$search}%"))
                    ->orWhere('location', 'LIKE', "%{$search}%");
            });
        }

        return $query->chronological()->get();
    }

    /**
     * Relations needed to render an export row. Mirrors the translation-backed
     * shape of detailRelations() minus the article sub-tree, which is not
     * exported.
     *
     * @return array<int, string>
     */
    private function exportRelations(): array
    {
        return [
            'translations:id,seerah_event_id,locale,title,description',
            'type:id,slug',
            'type.translations:id,seerah_event_type_id,locale,name',
            'participants:id,event_id,role',
            'participants.translations:id,seerah_participant_id,locale,name',
        ];
    }

    /**
     * Find by slug with relations
     */
    public function findBySlug(string $slug, array $columns = ['*'], array $relations = []): ?Model
    {
        $relations = empty($relations) ? $this->detailRelations() : $relations;

        return Cache::remember(
            SeerahCacheKeys::eventBySlug($slug),
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
     * @return array<int|string, string|\Closure>
     */
    private function publicDetailRelations(string $locale): array
    {
        return [
            'translations' => fn ($relation) => $relation
                ->select(['id', 'seerah_event_id', 'locale', 'title', 'description', 'content', 'is_ready'])
                ->where('locale', $locale)
                ->where('is_ready', true),
            'type:id,slug,icon,color,gradient',
            'type.translations' => fn ($relation) => $relation
                ->select(['id', 'seerah_event_type_id', 'locale', 'name'])
                ->where('locale', $locale),
            'participants:id,event_id,participant_type,participant_id,role',
            'participants.translations' => fn ($relation) => $relation
                ->select(['id', 'seerah_participant_id', 'locale', 'name', 'description'])
                ->where('locale', $locale),
            'timelineItems' => fn ($relation) => $relation
                ->select(['id', 'seerah_event_id', 'display_order'])
                ->orderBy('display_order'),
            'timelineItems.translations' => fn ($relation) => $relation
                ->select(['id', 'seerah_event_timeline_item_id', 'locale', 'title', 'description'])
                ->where('locale', $locale),
            'article' => function ($relation) use ($locale): void {
                $query = $relation->getQuery()->select(['articles.id', 'articles.status']);
                $this->publicVisibility->apply($query, $locale);
            },
            'article.locales' => fn ($relation) => $relation
                ->select(['id', 'article_id', 'locale', 'title', 'slug', 'is_ready'])
                ->where('locale', $locale)
                ->where('is_ready', true),
        ];
    }

    /**
     * @return array{prev: ?SeerahEvent, next: ?SeerahEvent}
     */
    private function getAdjacentForPublic(SeerahEvent $event, string $locale): array
    {
        $base = function () use ($event, $locale): Builder {
            $query = $this->model->newQuery()
                ->select(['id', 'slug', 'hijri_year', 'hijri_month', 'hijri_day', 'display_order'])
                ->with(['translations' => fn ($relation) => $relation
                    ->select(['id', 'seerah_event_id', 'locale', 'title', 'is_ready'])
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

    private function getRelatedForPublic(SeerahEvent $event, string $locale, int $limit): Collection
    {
        $query = $this->model->newQuery()
            ->select([
                'id', 'slug', 'seerah_event_type_id', 'phase', 'hijri_date',
                'hijri_year', 'hijri_month', 'hijri_day', 'gregorian_year',
                'location', 'is_pivotal', 'is_featured', 'display_order',
            ])
            ->with([
                'translations' => fn ($relation) => $relation
                    ->select(['id', 'seerah_event_id', 'locale', 'title', 'description', 'is_ready'])
                    ->where('locale', $locale)
                    ->where('is_ready', true),
                'type:id,slug,icon,color,gradient',
                'type.translations' => fn ($relation) => $relation
                    ->select(['id', 'seerah_event_type_id', 'locale', 'name'])
                    ->where('locale', $locale),
            ])
            ->whereKeyNot($event->id);

        $this->publicVisibility->apply($query, $locale);

        if ($event->phase === null) {
            $query->featured();
        } else {
            $phase = $event->phase->value;
            $query
                ->where(fn (Builder $candidate) => $candidate
                    ->where('phase', $phase)
                    ->orWhere('is_featured', true))
                ->orderByRaw('CASE WHEN phase = ? THEN 0 ELSE 1 END', [$phase]);
        }

        return $query->chronological()->limit($limit)->get();
    }

    private function applyChronologyBoundary(Builder $query, SeerahEvent $event, string $operator): void
    {
        $branches = 0;

        $query->where(function (Builder $boundaries) use ($event, $operator, &$branches): void {
            foreach (self::CHRONOLOGY_COLUMNS as $index => $column) {
                $value = $event->getAttribute($column);

                if ($operator === '<' && $value === null) {
                    continue;
                }

                $branches++;
                $boundaries->orWhere(function (Builder $branch) use ($event, $operator, $index, $column, $value): void {
                    foreach (array_slice(self::CHRONOLOGY_COLUMNS, 0, $index) as $equalColumn) {
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
