<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Domain\Quran\Models\Playlist;
use App\Domain\Quran\Models\PlaylistItem;
use App\Domain\Quran\Models\Recitation;
use App\Domain\Quran\Models\Reciter;
use App\Domain\Quran\Models\ReciterProfile;
use App\Domain\Quran\Models\Surah;
use App\Domain\Quran\Repositories\PlaylistRepository;
use App\Domain\Quran\Services\PlaylistCard;
use App\Domain\Quran\Services\PlaylistPlayRecorder;
use App\Domain\Quran\Services\PlaylistService;
use App\Domain\Quran\Services\QuranCacheKeys;
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
            'reciters' => Reciter::query()
                ->whereIn('id', PlaylistItem::query()->select('reciter_id')->whereHas('playlist', fn ($q) => $q->where('is_public', true)))
                ->distinct()->count('profile_id'),
        ]);

        $playlists = $this->repo->gallery($search ?: null, $sort, $request->user());
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
        $playlist = $this->repo->findOwnedBySlug($slug, $request->user()) ?? abort(404);

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
        if ($this->repo->countForUser($request->user()) >= self::PER_USER_CAP) {
            return $this->fail($request, __('quran.playlists.cap_reached', ['max' => self::PER_USER_CAP]), 'name');
        }
        $playlist = $this->service->create($request->user(), $request->toData());

        return $this->done($request, $playlist, __('quran.playlists.created'), 201);
    }

    public function update(StorePlaylistRequest $request, string $slug): JsonResponse|RedirectResponse
    {
        $playlist = $this->repo->findOwnedBySlug($slug, $request->user()) ?? abort(404);
        $this->service->update($playlist, $request->toData());

        return $this->done($request, $playlist, __('quran.playlists.updated'));
    }

    public function show(Request $request, string $slug): View
    {
        $playlist = $this->repo->findBySlugVisibleTo($slug, $request->user()) ?? abort(404);
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
        $playlist = $this->repo->findOwnedBySlug($slug, $request->user()) ?? abort(404);
        $on = $request->boolean('is_public');
        $this->service->setPublic($playlist, $on);

        return $request->expectsJson()
            ? response()->json(['success' => true, 'is_public' => $on, 'message' => __($on ? 'quran.playlists.now_public' : 'quran.playlists.now_private')])
            : back()->with('success', __($on ? 'quran.playlists.now_public' : 'quran.playlists.now_private'));
    }

    public function destroy(Request $request, string $slug): JsonResponse|RedirectResponse
    {
        $playlist = $this->repo->findOwnedBySlug($slug, $request->user()) ?? abort(404);
        $this->service->delete($playlist);

        return $request->expectsJson()
            ? response()->json(['success' => true, 'redirect' => route('public.profile').'#playlists'])
            : redirect()->to(route('public.profile').'#playlists')->with('success', __('quran.playlists.deleted'));
    }

    public function plays(Request $request, string $slug): JsonResponse
    {
        $playlist = $this->repo->findBySlugVisibleTo($slug, $request->user()) ?? abort(404);
        $this->plays->record($playlist, $request);

        return response()->json(['success' => true]);
    }

    /**
     * Wizard preview-play: redirect to the playable audio for one (surah, recording).
     */
    public function audio(int $surah, int $recording): RedirectResponse
    {
        $url = Recitation::query()->playable()
            ->where('surah_id', $surah)->where('reciter_id', $recording)
            ->with('source:id,priority')->get(['id', 'source_id', 'audio_url'])
            ->sortBy(fn (Recitation $r) => $r->source->priority)
            ->first()?->audio_url ?? abort(404);

        return redirect()->away($url);
    }

    // ------------------------------------------------------------------ helpers

    /**
     * @return array<string, mixed>
     */
    private function wizardProps(Request $request, ?Playlist $playlist): array
    {
        $locale = app()->getLocale();

        $profiles = ReciterProfile::query()->visible()
            ->with([
                'translations:id,profile_id,locale,name',
                'recordings' => fn ($q) => $q->without('sources')->active()->recitationsOnly()
                    ->with('translations:id,reciter_id,locale,name')->orderBy('display_order')->orderBy('id'),
            ])
            ->orderBy('display_order')->orderBy('id')->get()
            ->map(fn (ReciterProfile $p): array => [
                'id' => $p->id,
                'name' => $p->displayName($locale),
                'image' => $p->profile_image_url,
                'country' => $p->country,
                'flag' => country_flag($p->country),
                'styles' => $p->recordings->pluck('style')->filter()->unique()->map(fn ($s) => __('quran.recordings.'.$s))->values()->all(),
                'recordings' => $p->recordings->map(fn ($r) => ['id' => $r->id, 'label' => $r->recordingLabel($locale)])->values()->all(),
            ])->values()->all();

        $surahs = Surah::query()->orderBy('id')->get(['id', 'name_ar', 'name_en', 'transliteration', 'verses_count'])
            ->map(fn (Surah $s): array => ['id' => $s->id, 'name' => $s->getDisplayName($locale), 'verses' => $s->verses_count])->all();

        $initial = $playlist ? [
            'slug' => $playlist->slug,
            'name' => $playlist->name,
            'description' => $playlist->description,
            'scope' => $playlist->scope,
            'isPublic' => $playlist->is_public,
            'profileIds' => $playlist->reciterProfiles()->pluck('id')->all(),
            'surahIds' => $playlist->items->pluck('surah_id')->all(),
            'items' => $playlist->items->pluck('reciter_id', 'surah_id')->all(),
        ] : null;

        return [
            'profiles' => $profiles,
            'favoriteIds' => $this->favorites->favoritedContentIds($request->user(), FavorableType::RECITER),
            'surahs' => $surahs,
            'initial' => $initial,
            'playlist' => $playlist,
            'count' => $this->repo->countForUser($request->user()),
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
