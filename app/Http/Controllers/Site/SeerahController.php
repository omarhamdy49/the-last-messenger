<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Domain\Seerah\Enums\EventImportance;
use App\Domain\Seerah\Enums\EventPhase;
use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Seerah\Models\SeerahMilestone;
use App\Domain\Seerah\Services\SeerahCacheKeys;
use App\Domain\Seerah\Services\SeerahEventService;
use App\Domain\Seerah\Services\SeerahEventTypeService;
use App\Domain\Seerah\Services\SeerahMilestoneService;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

final class SeerahController extends Controller
{
    public function __construct(
        private readonly SeerahEventService $seerahEventService,
        private readonly SeerahEventTypeService $eventTypeService,
        private readonly SeerahMilestoneService $milestoneService,
    ) {}

    /**
     * Seerah hero-journey — milestones (الحِقَب) as story chapters along a spine
     * timeline, plus a flat "articles" mode. Only the milestone shells are
     * server-rendered; each milestone's events (and the articles feed) are
     * lazy-loaded from the JSON endpoints below when they scroll into view.
     */
    public function index(): View
    {
        $payload = $this->cachedJourneyPayload(app()->getLocale());

        return view('public.seerah.index', [
            'journey' => $payload['journey'],
            'stats' => $payload['stats'],
        ]);
    }

    /**
     * Journey shells + stats, cached per locale. Shared by the index page and
     * the event pages' milestone navigator modal.
     *
     * @return array{journey: array<int, array<string, mixed>>, stats: array<string, mixed>}
     */
    private function cachedJourneyPayload(string $locale): array
    {
        return Cache::remember(
            SeerahCacheKeys::journey($locale),
            1800,
            function () use ($locale): array {
                try {
                    $journey = $this->buildJourney($locale);

                    return [
                        'journey' => $journey,
                        'stats' => [
                            'milestones' => count($journey),
                            // Every published event, not just those grouped under an
                            // era — unassigned events still count.
                            'events' => SeerahEvent::query()->published()->count(),
                            'span' => $this->journeySpan($journey, $locale),
                            'pivotal' => array_sum(array_column($journey, 'pivotalCount')),
                            'disputed' => array_sum(array_column($journey, 'disputedCount')),
                        ],
                    ];
                } catch (Throwable $e) {
                    report($e);

                    return ['journey' => [], 'stats' => ['milestones' => 0, 'events' => 0, 'span' => '', 'pivotal' => 0, 'disputed' => 0]];
                }
            },
        );
    }

    /**
     * Events of a single journey milestone — JSON for viewport lazy loading.
     */
    public function journeyEvents(string $slug): JsonResponse
    {
        $locale = app()->getLocale();

        $events = Cache::remember(
            SeerahCacheKeys::journeyMilestoneEvents($slug, $locale),
            1800,
            function () use ($slug, $locale): ?array {
                try {
                    /** @var SeerahMilestone|null $milestone */
                    $milestone = $this->milestoneService->getJourney()->firstWhere('slug', $slug);

                    if ($milestone === null) {
                        return null;
                    }

                    return $milestone->events
                        ->map(fn (SeerahEvent $event) => $this->mapTimelineEvent($event, $locale))
                        ->values()
                        ->all();
                } catch (Throwable $e) {
                    report($e);

                    return null;
                }
            },
        );

        if ($events === null) {
            abort(404);
        }

        return response()->json(['events' => $events]);
    }

    /**
     * Flat chronological events feed — JSON pages for the "articles" mode
     * infinite scroll.
     */
    public function articlesFeed(Request $request): JsonResponse
    {
        $locale = app()->getLocale();
        $page = max(1, (int) $request->query('page', 1));

        $payload = Cache::remember(
            SeerahCacheKeys::articlesFeed($locale, $page),
            900,
            function () use ($locale): array {
                try {
                    $paginator = $this->seerahEventService->getAllEvents(perPage: 24);

                    return [
                        'items' => collect($paginator->items())
                            ->map(fn (SeerahEvent $event) => $this->mapTimelineEvent($event, $locale))
                            ->values()
                            ->all(),
                        'page' => $paginator->currentPage(),
                        'lastPage' => $paginator->lastPage(),
                        'total' => $paginator->total(),
                    ];
                } catch (Throwable $e) {
                    report($e);

                    return ['items' => [], 'page' => 1, 'lastPage' => 1, 'total' => 0];
                }
            },
        );

        return response()->json($payload);
    }

