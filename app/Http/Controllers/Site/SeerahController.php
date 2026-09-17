<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Domain\Seerah\Enums\EventEra;
use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Seerah\Models\SeerahMilestone;
use App\Domain\Seerah\Services\SeerahCacheKeys;
use App\Domain\Seerah\Services\SeerahEventService;
use App\Domain\Seerah\Services\SeerahEventTypeService;
use App\Domain\Seerah\Services\SeerahMilestoneService;
use App\Domain\Shared\Contracts\MediaReference;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
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
                            'disputed' => array_sum(array_column($journey, 'disputedCount')),
                        ],
                    ];
                } catch (Throwable $e) {
                    report($e);

                    return ['journey' => [], 'stats' => ['milestones' => 0, 'events' => 0, 'span' => '', 'disputed' => 0]];
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
                    'order' => $milestone->order,
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
                    // Surfaced on the era card itself: buried 6000px down inside a
                    // lazy-loaded row, a marker is not findable.
                    'disputedCount' => $milestone->events
                        ->filter(fn (SeerahEvent $event): bool => filled($event->getTranslation('dispute_note', $locale)))
                        ->count(),
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
            'era' => $this->whitelisted($request->query('era'), array_column(EventEra::cases(), 'value')),
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
                    'categoryCounts' => [], 'facets' => ['disputed' => 0],
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
            'eraOptions' => array_map(fn (EventEra $case): array => [
                'value' => $case->value,
                'label' => $locale === 'ar' ? $case->labelArabic() : $case->label(),
            ], EventEra::cases()),
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
            'era' => $filters['era'],
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

        if ($filters['era'] !== '') {
            $case = EventEra::from($filters['era']);
            $chips[] = [
                'label' => $locale === 'ar' ? $case->labelArabic() : $case->label(),
                'url' => $this->eventsUrl($filters, ['era' => null]),
            ];
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
                        'event' => $this->mapEventDetail($page['event'], $page['media'], $locale),
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

        return [
            'slug' => $event->slug,
            'title' => $event->getTranslation('title', $locale) ?? __('seerah.public.untitled_event'),
            'description' => excerpt_sentence($event->getTranslation('description', $locale), PHP_INT_MAX),
            'type' => $type?->slug,
            'typeLabel' => $type?->getTranslation('name', $locale) ?? '',
            'icon' => $type?->icon ?? 'circle',
            'gradient' => $type?->gradient ?? 'from-slate-400 to-slate-500',
            'era' => $event->era?->value,
            'hijriDate' => $event->formattedHijriDate($locale),
            'hijriYear' => $event->hijri_year,
            'hijriLabel' => $event->hijri_year === null ? null : SeerahMilestone::hijriYearLabel($event->hijri_year),
            'gregorianYear' => $event->gregorian_date?->year,
            'location' => $event->relationLoaded('place') ? $event->place?->name($locale) : null,
            'image' => $event->cardImageUrl(),
            // Marker only on cards; the note itself is read on the event page.
            'hasDispute' => filled($event->getTranslation('dispute_note', $locale)),
            'url' => route('public.seerah.event', $event->slug),
        ];
    }

    /**
     * Map an event model to the full detail payload.
     *
     * @param  array<int, MediaReference>  $media
     * @return array<string, mixed>
     */
    private function mapEventDetail(SeerahEvent $event, array $media, string $locale): array
    {
        $type = $event->type;
        $era = $event->era;

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
            'era' => $era?->value,
            'eraLabel' => $era ? ($locale === 'ar' ? $era->labelArabic() : $era->label()) : null,
            'hijriDate' => $event->formattedHijriDate($locale),
            'hijriYear' => $event->hijri_year,
            'gregorianDate' => $event->gregorian_date?->toDateString(),
            'gregorianYear' => $event->gregorian_date?->year,
            'place' => $place ? [
                'slug' => $place->slug,
                'name' => $place->name($locale),
                'type' => $place->type->value,
            ] : null,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'mapUrl' => $mapUrl,
            'coverImage' => $event->pageImageUrl(),
            'gallery' => $event->gallery
                ->map(fn ($item): array => [
                    'url' => Storage::disk('public')->url($item->image),
                    'caption' => $item->captionFor($locale),
                ])
                ->values()
                ->all(),
            // Only files with a public key can be streamed to visitors.
            'media' => array_values(array_map(
                fn (MediaReference $item): array => ['title' => $item->title, 'type' => $item->type, 'url' => $item->url],
                array_filter($media, fn (MediaReference $item): bool => $item->url !== null),
            )),
            // Dating dispute carried by the reference chronology («وقيل…»).
            'disputeNote' => $event->getTranslation('dispute_note', $locale),
            'details' => $event->metadataFor($locale),
            'statistics' => is_array($event->statistics) ? $event->statistics : [],
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

    /**
     * Build category filter pills from event-type counts, in the type dropdown's order.
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
}
