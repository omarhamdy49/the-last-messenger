<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Domain\Seerah\Enums\EventPhase;
use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Seerah\Models\SeerahMilestone;
use App\Domain\Seerah\Services\SeerahEventService;
use App\Domain\Seerah\Services\SeerahEventTypeService;
use App\Domain\Seerah\Services\SeerahMilestoneService;
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
        private readonly SeerahMilestoneService $milestoneService,
    ) {}

    /**
     * Seerah hero-journey — milestones (الحِقَب) as story chapters along a spine
     * timeline, plus a flat "articles" mode. Fully server-rendered; Alpine handles
     * the mode toggle and milestone scroll-spy navigation.
     */
    public function index(): View
    {
        $locale = app()->getLocale();

        $payload = Cache::remember(
            "public_seerah_journey_{$locale}",
            1800,
            function () use ($locale): array {
                try {
                    $journey = $this->buildJourney($locale);

                    // Flat chronological list (all published events) for "articles" mode.
                    $paginator = $this->seerahEventService->getAllEvents(perPage: 500);
                    $articles = collect($paginator->items())
                        ->map(fn (SeerahEvent $event) => $this->mapTimelineEvent($event, $locale))
                        ->values()
                        ->all();

                    $totalEvents = array_sum(array_column($journey, 'eventCount'));

                    return [
                        'journey' => $journey,
                        'articles' => $articles,
                        'stats' => [
                            'milestones' => count($journey),
                            'events' => $totalEvents,
                            'articles' => count($articles),
                            'span' => $this->journeySpan($journey, $locale),
                        ],
                    ];
                } catch (Throwable $e) {
                    report($e);

                    return ['journey' => [], 'articles' => [], 'stats' => ['milestones' => 0, 'events' => 0, 'articles' => 0, 'span' => '']];
                }
            },
        );

        return view('public.seerah.index', [
            'journey' => $payload['journey'],
            'articles' => $payload['articles'],
            'stats' => $payload['stats'],
        ]);
    }

    /**
     * Build the milestone journey: each active milestone with its published events.
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildJourney(string $locale): array
    {
        return $this->milestoneService->getJourney()
            ->map(function (SeerahMilestone $milestone) use ($locale): array {
                $events = $milestone->events
                    ->map(fn (SeerahEvent $event) => $this->mapTimelineEvent($event, $locale))
                    ->values()
                    ->all();

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
                    'events' => $events,
                    'eventCount' => count($events),
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
