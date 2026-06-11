<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Domain\Media\Enums\ProgramCategory;
use App\Domain\Media\Models\MediaEpisode;
use App\Domain\Media\Models\MediaProgram;
use App\Domain\Media\Services\MediaEpisodeService;
use App\Domain\Media\Services\MediaProgramService;
use App\Domain\Shared\Enums\FavorableType;
use App\Domain\Shared\Services\FavoriteService;
use App\Domain\Shared\Services\InteractionService;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
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

            $trending = $this->programService->getTrendingFlagged(12)
                ->map(fn (MediaProgram $program): array => $this->mapProgramCard($program))
                ->values()->all();

            $popular = $this->programService->getPopular(10)
                ->filter(fn (MediaProgram $program): bool => $program->views_count > 0)
                ->map(fn (MediaProgram $program): array => $this->mapProgramCard($program))
                ->values()->all();

            // Only categories flagged is_featured (admin-managed) load on the list.
            $featuredCategoryKeys = $this->programService->getFeaturedCategoryKeys();

            $categoryRows = [];
            foreach ($featuredCategoryKeys as $categoryKey) {
                if (ProgramCategory::tryFrom($categoryKey) === null) {
                    continue;
                }
                $programs = $this->programService->getByCategory($categoryKey, 12);
                if ($programs->isEmpty()) {
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
            $categoryRows = [];
        }

        return view('public.media.index', [
            'heroSlides' => $heroSlides,
            'featured' => $featured,
            'trending' => $trending,
            'popular' => $popular,
            'categoryRows' => $categoryRows,
            'continueWatching' => $this->resolveContinueWatching($request),
            'categories' => $this->categoryPills(array_keys($categoryRows)),
            'myListIds' => $this->resolveFavoritedProgramIds($request),
        ]);
    }

    /**
     * Program detail: banner, meta, description, episode list.
     */
    public function show(Request $request, string $slug): View
    {
        try {
            $program = $this->programService->getBySlug($slug);
        } catch (Throwable) {
            abort(404);
        }

        $episodes = $this->safeEpisodes($program->id);

        return view('public.media.program', [
            'program' => $this->mapProgramDetail($program, $episodes->count()),
            'programIsSaved' => $this->resolveProgramIsSaved($request, $program->id),
            'episodes' => $episodes
                ->map(fn (MediaEpisode $episode): array => $this->mapEpisodeCard($episode, $program->slug))
                ->values()->all(),
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
        } catch (Throwable) {
            abort(404);
        }

        $season = max(1, (int) $request->query('season', 1));

        try {
            $episode = $this->episodeService->getEpisode($program->id, $season, $episodeNumber);
        } catch (Throwable) {
            abort(404);
        }

        $this->recordEpisodeView($request, $episode);

        $next = $this->episodeService->findNextEpisode($episode);
        $allEpisodes = $this->safeEpisodes($program->id);

        return view('public.media.episode', [
            'programIsSaved' => $this->resolveProgramIsSaved($request, $program->id),
            'program' => $this->mapProgramDetail($program, $allEpisodes->count()),
            'episode' => $this->mapEpisodeDetail($episode, $program),
            'nextEpisode' => $next ? $this->mapEpisodeCard($next, $program->slug) : null,
            'moreEpisodes' => $allEpisodes
                ->reject(fn (MediaEpisode $other): bool => $other->id === $episode->id)
                ->map(fn (MediaEpisode $other): array => $this->mapEpisodeCard($other, $program->slug))
                ->values()->all(),
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
            'poster' => $program->poster_url,
            'banner' => $program->banner_url ?? $program->poster_url,
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
            'number' => (int) $episode->episode_number,
            'season' => (int) $episode->season_number,
            'title' => (string) ($episode->trans('title') ?? ''),
            'duration' => $episode->duration_formatted,
            'thumbnail' => $episode->getThumbnailUrl() ?? $this->youtubeThumbnail($episode->video_url),
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
        $card['views_count'] = (int) $episode->views_count;
        $card['transcript'] = $this->transcriptLines($episode->trans('transcript'));

        return $card;
    }

    /**
     * Split a transcript into display lines; a leading "mm:ss" / "hh:mm:ss"
     * becomes a YouTube-style time chip.
     *
     * @return list<array{time: string|null, text: string}>
     */
    private function transcriptLines(?string $transcript): array
    {
        if ($transcript === null || trim($transcript) === '') {
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

    /**
     * Category filter pills: "All" plus one pill per category row that loaded
     * (featured categories with at least one program).
     *
     * @param  list<string>  $categoryKeys
     * @return array<int, array<string, string>>
     */
    private function categoryPills(array $categoryKeys): array
    {
        $pills = [['value' => 'all', 'label' => __('media.public.filter_all')]];

        foreach ($categoryKeys as $key) {
            $pills[] = ['value' => $key, 'label' => __('media.public.category_'.$key)];
        }

        return $pills;
    }
}
