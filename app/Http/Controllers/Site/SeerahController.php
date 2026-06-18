<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Domain\Seerah\Enums\EventPhase;
use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Seerah\Services\SeerahEventService;
use App\Domain\Seerah\Services\SeerahEventTypeService;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Throwable;

final class SeerahController extends Controller
{
    public function __construct(
        private readonly SeerahEventService $seerahEventService,
        private readonly SeerahEventTypeService $eventTypeService,
    ) {}

    /**
     * Seerah timeline — full chronological vertical timeline with phase navigation.
     * All events server-rendered once; Alpine handles client-side search + category filtering.
     */
    public function index(): View
    {
        $locale = app()->getLocale();

        $payload = Cache::remember(
            "public_seerah_index_{$locale}",
            1800,
            function () use ($locale): array {
                try {
                    // All 62 events, chronological. perPage large enough to render the full timeline.
                    $paginator = $this->seerahEventService->getAllEvents(perPage: 500);

                    $events = collect($paginator->items())
                        ->map(fn (SeerahEvent $event) => $this->mapTimelineEvent($event, $locale))
                        ->values()
                        ->all();

                    $stats = $this->seerahEventService->getIndexStats();
                    $categoryCounts = $this->seerahEventService->countByCategory();

                    return [
                        'events' => $events,
                        'total' => (int) ($stats['total'] ?? count($events)),
                        'categoryCounts' => $categoryCounts,
                    ];
                } catch (Throwable $e) {
                    report($e);

                    return ['events' => [], 'total' => 0, 'categoryCounts' => []];
                }
            },
        );

        $events = $payload['events'];

        $stats = [
            'total' => $payload['total'],
            'pivotal' => collect($events)->where('isPivotal', true)->count(),
            'phases' => count($this->phaseMeta()),
        ];

        $categoryPills = $this->buildCategoryPills($payload['categoryCounts'], $locale);
        $phases = $this->phaseMeta($locale);

        return view('public.seerah.index', compact('events', 'stats', 'categoryPills', 'phases'));
    }

    /**
     * Seerah events directory — paginated list with search + category filtering (server-side).
     */
    public function events(Request $request): View
    {
        $locale = app()->getLocale();
        $perPage = 12;
        $page = max(1, (int) $request->query('page', 1));
        $search = trim((string) $request->query('q', ''));
        $category = (string) $request->query('category', 'all');

        // Whitelist the category against active DB event-type slugs to prevent invalid filtering.
        $activeSlugs = $this->eventTypeService->getForDropdown()->pluck('slug')->all();
        $validCategory = $category !== 'all' && in_array($category, $activeSlugs, true)
            ? $category
            : 'all';

        $cacheKey = sprintf(
            'public_seerah_events_%s_%s_%s_%d',
            $locale,
            md5($search),
            $validCategory,
            $page,
        );

        $payload = Cache::remember($cacheKey, 900, function () use ($search, $validCategory, $perPage, $locale): array {
            try {
                if ($search !== '') {
                    $paginator = $this->seerahEventService->searchEvents($search, $perPage);
                } elseif ($validCategory !== 'all') {
                    $paginator = $this->seerahEventService->getEventsByType($validCategory, $perPage);
                } else {
                    $paginator = $this->seerahEventService->getAllEvents($perPage);
                }

                $items = collect($paginator->items())
                    ->map(fn (SeerahEvent $event) => $this->mapTimelineEvent($event, $locale))
                    ->values()
                    ->all();

                return [
                    'items' => $items,
                    'total' => $paginator->total(),
                    'currentPage' => $paginator->currentPage(),
                    'lastPage' => $paginator->lastPage(),
                    'categoryCounts' => $this->seerahEventService->countByCategory(),
                ];
            } catch (Throwable $e) {
                report($e);

                return ['items' => [], 'total' => 0, 'currentPage' => 1, 'lastPage' => 1, 'categoryCounts' => []];
            }
        });

        // Rebuild the paginator instance for the view component (cache stores plain arrays only).
        $events = new \Illuminate\Pagination\LengthAwarePaginator(
            items: $payload['items'],
            total: $payload['total'],
            perPage: $perPage,
            currentPage: $payload['currentPage'],
            options: [
                'path' => $request->url(),
                'query' => $request->query(),
            ],
        );

        $categoryPills = $this->buildCategoryPills($payload['categoryCounts'], $locale, $search);

        return view('public.seerah.events', [
            'events' => $events,
            'categoryPills' => $categoryPills,
            'activeCategory' => $validCategory,
            'searchQuery' => $search,
            'totalEvents' => $payload['total'],
        ]);
    }

