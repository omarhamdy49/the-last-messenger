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
