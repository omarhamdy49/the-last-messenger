<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Domain\Quran\Enums\ResourceKind;
use App\Domain\Quran\Models\Playlist;
use App\Domain\Quran\Models\PlaylistItem;
use App\Domain\Quran\Models\QuranResource;
use App\Domain\Quran\Repositories\PlaylistRepository;
use App\Domain\Quran\Services\PlaylistCard;
use App\Domain\Quran\Services\PlaylistPlayRecorder;
use App\Domain\Quran\Services\PlaylistService;
use App\Domain\Quran\Services\QuranCacheKeys;
use App\Domain\Quran\Services\ReaderService;
use App\Domain\Quran\Services\ResourceService;
use App\Domain\Shared\Enums\FavorableType;
use App\Domain\Shared\Services\FavoriteService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\PreviewPlaylistRequest;
use App\Http\Requests\Site\StorePlaylistRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Visitor-built reciter playlists: public gallery, 3-step wizard, playlist
 * page, owner CRUD, and the play counter. Ownership and visibility are
 * enforced by the repository lookups (404, never 403).
 */
final class QuranPlaylistController extends Controller
{
    private const PER_USER_CAP = 20;

    public function __construct(
        private readonly PlaylistRepository $repo,
        private readonly PlaylistService $service,
        private readonly FavoriteService $favorites,
        private readonly PlaylistPlayRecorder $plays,
        private readonly ReaderService $reader,
        private readonly ResourceService $resources,
    ) {}

    public function index(Request $request): View
    {
        $locale = app()->getLocale();
        $search = trim((string) $request->query('q', ''));
        $sort = $request->query('sort') === 'plays' ? 'plays' : 'latest';

        $featured = Cache::remember(QuranCacheKeys::playlistScoped('featured:'.$locale), 600, fn (): array => $this->repo->featured(1)
            ->map(fn (Playlist $p) => PlaylistCard::from($p, $locale))->all());
        $totals = Cache::remember(QuranCacheKeys::playlistScoped('totals'), 600, fn (): array => [
            'public' => Playlist::query()->where('is_public', true)->count(),
            'plays' => (int) Playlist::query()->where('is_public', true)->sum('plays_count'),
            'reciters' => PlaylistItem::query()->whereNotNull('resource_id')
                ->whereHas('playlist', fn ($q) => $q->where('is_public', true))
                ->distinct()->count('resource_id'),
        ]);

        $playlists = $this->repo->gallery($search ?: null, $sort, $request->user()?->id);
        $playlists->getCollection()->transform(fn (Playlist $p) => PlaylistCard::from($p, $locale));

        return view('public.quran.playlists.index', [
            'playlists' => $playlists,
            'featured' => $featured[0] ?? null,
            'totals' => $totals,
            'search' => $search,
            'sort' => $sort,
        ]);
    }

    public function create(Request $request): View
    {
        return view('public.quran.playlists.wizard', $this->wizardProps($request, null));
    }

    public function edit(Request $request, string $slug): View
    {
        $playlist = $this->repo->findOwnedBySlug($slug, $request->user()->id) ?? abort(404);

        return view('public.quran.playlists.wizard', $this->wizardProps($request, $playlist));
    }

    public function preview(PreviewPlaylistRequest $request): JsonResponse
    {
        return response()->json(
            $this->service->assign($request->profileIds(), $request->surahIds(), $request->keep())->toArray(),
        );
    }

    public function store(StorePlaylistRequest $request): JsonResponse|RedirectResponse
    {
        if ($this->repo->countForUser($request->user()->id) >= self::PER_USER_CAP) {
            return $this->fail($request, __('quran.playlists.cap_reached', ['max' => self::PER_USER_CAP]), 'name');
        }
        $playlist = $this->service->create($request->user()->id, $request->toData());

        return $this->done($request, $playlist, __('quran.playlists.created'), 201);
    }

    public function update(StorePlaylistRequest $request, string $slug): JsonResponse|RedirectResponse
    {
        $playlist = $this->repo->findOwnedBySlug($slug, $request->user()->id) ?? abort(404);
        $this->service->update($playlist, $request->toData());

        return $this->done($request, $playlist, __('quran.playlists.updated'));
    }

    public function show(Request $request, string $slug): View
    {
        $playlist = $this->repo->findBySlugVisibleTo($slug, $request->user()?->id) ?? abort(404);
        $locale = app()->getLocale();

        return view('public.quran.playlists.show', [
            'playlist' => $playlist,
            'card' => PlaylistCard::from($playlist, $locale),
            'tracks' => $this->service->tracks($playlist, $locale),
            'isOwner' => $request->user()?->id === $playlist->user_id,
        ]);
    }

