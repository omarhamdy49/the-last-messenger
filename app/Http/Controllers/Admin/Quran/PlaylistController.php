<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Quran;

use App\Domain\Quran\DTOs\PlaylistData;
use App\Domain\Quran\Models\Playlist;
use App\Domain\Quran\Repositories\PlaylistRepository;
use App\Domain\Quran\Services\PlaylistService;
use App\Domain\Quran\Services\QuranCacheKeys;
use App\Domain\Shared\Services\AdminLoggerService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Quran\UpdatePlaylistRequest;
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

        return view('admin.playlists.edit', [
            'playlist' => $model,
            'coverage' => $this->service->coverage($model->items->pluck('surah_id')->all()),
        ]);
    }

    public function update(UpdatePlaylistRequest $request, int $playlist): RedirectResponse
    {
        $model = Playlist::query()->with('items')->findOrFail($playlist);
        $old = $model->toArray();

        // Only surahs already on the playlist can be re-assigned here; adding
        // surahs is the owner's job through the wizard.
        $items = $model->items->pluck('reciter_id', 'surah_id')->all();
        foreach ($request->items() as $surah => $recording) {
            if (array_key_exists($surah, $items)) {
                $items[$surah] = $recording;
            }
        }

        $this->service->update($model, new PlaylistData(
            trim($request->validated('name')),
            $request->validated('description') ?: null,
            $model->scope,
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