    /**
     * Single Seerah event — article-style detail page (default) or the
     * immersive timeline style when requested via ?view=timeline.
     */
    public function event(Request $request, string $slug): View
    {
        $locale = app()->getLocale();

        // Whitelist the style variant; anything else falls back to the article style.
        $viewStyle = $request->query('view') === 'timeline' ? 'timeline' : 'article';

        $payload = Cache::remember(
            "public_seerah_event_{$slug}_{$locale}",
            1800,
            function () use ($slug, $locale): ?array {
                try {
                    $event = $this->seerahEventService->getEventBySlug($slug);

                    return $this->mapEventDetail($event, $locale);
                } catch (Throwable $e) {
                    report($e);

                    return null;
                }
            },
        );

        if ($payload === null) {
            abort(404);
        }

        // Related events from the same phase (or featured fallback), excluding the current one.
        $related = Cache::remember(
            "public_seerah_related_{$slug}_{$locale}",
            1800,
            function () use ($payload, $locale): array {
                try {
                    $featured = $this->seerahEventService->getFeaturedEvents(8);

                    return $featured
                        ->reject(fn (SeerahEvent $event) => $event->slug === $payload['slug'])
                        ->take(3)
                        ->map(fn (SeerahEvent $event) => $this->mapTimelineEvent($event, $locale))
                        ->values()
                        ->all();
                } catch (Throwable) {
                    return [];
                }
            },
        );

        $template = $viewStyle === 'timeline' ? 'public.seerah.event-timeline' : 'public.seerah.event';

        return view($template, [
            'event' => $payload,
            'related' => $related,
        ]);
    }

    /**
     * Map an event model to a compact array for timeline cards / list rows.
     *
     * @return array<string, mixed>
     */
    private function mapTimelineEvent(SeerahEvent $event, string $locale): array
    {
        $type = $event->type;
        $phase = $event->phase;

        return [
            'slug' => $event->slug,
            'title' => $event->getTranslation('title', $locale) ?? __('seerah.public.untitled_event'),
            'description' => $event->getTranslation('description', $locale),
            'type' => $type?->slug,
            'typeLabel' => $type?->getTranslation('name', $locale) ?? '',
            'icon' => $type?->icon ?? 'circle',
            'gradient' => $type?->gradient ?? 'from-slate-400 to-slate-500',
            'phase' => $phase?->value,
            'hijriDate' => $event->hijri_date,
            'hijriYear' => $event->hijri_year,
            'gregorianYear' => $event->gregorian_year,
            'location' => $event->location,
            'isPivotal' => (bool) $event->is_pivotal,
            'isFeatured' => (bool) $event->is_featured,
            'url' => route('public.seerah.event', $event->slug),
        ];
    }

