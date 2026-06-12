<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Repositories;

use App\Domain\Seerah\Enums\EventType;
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
     * Get events by type
     */
    public function getByType(EventType|string $type, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->with($this->listRelations())
            ->byType($type)
            ->chronological()
            ->paginate($perPage);
    }

    /**
     * Get events by Hijri year
     */
    public function getByHijriYear(int $year, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->with($this->listRelations())
            ->byHijriYear($year)
            ->chronological()
            ->paginate($perPage);
    }

    /**
     * Get events by Gregorian year
     */
    public function getByGregorianYear(int $year, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->with($this->listRelations())
            ->byGregorianYear($year)
            ->chronological()
            ->paginate($perPage);
    }

    /**
     * Get featured events
     */
    public function getFeatured(int $limit = 10): Collection
    {
        return Cache::remember(
            "seerah_featured_{$limit}",
            self::CACHE_TTL,
            fn () => $this->model
                ->with($this->listRelations())
                ->featured()
                ->chronological()
                ->limit($limit)
                ->get()
        );
    }

    /**
     * Get events with location
     */
    public function getEventsWithLocation(): Collection
    {
        return Cache::remember(
            'seerah_events_with_location',
            self::CACHE_TTL,
            fn () => $this->model
                ->with('translations:id,seerah_event_id,locale,title')
                ->withLocation()
                ->chronological()
                ->get(['id', 'slug', 'location', 'latitude', 'longitude', 'event_type', 'hijri_year'])
        );
    }

    /**
     * Search events via translation table.
     * FULLTEXT index covers (title, description) on seerah_event_translations.
     * 'location' on seerah_events has no FULLTEXT index and keeps LIKE.
     */
    public function search(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->with($this->listRelations())
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
     * Get events in chronological order
     */
    public function getChronological(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->with($this->listRelations())
            ->chronological()
            ->paginate($perPage);
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
        $rows = DB::table('seerah_events')
            ->selectRaw('event_type, COUNT(*) as count')
            ->groupBy('event_type')
            ->pluck('count', 'event_type');

        return [
            'total' => (int) $rows->sum(),
            'categories' => $rows->all(),
        ];
    }
}