    /**
     * Build the milestone journey shells: metadata + event counts only. The
     * events themselves are served by journeyEvents() on demand.
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildJourney(string $locale): array
    {
        return $this->milestoneService->getJourney()
            ->map(function (SeerahMilestone $milestone) use ($locale): array {
                return [
                    'slug' => $milestone->slug,
                    'order' => $milestone->order_index,
                    'anchor' => 'milestone-'.$milestone->slug,
                    'title' => $milestone->getTranslation('title', $locale) ?? '',
                    'subtitle' => $milestone->getTranslation('subtitle', $locale),
                    'description' => $milestone->getTranslation('description', $locale),
                    'icon' => $milestone->icon ?: 'milestone',
                    'gradient' => $milestone->gradient ?: 'from-[#0056b3] to-[#218838]',
                    'hijriRange' => $milestone->hijriRangeLabel(),
                    'gregorianRange' => $milestone->gregorianRangeLabel(),
                    'eventCount' => $milestone->events->count(),
                    'eventsUrl' => route('public.seerah.journey.events', $milestone->slug),
                    // Flags surfaced on the era card itself: buried 6000px down inside
                    // a lazy-loaded row, a marker is not findable.
                    'pivotalCount' => $milestone->events->where('is_pivotal', true)->count(),
                    'disputedCount' => $milestone->events
                        ->filter(fn (SeerahEvent $event): bool => filled($event->getTranslation('dispute_note', $locale)))
                        ->count(),
                    'pivotalUrl' => route('public.seerah.events', ['milestone' => $milestone->slug, 'pivotal' => 1]),
                    'disputedUrl' => route('public.seerah.events', ['milestone' => $milestone->slug, 'disputed' => 1]),
                ];
            })
            ->filter(fn (array $milestone): bool => $milestone['eventCount'] > 0)
            ->values()
            ->all();
    }

    /**
     * Compact "first → last" Hijri span label across the whole journey.
     *
     * @param  array<int, array<string, mixed>>  $journey
     */
    private function journeySpan(array $journey, string $locale): string
    {
        if ($journey === []) {
            return '';
        }

        $first = $journey[0]['hijriRange'] ?? '';
        $last = end($journey)['hijriRange'] ?? '';

        $from = trim(explode('–', $first)[0] ?? '');
        $to = trim(array_slice(explode('–', $last), -1)[0] ?? '');

        return $from && $to ? "{$from} — {$to}" : ($from ?: $to);
    }