    /**
     * Map an event model to the full detail payload (intro/context/lessons/sources + characters).
     *
     * @return array<string, mixed>
     */
    private function mapEventDetail(SeerahEvent $event, string $locale): array
    {
        $type = $event->type;
        $phase = $event->phase;
        $era = $event->era;

        $characters = $event->participants
            ->map(fn ($participant) => [
                'name' => $participant->getTranslation('name', $locale) ?? '',
                'description' => $participant->getTranslation('description', $locale),
                'role' => $participant->role->value,
                'roleLabel' => $locale === 'ar' ? $participant->role->labelArabic() : $participant->role->label(),
            ])
            ->filter(fn (array $character) => $character['name'] !== '')
            ->values()
            ->all();

        // Inner event timeline (ordered by display_order in the repository eager load).
        $timeline = $event->timelineItems
            ->map(fn ($item) => [
                'title' => $item->getTranslation('title', $locale) ?? '',
                'description' => $item->getTranslation('description', $locale),
                'order' => (int) $item->display_order,
            ])
            ->filter(fn (array $timelineItem) => $timelineItem['title'] !== '')
            ->values()
            ->all();

        return [
            'slug' => $event->slug,
            'title' => $event->getTranslation('title', $locale) ?? __('seerah.public.untitled_event'),
            'description' => $event->getTranslation('description', $locale),
            'content' => $event->getTranslation('content', $locale),
            'type' => $type?->slug,
            'typeLabel' => $type?->getTranslation('name', $locale) ?? '',
            'icon' => $type?->icon ?? 'circle',
            'gradient' => $type?->gradient ?? 'from-slate-400 to-slate-500',
            'phase' => $phase?->value,
            'phaseLabel' => $phase ? ($locale === 'ar' ? $phase->labelArabic() : $phase->label()) : null,
            'era' => $era?->value,
            'eraLabel' => $era ? ($locale === 'ar' ? $era->labelArabic() : $era->label()) : null,
            'hijriDate' => $event->hijri_date,
            'hijriYear' => $event->hijri_year,
            'gregorianYear' => $event->gregorian_year,
            'location' => $event->location,
            'latitude' => $event->latitude !== null ? (float) $event->latitude : null,
            'longitude' => $event->longitude !== null ? (float) $event->longitude : null,
            'isPivotal' => (bool) $event->is_pivotal,
            'viewsCount' => (int) $event->views_count,
            'statistics' => is_array($event->statistics) ? $event->statistics : [],
            'characters' => $characters,
            'timeline' => $timeline,
            'url' => route('public.seerah.event', $event->slug),
        ];
    }

    /**
     * Build category filter pills from event-type counts, ordered by the EventType enum.
     *
     * @param  array<string, int>  $counts
     * @return array<int, array<string, mixed>>
     */
    private function buildCategoryPills(array $counts, string $locale, string $search = ''): array
    {
        $total = array_sum($counts);

        $pills = [[
            'value' => 'all',
            'label' => __('seerah.public.filter_all'),
            'icon' => 'layout-grid',
            'count' => $total,
            'url' => route('public.seerah.events', array_filter(['q' => $search ?: null])),
        ]];

        foreach ($this->eventTypeService->getForDropdown() as $type) {
            $count = $counts[$type->slug] ?? 0;
            if ($count === 0) {
                continue;
            }

            $pills[] = [
                'value' => $type->slug,
                'label' => $type->getTranslation('name', $locale),
                'icon' => $type->icon ?? 'circle',
                'count' => $count,
                'url' => route('public.seerah.events', array_filter([
                    'category' => $type->slug,
                    'q' => $search ?: null,
                ])),
            ];
        }

        return $pills;
    }

    /**
     * Phase metadata for the sticky phase navigation and timeline phase headers.
     *
     * @return array<int, array<string, string>>
     */
    private function phaseMeta(?string $locale = null): array
    {
        $locale ??= app()->getLocale();

        return collect(EventPhase::cases())
            ->map(fn (EventPhase $phase) => [
                'value' => $phase->value,
                'label' => $locale === 'ar' ? $phase->labelArabic() : $phase->label(),
                'icon' => match ($phase) {
                    EventPhase::EARLY_LIFE => 'sunrise',
                    EventPhase::MECCA => 'book-open',
                    EventPhase::HIJRA => 'map',
                    EventPhase::FINAL_YEARS => 'heart',
                },
            ])
            ->all();
    }
}