    public function visibility(Request $request, string $slug): JsonResponse|RedirectResponse
    {
        $playlist = $this->repo->findOwnedBySlug($slug, $request->user()->id) ?? abort(404);
        $on = $request->boolean('is_public');
        $this->service->setPublic($playlist, $on);

        return $request->expectsJson()
            ? response()->json(['success' => true, 'is_public' => $on, 'message' => __($on ? 'quran.playlists.now_public' : 'quran.playlists.now_private')])
            : back()->with('success', __($on ? 'quran.playlists.now_public' : 'quran.playlists.now_private'));
    }

    public function destroy(Request $request, string $slug): JsonResponse|RedirectResponse
    {
        $playlist = $this->repo->findOwnedBySlug($slug, $request->user()->id) ?? abort(404);
        $this->service->delete($playlist);

        return $request->expectsJson()
            ? response()->json(['success' => true, 'redirect' => route('public.profile').'#playlists'])
            : redirect()->to(route('public.profile').'#playlists')->with('success', __('quran.playlists.deleted'));
    }

    public function plays(Request $request, string $slug): JsonResponse
    {
        $playlist = $this->repo->findBySlugVisibleTo($slug, $request->user()?->id) ?? abort(404);
        $this->plays->record($playlist, $request);

        return response()->json(['success' => true]);
    }

    /**
     * The playable audio for one (surah, recording): a redirect for the wizard's
     * preview play, or `{url}` as JSON for the player's in-place surah stepping
     * (so the loaded track carries the real file URL, same as the reader).
     */
    public function audio(Request $request, int $surah, int $recording): JsonResponse|RedirectResponse
    {
        $url = $this->service->audioUrl($surah, $recording) ?? abort(404);

        return $request->expectsJson()
            ? response()->json(['url' => $url])
            : redirect()->away($url);
    }

    // ------------------------------------------------------------------ helpers

    /**
     * @return array<string, mixed>
     */
    private function wizardProps(Request $request, ?Playlist $playlist): array
    {
        $locale = app()->getLocale();

        $profiles = $this->resources->active(ResourceKind::CHAPTER_RECITER)
            ->map(fn (QuranResource $r): array => [
                'id' => $r->id,
                'name' => $r->displayName($locale),
                'image' => PlaylistCard::imageUrl($r->image),
                'country' => null,
                'flag' => null,
                'styles' => array_values(array_filter([$r->style?->label($locale), $r->qirat])),
                'recordings' => [['id' => $r->id, 'label' => trim(($r->style?->label($locale) ?? '').' '.($r->qirat ?? ''))]],
            ])->values()->all();

        $surahs = collect($this->reader->chapterList($locale))
            ->map(fn (array $c): array => ['id' => $c['number'], 'name' => $locale === 'ar' ? $c['nameAr'] : $c['nameEn'], 'verses' => $c['verses']])
            ->all();

        $initial = $playlist ? [
            'slug' => $playlist->slug,
            'name' => $playlist->name,
            'description' => $playlist->description,
            'scope' => $playlist->scope,
            'isPublic' => $playlist->is_public,
            'profileIds' => $playlist->reciters()->pluck('id')->all(),
            'surahIds' => $playlist->items->pluck('chapter_id')->all(),
            'items' => $playlist->items->pluck('resource_id', 'chapter_id')->all(),
        ] : null;

        return [
            'profiles' => $profiles,
            'favoriteIds' => $this->favorites->favoritedContentIds($request->user(), FavorableType::RECITER),
            'surahs' => $surahs,
            'initial' => $initial,
            'playlist' => $playlist,
            'count' => $this->repo->countForUser($request->user()->id),
            'cap' => self::PER_USER_CAP,
        ];
    }

    private function done(Request $request, Playlist $playlist, string $message, int $status = 200): JsonResponse|RedirectResponse
    {
        $url = route('public.quran.playlists.show', $playlist->slug);

        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => $message, 'redirect' => $url], $status)
            : redirect()->to($url)->with('success', $message);
    }

    private function fail(Request $request, string $message, string $field): JsonResponse|RedirectResponse
    {
        return $request->expectsJson()
            ? response()->json(['success' => false, 'message' => $message, 'errors' => [$field => [$message]]], 422)
            : back()->withErrors([$field => $message])->withInput();
    }
}