    /**
     * Seerah events directory — paginated list with search + category filtering (server-side).
     */
    public function events(Request $request): View
    {
        $locale = app()->getLocale();
        $perPage = 12;
        $page = max(1, (int) $request->query('page', 1));

        // Every filter is whitelisted against real data before it reaches a query.
        $typeSlugs = $this->eventTypeService->getForDropdown()->pluck('slug')->all();
        $milestones = $this->milestoneService->getForDropdown();

        $filters = [
            'search' => trim((string) $request->query('q', '')),
            'type' => $this->whitelisted($request->query('category'), $typeSlugs),
            'milestone' => $this->whitelisted($request->query('milestone'), $milestones->pluck('slug')->all()),
            'importance' => $this->whitelisted($request->query('importance'), EventImportance::values()),
            'pivotal' => $request->boolean('pivotal'),
            'disputed' => $request->boolean('disputed'),
            'sort' => $request->query('sort') === 'desc' ? 'desc' : 'asc',
        ];

        $cacheKey = SeerahCacheKeys::events($locale, $filters, $page);

        $payload = Cache::remember($cacheKey, 900, function () use ($filters, $perPage, $locale): array {
            try {
                $paginator = $this->seerahEventService->paginatePublicFiltered($filters, $locale, $perPage);

                $items = collect($paginator->items())
                    ->map(fn (SeerahEvent $event) => $this->mapTimelineEvent($event, $locale))
                    ->values()
                    ->all();

                return [
                    'items' => $items,
                    'total' => $paginator->total(),
                    'currentPage' => $paginator->currentPage(),
                    'lastPage' => $paginator->lastPage(),
                    'categoryCounts' => $this->seerahEventService->countPublicByCategory($locale),
                    'facets' => $this->seerahEventService->publicFilterFacets($locale),
                ];
            } catch (Throwable $e) {
                report($e);

                return [
                    'items' => [], 'total' => 0, 'currentPage' => 1, 'lastPage' => 1,
                    'categoryCounts' => [], 'facets' => ['pivotal' => 0, 'disputed' => 0, 'importance' => []],
                ];
            }
        });

        // Rebuild the paginator instance for the view component (cache stores plain arrays only).
        $events = new LengthAwarePaginator(
            items: $payload['items'],
            total: $payload['total'],
            perPage: $perPage,
            currentPage: $payload['currentPage'],
            options: [
                'path' => $request->url(),
                'query' => $request->query(),
            ],
        );

        return view('public.seerah.events', [
            'events' => $events,
            'categoryPills' => $this->buildCategoryPills($payload['categoryCounts'], $locale, $filters),
            'milestoneOptions' => $milestones->map(fn (SeerahMilestone $milestone) => [
                'value' => $milestone->slug,
                'label' => $milestone->getTranslation('title', $locale) ?? $milestone->slug,
            ])->all(),
            'importanceOptions' => collect(EventImportance::cases())
                ->map(fn (EventImportance $case) => [
                    'value' => $case->value,
                    'label' => $locale === 'ar' ? $case->labelArabic() : $case->label(),
                    'count' => $payload['facets']['importance'][$case->value] ?? 0,
                ])
                ->filter(fn (array $option) => $option['count'] > 0)
                ->values()
                ->all(),
            'facets' => $payload['facets'],
            'filters' => $filters,
            'filterUrl' => fn (array $overrides = []) => $this->eventsUrl($filters, $overrides),
            'activeChips' => $this->buildActiveFilterChips($filters, $locale, $milestones),
            'searchQuery' => $filters['search'],
            'activeCategory' => $filters['type'] ?: 'all',
            'totalEvents' => $payload['total'],
        ]);
    }

    /**
     * @param  array<int, string>  $allowed
     */
    private function whitelisted(mixed $value, array $allowed): string
    {
        $value = is_string($value) ? trim($value) : '';

        return in_array($value, $allowed, true) ? $value : '';
    }

    /**
     * Directory URL carrying the current filter set, with overrides applied.
     * Any change resets to page 1 — page 3 of the old filter is not page 3 of the new one.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<string, mixed>  $overrides
     */
    private function eventsUrl(array $filters, array $overrides = []): string
    {
        $query = array_merge([
            'q' => $filters['search'],
            'category' => $filters['type'],
            'milestone' => $filters['milestone'],
            'importance' => $filters['importance'],
            'pivotal' => $filters['pivotal'] ? 1 : null,
            'disputed' => $filters['disputed'] ? 1 : null,
            'sort' => $filters['sort'] === 'desc' ? 'desc' : null,
        ], $overrides);

        return route('public.seerah.events', array_filter($query, fn ($value) => $value !== null && $value !== '' && $value !== false));
    }

    /**
     * One removable chip per active filter — a filter you cannot see is a filter
     * you cannot undo.
     *
     * @param  array<string, mixed>  $filters
     * @param  Collection<int, SeerahMilestone>  $milestones
     * @return array<int, array{label: string, url: string}>
     */
    private function buildActiveFilterChips(array $filters, string $locale, $milestones): array
    {
        $chips = [];

        if ($filters['search'] !== '') {
            $chips[] = ['label' => '«'.$filters['search'].'»', 'url' => $this->eventsUrl($filters, ['q' => null])];
        }

        if ($filters['type'] !== '') {
            $type = $this->eventTypeService->getForDropdown()->firstWhere('slug', $filters['type']);
            $chips[] = [
                'label' => $type?->getTranslation('name', $locale) ?? $filters['type'],
                'url' => $this->eventsUrl($filters, ['category' => null]),
            ];
        }

        if ($filters['milestone'] !== '') {
            $milestone = $milestones->firstWhere('slug', $filters['milestone']);
            $chips[] = [
                'label' => $milestone?->getTranslation('title', $locale) ?? $filters['milestone'],
                'url' => $this->eventsUrl($filters, ['milestone' => null]),
            ];
        }

        if ($filters['importance'] !== '') {
            $case = EventImportance::tryFrom($filters['importance']);
            $chips[] = [
                'label' => $case ? ($locale === 'ar' ? $case->labelArabic() : $case->label()) : $filters['importance'],
                'url' => $this->eventsUrl($filters, ['importance' => null]),
            ];
        }

        if ($filters['pivotal']) {
            $chips[] = ['label' => __('seerah.public.pivotal_event'), 'url' => $this->eventsUrl($filters, ['pivotal' => null])];
        }

        if ($filters['disputed']) {
            $chips[] = ['label' => __('seerah.public.dispute_badge'), 'url' => $this->eventsUrl($filters, ['disputed' => null])];
        }

        return $chips;
    }

