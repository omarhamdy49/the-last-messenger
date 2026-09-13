<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Quran;

use App\Domain\Quran\DTOs\PlaylistData;
use App\Domain\Quran\Models\Playlist;
use App\Domain\Quran\Models\Surah;
use App\Domain\Quran\Repositories\PlaylistRepository;
use App\Domain\Quran\Services\PlaylistService;
use App\Domain\Quran\Services\QuranCacheKeys;
use App\Domain\Shared\Services\AdminLoggerService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Quran\UpdatePlaylistRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Moderation of visitor-built reciter playlists: list, edit (rename, items,
 * visibility, featured), toggle, bulk unpublish, delete.
 */
final class PlaylistController extends Controller
{
    private const FILTERS = ['search', 'public', 'featured'];

    public function __construct(
        private readonly PlaylistRepository $repo,
        private readonly PlaylistService $service,
        private readonly AdminLoggerService $logger,
    ) {}

    public function index(Request $request): View
    {
        $filters = array_filter($request->only(self::FILTERS), 'filled');

        $stats = Playlist::query()
            ->selectRaw('COUNT(*) AS total, COALESCE(SUM(is_public), 0) AS public_count, COALESCE(SUM(is_featured), 0) AS featured, COALESCE(SUM(plays_count), 0) AS plays')
            ->first();

        return view('admin.playlists.index', [
            'playlists' => $this->repo->adminList($filters),
            'filters' => $filters,
            'stats' => $stats,
        ]);
    }

    public function edit(int $playlist): View
    {
        $model = Playlist::query()->with([
            'owner:id,name,email',
            'items.surah:id,name_ar,name_en,transliteration,verses_count',
            'items.reciter:id,profile_id,style,riwayah,slug',
            'items.reciter.profile:id',
            'items.reciter.profile.translations:id,profile_id,locale,name',
        ])->findOrFail($playlist);

        $locale = app()->getLocale();
        $coverage = $this->service->coverage($model->items->pluck('surah_id')->all());

        return view('admin.playlists.edit', [
            'playlist' => $model,
            // Rows the Alpine editor starts with; recordings for a newly added surah are fetched lazily.
            'rows' => $model->items->map(fn ($item) => [
                'surah' => $item->surah_id,
                'name' => $item->surah?->getDisplayName($locale) ?? (string) $item->surah_id,
                'verses' => $item->surah?->verses_count,
                'recording' => $item->reciter_id,
                'options' => $coverage[$item->surah_id] ?? [],
            ])->values()->all(),
            'surahs' => Surah::query()->orderBy('id')->get(['id', 'name_ar', 'name_en', 'verses_count'])
                ->map(fn (Surah $s) => ['id' => $s->id, 'name' => $s->getDisplayName($locale), 'verses' => $s->verses_count])->all(),
        ]);
    }

    /**
     * Playable recordings for one surah — the "add surah" picker loads these on demand.
     */
    public function coverage(int $surah): JsonResponse
    {
        abort_unless($surah >= 1 && $surah <= 114, 404);

        return response()->json(['options' => $this->service->coverage([$surah])[$surah] ?? []]);
    }

    public function update(UpdatePlaylistRequest $request, int $playlist): RedirectResponse
    {
        $model = Playlist::query()->with('items')->findOrFail($playlist);
        $old = $model->toArray();

        // The posted rows are the whole list: a row left out is removed, a new
        // surah is added. Recordings without playable audio are kept as "needs a reciter".
        $items = $request->items();
        foreach ($this->service->invalidPairs($items) as $bad) {
            $items[$bad['surah']] = null;
        }

        $this->service->update($model, new PlaylistData(
            trim($request->validated('name')),
            $request->validated('description') ?: null,
            count($items) === 114 ? 'full' : 'custom',
            $items,
            $request->boolean('is_public'),
        ));
        $this->service->setFeatured($model, $request->boolean('is_featured'));
        $this->logger->logUpdate($model, $old, $model->fresh()->toArray(), $request->user());

        return redirect()->route('admin.playlists.edit', $model->id)->with('success', __('quran.playlists_admin.updated'));
    }

    public function toggle(Request $request, int $playlist, string $field): RedirectResponse
    {
        $model = Playlist::query()->findOrFail($playlist);
        $old = $model->toArray();

        $field === 'public'
            ? $this->service->setPublic($model, ! $model->is_public)
            : $this->service->setFeatured($model, ! $model->is_featured);
        $this->logger->logUpdate($model, $old, $model->toArray(), $request->user());

        return back()->with('success', __('quran.playlists_admin.toggled'));
    }

    public function bulkUnpublish(Request $request): RedirectResponse
    {
        $ids = array_values(array_filter(array_map('intval', (array) $request->input('ids', []))));
        if ($ids !== []) {
            Playlist::query()->whereKey($ids)->update(['is_public' => false, 'is_featured' => false]);
            QuranCacheKeys::flushPlaylists();
        }

        return back()->with('success', __('quran.playlists_admin.unpublished', ['count' => count($ids)]));
    }

    public function destroy(Request $request, int $playlist): RedirectResponse
    {
        $model = Playlist::query()->findOrFail($playlist);
        $this->logger->logDelete($model, $request->user());
        $this->service->delete($model);

        return redirect()->route('admin.playlists.index')->with('success', __('quran.playlists_admin.deleted'));
    }
}
