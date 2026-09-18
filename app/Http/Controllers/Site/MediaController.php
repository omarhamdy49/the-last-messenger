<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Domain\Media\Enums\ProgramCategory;
use App\Domain\Media\Models\LiveChannel;
use App\Domain\Media\Models\MediaEpisode;
use App\Domain\Media\Models\MediaProgram;
use App\Domain\Media\Services\LiveChannelService;
use App\Domain\Media\Services\MediaEpisodeService;
use App\Domain\Media\Services\MediaProgramService;
use App\Domain\Quran\Services\PublicReciterService;
use App\Domain\Shared\Enums\FavorableType;
use App\Domain\Shared\Services\FavoriteService;
use App\Domain\Shared\Services\InteractionService;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Public Media / Programs section (Netflix-style hub + episode player).
 *
 * All data flows through the Media domain services (cached repositories).
 * Models are mapped to plain arrays here so Blade never triggers lazy loads.
 */
final class MediaController extends Controller
{
    /** Category gradient fallbacks for programs without an uploaded poster/banner. */
    private const CATEGORY_GRADIENTS = [
        'seerah' => 'from-[#0056b3] via-[#1a6e57] to-[#218838]',
        'quran' => 'from-emerald-800 via-emerald-600 to-teal-500',
        'hadith' => 'from-indigo-900 via-blue-700 to-sky-600',
        'kids' => 'from-amber-500 via-orange-500 to-rose-500',
        'documentary' => 'from-slate-800 via-slate-600 to-slate-500',
    ];

    public function __construct(
        private readonly MediaProgramService $programService,
        private readonly MediaEpisodeService $episodeService,
        private readonly LiveChannelService $liveChannelService,
        private readonly InteractionService $interactionService,
        private readonly FavoriteService $favoriteService,
    ) {}

    /**
     * Programs hub: hero featured carousel, sticky category pills,
     * Continue Watching (auth), Trending and per-category rows.
     */
    public function index(Request $request): View
    {
        try {
            $heroSlides = $this->programService->getFeaturedForHero(5)
                ->map(fn (MediaProgram $program): array => $this->mapProgramCard($program, withDescription: true))
                ->values()->all();

            $featured = $this->programService->getFeatured(12)
                ->map(fn (MediaProgram $program): array => $this->mapProgramCard($program))
                ->values()->all();

            // Real 7-day watch data (falls back to the manual flag when cold).
            $trending = $this->programService->getTrending(12)
                ->map(fn (MediaProgram $program): array => $this->mapProgramCard($program))
                ->values()->all();

            $popular = $this->programService->getPopular(10)
                ->filter(fn (MediaProgram $program): bool => $program->views_count > 0)
                ->map(fn (MediaProgram $program): array => $this->mapProgramCard($program))
                ->values()->all();

            // Dedupe the editorial rows (featured > trending > popular): with a
            // small catalog every flag matches the same programs and the page
            // repeats identical rows. A row emptied by the dedupe disappears.
            // Category rows are exempt — they are the browsing taxonomy.
            $seenIds = array_column($featured, 'id');
            $trending = array_values(array_filter(
                $trending,
                static fn (array $card): bool => ! in_array($card['id'], $seenIds, true),
            ));
            $seenIds = [...$seenIds, ...array_column($trending, 'id')];
            $popular = array_values(array_filter(
                $popular,
                static fn (array $card): bool => ! in_array($card['id'], $seenIds, true),
            ));

            $liveChannels = $this->liveChannelService->getPublishedForRow(12)
                ->map(fn (LiveChannel $channel): array => $this->mapLiveCard($channel))
                ->values()->all();

            // Featured Quran reciters teaser row (cross-section discovery). The
            // reciter catalogue is one Quran resource table now, so the directory
            // service owns the picking and this only reshapes its cards.
            $locale = app()->getLocale();
            $reciters = array_map(static fn (array $card): array => [
                'name' => $locale === 'ar' ? $card['nameAr'] : ($card['nameEn'] ?: $card['nameAr']),
                'style' => $card['styleLabel'],
                'image' => $card['profileImage'],
                'country_flag' => $card['countryFlag'],
                'country_name' => $card['countryName'],
                'url' => route('public.quran.reciter', $card['slug']),
            ], app(PublicReciterService::class)->listing($locale)['featured']);

            // Only categories flagged is_featured (admin-managed) load on the list.
            $featuredCategoryKeys = $this->programService->getFeaturedCategoryKeys();

            // One query for every featured category's programs, grouped in PHP —
            // no per-category N+1. Iterate the featured keys to preserve the
            // admin-defined category order; skip categories with no programs.
            $groupedPrograms = $this->programService->getProgramsGroupedByCategories($featuredCategoryKeys, 12);

            $categoryRows = [];
            foreach ($featuredCategoryKeys as $categoryKey) {
                if (ProgramCategory::tryFrom($categoryKey) === null) {
                    continue;
                }
                $programs = $groupedPrograms->get($categoryKey);
                if ($programs === null || $programs->isEmpty()) {
                    continue;
                }
                $categoryRows[$categoryKey] = [
                    'label' => __('media.public.category_'.$categoryKey),
                    'programs' => $programs
                        ->map(fn (MediaProgram $program): array => $this->mapProgramCard($program))
                        ->values()->all(),
                ];
            }
        } catch (Throwable $e) {
            report($e);
            $heroSlides = [];
            $featured = [];
            $trending = [];
            $popular = [];
            $liveChannels = [];
            $reciters = [];
            $categoryRows = [];
        }

        // Continue Watching is NOT rendered server-side: the hub page stays
        // per-user-free (safe for page/edge caching) and the section lazy-loads
        // through continueWatching() below for signed-in visitors.
        return view('public.media.index', [
            'heroSlides' => $heroSlides,
            'featured' => $featured,
            'trending' => $trending,
            'popular' => $popular,
            'liveChannels' => $liveChannels,
            'reciters' => $reciters,
            'categoryRows' => $categoryRows,
            'myListIds' => $this->resolveFavoritedProgramIds($request),
        ]);
    }