    /**
     * Single Seerah event — immersive timeline style by default, or the
     * article style when requested via ?view=article.
     */
    public function event(Request $request, string $slug): View
    {
        $locale = app()->getLocale();

        // Whitelist the style variant; anything else falls back to the timeline style.
        $viewStyle = $request->query('view') === 'article' ? 'article' : 'timeline';

        $payload = Cache::remember(
            SeerahCacheKeys::event($slug, $locale),
            1800,
            function () use ($slug, $locale): ?array {
                try {
                    $page = $this->seerahEventService->getPublicEventPage($slug, $locale);

                    return [
                        'event' => $this->mapEventDetail($page['event'], $locale),
                        'prev' => $page['prev'] ? $this->mapEventNavigation($page['prev'], $locale) : null,
                        'next' => $page['next'] ? $this->mapEventNavigation($page['next'], $locale) : null,
                        'related' => $page['related']
                            ->map(fn (SeerahEvent $event) => $this->mapTimelineEvent($event, $locale))
                            ->values()
                            ->all(),
                    ];
                } catch (Throwable $e) {
                    report($e);

                    return null;
                }
            },
        );

        if ($payload === null) {
            abort(404);
        }

        $related = Cache::remember(
            SeerahCacheKeys::related($slug, $locale),
            1800,
            fn (): array => $payload['related'],
        );

        $template = $viewStyle === 'timeline' ? 'public.seerah.event-timeline' : 'public.seerah.event';

        return view($template, [
            'event' => $payload['event'],
            'related' => $related,
            'prev' => $payload['prev'],
            'next' => $payload['next'],
            // Milestone shells for the breadcrumb navigator modal.
            'journeyNav' => $this->cachedJourneyPayload($locale)['journey'],
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
            'description' => excerpt_sentence($event->getTranslation('description', $locale), PHP_INT_MAX),
            'type' => $type?->slug,
            'typeLabel' => $type?->getTranslation('name', $locale) ?? '',
            'icon' => $type?->icon ?? 'circle',
            'gradient' => $type?->gradient ?? 'from-slate-400 to-slate-500',
            'phase' => $phase?->value,
            'hijriDate' => $event->hijri_date,
            'hijriYear' => $event->hijri_year,
            'hijriLabel' => $this->hijriYearLabel($event->hijri_year),
            'gregorianYear' => $event->gregorian_year,
            'location' => $event->location,
            'isPivotal' => (bool) $event->is_pivotal,
            'isFeatured' => (bool) $event->is_featured,
            // Marker only on cards; the note itself is read on the event page.
            'hasDispute' => filled($event->getTranslation('dispute_note', $locale)),
            'url' => route('public.seerah.event', $event->slug),
        ];
    }

    /**
     * "3 هـ" / "13 ق.هـ" style label; negative years are before the Hijra.
     */
    private function hijriYearLabel(?int $year): ?string
    {
        if ($year === null) {
            return null;
        }

        return $year < 0
            ? abs($year).' '.__('seerah.public.before_hijra_suffix')
            : $year.' '.__('seerah.public.hijri_suffix');
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

        $articleLocale = $event->article?->locales->firstWhere('locale', $locale);

        // Coordinates come from the event when it has its own, otherwise from
        // the canonical place it resolves to. Before the locations module only
        // 2 of 578 events carried coordinates, so 576 event pages rendered this
        // map region blank; the fallback is what fills it.
        $coordinates = $event->resolvedCoordinates();
        $latitude = $coordinates['latitude'] ?? null;
        $longitude = $coordinates['longitude'] ?? null;

        $place = $event->relationLoaded('place') ? $event->place : null;
        $mapUrl = null;

        if ($latitude !== null && $longitude !== null) {
            $mapUrl = 'https://www.openstreetmap.org/export/embed.html?'.http_build_query([
                'bbox' => implode(',', [$longitude - 0.04, $latitude - 0.04, $longitude + 0.04, $latitude + 0.04]),
                'layer' => 'mapnik',
                'marker' => "{$latitude},{$longitude}",
            ], '', '&', PHP_QUERY_RFC3986);
        }

        $milestone = $event->milestone;

        return [
            'slug' => $event->slug,
            'title' => $event->getTranslation('title', $locale) ?? __('seerah.public.untitled_event'),
            'description' => $event->getTranslation('description', $locale),
            // Numbers the bare ayah marks inside stored verse embeds and strips
            // footer emoji — see enhance_rich_embeds(). Runs once per cache fill.
            'content' => enhance_rich_embeds($event->getTranslation('content', $locale)),
            'milestone' => $milestone ? [
                'slug' => $milestone->slug,
                'title' => $milestone->getTranslation('title', $locale) ?? '',
                'url' => route('public.seerah').'#milestone-'.$milestone->slug,
            ] : null,
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
            // `location` is the historian's descriptive note and is unchanged.
            // `place` is the canonical, localized place name — they are shown
            // together, not one instead of the other.
            'location' => $event->location,
            'place' => $place ? [
                'slug' => $place->slug,
                'name' => $place->name($locale),
                'type' => $place->type->value,
            ] : null,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'mapUrl' => $mapUrl,
            'featuredImage' => $this->publicImageUrl($event->featured_image),
            'gallery' => collect($event->gallery ?? [])
                ->filter(fn ($image): bool => is_string($image) && trim($image) !== '')
                ->map(fn (string $image): string => $this->publicImageUrl($image))
                ->filter()
                ->values()
                ->all(),
            'relatedArticle' => $articleLocale ? [
                'title' => $articleLocale->title,
                'url' => route('public.articles.show', $articleLocale->slug),
            ] : null,
            'isPivotal' => (bool) $event->is_pivotal,
            'importance' => $event->importance?->value,
            'importanceLabel' => $event->importance
                ? ($locale === 'ar' ? $event->importance->labelArabic() : $event->importance->label())
                : null,
            // Dating dispute carried by the reference chronology («وقيل…»).
            'disputeNote' => $event->getTranslation('dispute_note', $locale),
            'viewsCount' => (int) $event->views_count,
            'statistics' => is_array($event->statistics) ? $event->statistics : [],
            'characters' => $characters,
            'timeline' => $timeline,
            'lessons' => $event->lessonsFor($locale),
            'sources' => $event->sourcesFor($locale),
            'url' => route('public.seerah.event', $event->slug),
        ];
    }

    /**
     * @return array{title: string, url: string}
     */
    private function mapEventNavigation(SeerahEvent $event, string $locale): array
    {
        return [
            'title' => $event->getTranslation('title', $locale) ?? __('seerah.public.untitled_event'),
            'url' => route('public.seerah.event', $event->slug),
        ];
    }

    private function publicImageUrl(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        return Str::startsWith($path, ['http://', 'https://'])
            ? $path
            : Storage::disk('public')->url($path);
    }

    /**
     * Build category filter pills from event-type counts, ordered by the EventType enum.
     *
     * @param  array<string, int>  $counts
     * @return array<int, array<string, mixed>>
     */
    private function buildCategoryPills(array $counts, string $locale, array $filters): array
    {
        $pills = [[
            'value' => 'all',
            'label' => __('seerah.public.filter_all'),
            'icon' => 'layout-grid',
            'count' => array_sum($counts),
            'url' => $this->eventsUrl($filters, ['category' => null]),
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
                // Picking a type keeps the other filters — pills are not a reset button.
                'url' => $this->eventsUrl($filters, ['category' => $type->slug]),
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
