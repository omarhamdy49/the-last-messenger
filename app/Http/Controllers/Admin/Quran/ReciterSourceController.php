<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Quran;

use App\Domain\Quran\Enums\ExternalSource;
use App\Domain\Quran\Enums\ResourceKind;
use App\Domain\Quran\Import\QuranFoundation\Exceptions\QfApiException;
use App\Domain\Quran\Import\Reciters\ReciterFetchService;
use App\Domain\Quran\Services\QuranAdminCommands;
use App\Domain\Quran\Services\ResourceService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Quran\ReciterSourceRequest;
use App\Jobs\FetchReciterFromSourceJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use RuntimeException;

/**
 * Source-backed reciters: preview a Quran Foundation / mp3quran id, then fetch data,
 * audio files and timings in the background.
 */
final class ReciterSourceController extends Controller
{
    public function __construct(
        private readonly ReciterFetchService $fetcher,
        private readonly ResourceService $resources,
        private readonly QuranAdminCommands $commands,
    ) {}

    /**
     * Browse mp3quran by reciter name: import a recording as a new reciter, or point an
     * existing reciter at it.
     */
    public function mp3quran(Request $request): View
    {
        $rows = [];
        $error = null;

        try {
            $rows = $this->fetcher->mp3QuranCatalogue($request->boolean('refresh'));
        } catch (RuntimeException $e) {
            report($e);
            $error = __('quran.manage.mp3quran_unavailable');
        }

        return view('admin.quran.resources.mp3quran', [
            'rows' => $rows,
            'error' => $error,
            'linked' => $this->resources->linkedTo(ExternalSource::MP3QURAN),
            'reciters' => $this->resources->adminIndex(ResourceKind::CHAPTER_RECITER, [], 500)->getCollection(),
        ]);
    }

    public function importMp3quran(Request $request): RedirectResponse
    {
        $validated = $request->validate(['ref_id' => ['required', 'string', 'regex:/^\d{1,9}$/']]);

        try {
            $resource = $this->fetcher->createFromMp3Quran((string) $validated['ref_id']);
        } catch (InvalidArgumentException $e) {
            // An id mp3quran does not have is the admin's typo, not an incident.
            return back()->with('error', $e->getMessage());
        } catch (RuntimeException $e) {
            report($e);

            return back()->with('error', __('quran.manage.mp3quran_unavailable'));
        }

        // A reciter with no audio is an empty page: fetch its files and timings straight away.
        if ($resource->wasRecentlyCreated) {
            $run = $this->commands->startFetchRun($resource->id, $request->user()?->id);
            FetchReciterFromSourceJob::dispatch($resource->id, $run->id, false, true, true, false);
        }

        return redirect()->route('admin.quran.resources.show', $resource->id)
            ->with('success', $resource->wasRecentlyCreated ? __('quran.manage.mp3quran_imported') : __('quran.manage.source_already_linked', ['name' => $resource->displayName()]));
    }

    /**
     * Tie an existing reciter to a QF / mp3quran recording so it can be fetched from there.
     */
    public function linkSource(ReciterSourceRequest $request, int $resource): RedirectResponse
    {
        $model = $this->resources->find($resource) ?? abort(404);
        $validated = $request->validated();
        $source = ExternalSource::from((string) $validated['external_source']);
        $refId = trim((string) $validated['external_ref_id']);

        if (! $model->kind->isReciter()) {
            return back()->with('error', __('quran.manage.reciter_no_source'));
        }

        $owner = $this->resources->linkedTo($source)->get($refId);
        if ($owner !== null && $owner->id !== $model->id) {
            return back()->with('error', __('quran.manage.source_already_linked', ['name' => $owner->displayName()]));
        }

        try {
            // Proves the id exists at the source (and suits this kind) before it is stored.
            $this->fetcher->lookup($source, $refId, $model->kind);
        } catch (InvalidArgumentException|RuntimeException|QfApiException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->resources->linkSource($model->id, $source, $refId);

        return redirect()->route('admin.quran.resources.show', $model->id)->with('success', __('quran.manage.source_linked', ['source' => $source->label()]));
    }

    public function lookup(ReciterSourceRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $preview = $this->fetcher->lookup(
                ExternalSource::from((string) $validated['external_source']),
                (string) $validated['external_ref_id'],
                ResourceKind::from((string) $validated['kind']),
            );
        } catch (InvalidArgumentException|RuntimeException|QfApiException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'errors' => ['external_ref_id' => [$e->getMessage()]]], 422);
        }

        return response()->json(['success' => true, 'data' => $preview->toArray()]);
    }

    public function fetch(ReciterSourceRequest $request, int $resource): RedirectResponse|JsonResponse
    {
        $model = $this->resources->find($resource) ?? abort(404);
        if (! $model->kind->isReciter() || $model->external_source === null) {
            return $this->reject($request, __('quran.manage.reciter_no_source'));
        }

        $steps = ['data' => $request->boolean('data'), 'audio' => $request->boolean('audio'), 'timestamps' => $request->boolean('timestamps')];
        if (! in_array(true, $steps, true)) {
            return $this->reject($request, __('quran.manage.choose_fetch_steps'));
        }

        $run = $this->commands->startFetchRun($model->id, $request->user()?->id);
        FetchReciterFromSourceJob::dispatch($model->id, $run->id, $steps['data'], $steps['audio'], $steps['timestamps'], $request->boolean('download'));

        return $request->expectsJson()
            ? response()->json(['success' => true, 'data' => ['run_id' => $run->id]], 202)
            : back()->with('success', __('quran.manage.fetch_queued', ['run' => $run->id]));
    }

    public function status(int $resource): JsonResponse
    {
        $model = $this->resources->find($resource) ?? abort(404);
        $run = $this->commands->latestFetchRun($model->id);

        return response()->json(['success' => true, 'data' => [
            'status' => $run?->status ?? 'idle',
            'run_id' => $run?->id,
            'finished_at' => $run?->finished_at?->toIso8601String(),
            'report' => $model->last_fetch_report,
        ]]);
    }

    private function reject(ReciterSourceRequest $request, string $message): RedirectResponse|JsonResponse
    {
        return $request->expectsJson()
            ? response()->json(['success' => false, 'message' => $message], 422)
            : back()->with('error', $message);
    }
}