    /**
     * Continue Watching cards for the signed-in visitor (lazy-loaded by the
     * hub page after render, so the page itself carries no per-user markup).
     */
    public function continueWatching(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'items' => $this->resolveContinueWatching($request),
        ]);
    }

    /**
     * Program detail: banner, meta, description, episode list.
     */
    public function show(Request $request, string $slug): View
    {
        try {
            $program = $this->programService->getBySlug($slug);
        } catch (Throwable $e) {
            report($e);
            abort(404);
        }

        $episodes = $this->safeEpisodes($program->id);

        $episodeCards = $episodes
            ->map(fn (MediaEpisode $episode): array => $this->mapEpisodeCard($episode, $program->slug))
            ->values()->all();
        $episodeCards = $this->mergeProgress(
            $episodeCards,
            $this->safeProgressMap($request, array_column($episodeCards, 'id')),
        );

        return view('public.media.program', [
            'program' => $this->mapProgramDetail($program, $episodes->count()),
            'programIsSaved' => $this->resolveProgramIsSaved($request, $program->id),
            'episodes' => $episodeCards,
            'seasons' => $episodes
                ->pluck('season_number')
                ->unique()->sort()->values()->all(),
        ]);
    }

    /**
     * Episode player page — episode resolved by episode_number within program.
     */
    public function episode(Request $request, string $slug, int $episodeNumber): View
    {
        try {
            $program = $this->programService->getBySlug($slug);
        } catch (Throwable $e) {
            report($e);
            abort(404);
        }

        $season = max(1, (int) $request->query('season', 1));

        try {
            $episode = $this->episodeService->getEpisode($program->id, $season, $episodeNumber);
        } catch (Throwable $e) {
            report($e);
            abort(404);
        }

        $this->recordEpisodeView($request, $episode);

        $next = $this->episodeService->findNextEpisode($episode);
        $allEpisodes = $this->safeEpisodes($program->id);

        $moreEpisodes = $allEpisodes
            ->reject(fn (MediaEpisode $other): bool => $other->id === $episode->id)
            ->map(fn (MediaEpisode $other): array => $this->mapEpisodeCard($other, $program->slug))
            ->values()->all();

        // One query covers the resume position and the more-episodes bars.
        $progress = $this->safeProgressMap(
            $request,
            [(int) $episode->id, ...array_column($moreEpisodes, 'id')],
        );

        $current = $progress[(int) $episode->id] ?? null;
        $resumeSeconds = ($current !== null && ! $current['completed']) ? $current['seconds'] : 0;

        return view('public.media.episode', [
            'programIsSaved' => $this->resolveProgramIsSaved($request, $program->id),
            'program' => $this->mapProgramDetail($program, $allEpisodes->count()),
            'episode' => $this->mapEpisodeDetail($episode, $program),
            'resumeSeconds' => $resumeSeconds,
            'nextEpisode' => $next ? $this->mapEpisodeCard($next, $program->slug) : null,
            'moreEpisodes' => $this->mergeProgress($moreEpisodes, $progress),
        ]);
    }

    /**
     * Paginated list of all published live channels ("view all" from the hub row).
     */
    public function liveIndex(): View
    {
        $channels = $this->liveChannelService->paginatePublished(24);
        $channels->through(fn (LiveChannel $channel): array => $this->mapLiveCard($channel));

        return view('public.media.live-index', ['channels' => $channels]);
    }

    /**
     * Live channel page — player + info + related channels (no chat).
     */
    public function live(Request $request, string $slug): View
    {
        try {
            $channel = $this->liveChannelService->getPublishedBySlug($slug);
        } catch (Throwable $e) {
            report($e);
            abort(404);
        }

        $related = $this->liveChannelService->getRelated($channel->id, 6)
            ->map(fn (LiveChannel $other): array => $this->mapLiveCard($other))
            ->values()->all();

        return view('public.media.live', [
            'channel' => $this->mapLiveDetail($channel),
            'related' => $related,
        ]);
    }

    // ------------------------------------------------------------------
    // Mapping helpers (keep Blade free of model access / lazy loads)
    // ------------------------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    private function mapProgramCard(MediaProgram $program, bool $withDescription = false): array
    {
        $category = $program->category->value;

        return [
            'id' => (int) $program->id,
            'slug' => $program->slug,
            'url' => route('public.media.program', $program->slug),
            'title' => (string) ($program->trans('title') ?? $program->slug),
            'description' => $withDescription ? (string) ($program->trans('description') ?? '') : null,
            'category' => $category,
            'category_label' => __('media.public.category_'.$category),
            'year' => $program->year,
            'rating' => (float) $program->rating,
            'maturity_label' => __('media.public.maturity_'.$program->maturity_rating->value),
            'episodes_count' => (int) ($program->episodes_count ?? 0),
            'is_trending' => (bool) $program->is_trending,
            // Artwork comes from the YouTube CDN unless an admin uploaded a
            // real file. When it is the 480px CDN fallback, offer maxres
            // (1280px) first for the full-bleed hero — the view falls back via
            // onerror when the video has no HD upload.
            'poster' => $program->poster_thumb_url,
            'banner' => $program->banner_url ?? $program->poster_url,
            'banner_hq' => $program->getFirstMedia('banner') === null
                ? $program->youtubeMaxResThumbnailUrl()
                : null,
            'gradient' => self::CATEGORY_GRADIENTS[$category] ?? self::CATEGORY_GRADIENTS['documentary'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapProgramDetail(MediaProgram $program, int $episodesCount): array
    {
        $card = $this->mapProgramCard($program, withDescription: true);
        $card['host'] = $program->trans('host');
        $card['views_count'] = (int) $program->views_count;
        $card['episodes_count'] = $episodesCount;

        // SEO overrides — views fall back to "{title} | {app.name}" / description.
        $card['seo_title'] = $program->trans('seo_title');
        $card['seo_description'] = $program->trans('seo_description');
        $card['seo_keywords'] = $program->trans('seo_keywords');

        return $card;
    }

    /**
     * @return array<string, mixed>
     */
    private function mapEpisodeCard(MediaEpisode $episode, string $programSlug): array
    {
        return [
            'id' => (int) $episode->id,
            'duration_seconds' => (int) ($episode->duration_seconds ?? 0),
            'number' => (int) $episode->episode_number,
            'season' => (int) $episode->season_number,
            'title' => (string) ($episode->trans('title') ?? ''),
            'duration' => $episode->duration_formatted,
            // Lists/cards read the 400x225 'thumb' conversion (falls back to
            // the original, then the YouTube CDN).
            'thumbnail' => $episode->thumb_url ?? $this->youtubeThumbnail($episode->video_url),
            'has_video' => $episode->video_url !== null,
            'url' => route('public.media.episode', ['slug' => $programSlug, 'episode' => $episode->episode_number])
                .($episode->season_number > 1 ? '?season='.$episode->season_number : ''),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapEpisodeDetail(MediaEpisode $episode, MediaProgram $program): array
    {
        $card = $this->mapEpisodeCard($episode, $program->slug);
        $card['id'] = (int) $episode->id;
        $card['description'] = $episode->trans('description');
        $card['video_url'] = $episode->video_url;
        $card['video_id'] = $this->youtubeVideoId($episode->video_url);

        // The player poster renders near-full-width — use the saved HQ
        // original, not the card conversion.
        $card['thumbnail'] = $episode->getThumbnailUrl() ?? $this->youtubeThumbnail($episode->video_url);

        // When the thumbnail is still the 480px CDN fallback (nothing saved
        // yet), offer maxres (1280px) first — the view falls back via onerror
        // when maxres 404s.
        $isCdnThumbnail = $card['thumbnail'] !== null
            && (str_contains($card['thumbnail'], 'i.ytimg.com') || str_contains($card['thumbnail'], 'img.youtube.com'));
        $card['thumbnail_hq'] = ($isCdnThumbnail && $card['video_id'] !== null)
            ? "https://i.ytimg.com/vi/{$card['video_id']}/maxresdefault.jpg"
            : null;
        $card['views_count'] = (int) $episode->views_count;
        $card['transcript'] = $this->transcriptLines($episode->trans('transcript'));
        $card['seo_title'] = $episode->trans('seo_title');
        $card['seo_description'] = $episode->trans('seo_description');
        $card['seo_keywords'] = $episode->trans('seo_keywords');

        return $card;
    }

    /**
     * Live channel card (row + related list).
     *
     * @return array<string, mixed>
     */
    private function mapLiveCard(LiveChannel $channel): array
    {
        return [
            'id' => (int) $channel->id,
            'slug' => $channel->slug,
            'title' => (string) ($channel->trans('title') ?? $channel->slug),
            'status' => $channel->status->value,
            'is_live' => $channel->isLive(),
            'thumbnail' => $channel->thumbnail_url ?? $this->youtubeThumbnail($channel->streaming_url),
            'views_count' => (int) $channel->views_count,
            'url' => route('public.media.live', $channel->slug),
        ];
    }

    /**
     * Live channel detail for the single page.
     *
     * @return array<string, mixed>
     */
    private function mapLiveDetail(LiveChannel $channel): array
    {
        $card = $this->mapLiveCard($channel);
        $card['description'] = $channel->trans('description');
        $card['streaming_url'] = $channel->streaming_url;
        $card['embed_url'] = $channel->embed_url;

        return $card;
    }

    /**
     * Split a transcript into display lines; a leading "mm:ss" / "hh:mm:ss"
     * becomes a YouTube-style time chip.
     *
     * Transcripts authored before the WYSIWYG editor are plain text with
     * newlines; newer ones are HTML where each block element is one line.
     *
     * @return list<array{time: string|null, text: string}>
     */
    private function transcriptLines(?string $transcript): array
    {
        if ($transcript === null || trim($transcript) === '') {
            return [];
        }

        if (str_contains($transcript, '<')) {
            $transcript = preg_replace('/<(?:br|\/p|\/div|\/li|\/h[1-6])[^>]*>/i', "\n", $transcript);
            $transcript = html_entity_decode(strip_tags($transcript), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        if (trim($transcript) === '') {
            return [];
        }

        $lines = [];
        foreach (preg_split('/\r\n|\r|\n/', $transcript) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            if (preg_match('/^((?:\d{1,2}:)?\d{1,2}:\d{2})\s+(.+)$/u', $line, $m) === 1) {
                $lines[] = ['time' => $m[1], 'text' => $m[2]];
            } else {
                $lines[] = ['time' => null, 'text' => $line];
            }
        }

        return $lines;
    }

    /**
     * Continue Watching row for authenticated visitors (empty for guests).
     *
     * @return array<int, array<string, mixed>>
     */
    private function resolveContinueWatching(Request $request): array
    {
        $user = $request->user();
        if ($user === null) {
            return [];
        }

        try {
            return $this->episodeService->getContinueWatching($user->id, 10)
                ->filter(fn ($progress): bool => $progress->episode !== null && $progress->episode->program !== null)
                ->map(function ($progress): array {
                    $episode = $progress->episode;
                    $card = $this->mapEpisodeCard($episode, $episode->program->slug);
                    $card['program_title'] = (string) ($episode->program->trans('title') ?? '');
                    $card['seconds'] = (int) $progress->seconds;
                    $card['progress_percent'] = $episode->duration_seconds
                        ? min(100, (int) round($progress->seconds / $episode->duration_seconds * 100))
                        : 0;

                    return $card;
                })
                ->values()->all();
        } catch (Throwable $e) {
            report($e);

            return [];
        }
    }

    /**
     * The visitor's watch positions for a set of episode ids (empty for guests).
     *
     * @param  list<int>  $episodeIds
     * @return array<int, array{seconds: int, completed: bool}>
     */
    private function safeProgressMap(Request $request, array $episodeIds): array
    {
        $user = $request->user();
        if ($user === null || $episodeIds === []) {
            return [];
        }

        try {
            return $this->episodeService->getProgressForEpisodes($user->id, $episodeIds);
        } catch (Throwable $e) {
            report($e);

            return [];
        }
    }

    /**
     * Merge watch progress into episode cards; cards without progress are untouched.
     *
     * @param  array<int, array<string, mixed>>  $cards
     * @param  array<int, array{seconds: int, completed: bool}>  $progress
     * @return array<int, array<string, mixed>>
     */
    private function mergeProgress(array $cards, array $progress): array
    {
        if ($progress === []) {
            return $cards;
        }

        foreach ($cards as &$card) {
            $entry = $progress[$card['id']] ?? null;
            if ($entry === null) {
                continue;
            }

            $card['completed'] = $entry['completed'];
            $card['progress_percent'] = match (true) {
                $entry['completed'] => 100,
                $card['duration_seconds'] > 0 => min(100, (int) round($entry['seconds'] / $card['duration_seconds'] * 100)),
                default => 0,
            };
        }

        return $cards;
    }

    /**
     * Program ids the visitor saved to My List (empty for guests).
     *
     * @return list<int>
     */
    private function resolveFavoritedProgramIds(Request $request): array
    {
        $user = $request->user();
        if ($user === null) {
            return [];
        }

        try {
            return $this->favoriteService->favoritedContentIds($user, FavorableType::MEDIA);
        } catch (Throwable $e) {
            report($e);

            return [];
        }
    }

    /**
     * Whether the visitor already saved this program (false for guests).
     */
    private function resolveProgramIsSaved(Request $request, int $programId): bool
    {
        $user = $request->user();
        if ($user === null) {
            return false;
        }

        try {
            return $this->favoriteService->isFavorited($user, FavorableType::MEDIA, $programId);
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }

    /**
     * Best-effort per-user view interaction; views_count increments happen in the services.
     */
    private function recordEpisodeView(Request $request, MediaEpisode $episode): void
    {
        $user = $request->user();
        if ($user === null) {
            return;
        }

        try {
            $this->interactionService->recordView($user, $episode);
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function safeEpisodes(int $programId): Collection
    {
        try {
            return collect($this->episodeService->getByProgram($programId)->all());
        } catch (Throwable $e) {
            report($e);

            return collect();
        }
    }

    private function youtubeVideoId(?string $videoUrl): ?string
    {
        if ($videoUrl === null || preg_match('#youtube\.com/embed/([A-Za-z0-9_-]{11})#', $videoUrl, $m) !== 1) {
            return null;
        }

        return $m[1];
    }

    private function youtubeThumbnail(?string $videoUrl): ?string
    {
        $videoId = $this->youtubeVideoId($videoUrl);

        return $videoId !== null ? "https://img.youtube.com/vi/{$videoId}/hqdefault.jpg" : null;
    }
}
