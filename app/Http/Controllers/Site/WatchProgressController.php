<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Domain\Media\Services\MediaEpisodeService;
use App\Exceptions\Domain\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\StoreWatchProgressRequest;
use Illuminate\Http\JsonResponse;
use Throwable;

/**
 * Episode watch-progress beats from the public player (session guard).
 *
 * Callers are fetch/sendBeacon only, so responses are always JSON. The write
 * is a single indexed upsert scoped to the acting user inside the service
 * (IDOR boundary); guests are rejected by the `auth` middleware.
 */
final class WatchProgressController extends Controller
{
    public function __construct(
        private readonly MediaEpisodeService $episodes,
    ) {}

    public function store(StoreWatchProgressRequest $request): JsonResponse
    {
        try {
            $this->episodes->saveProgress(
                $request->user()->id,
                (int) $request->validated('episode_id'),
                (int) $request->validated('seconds'),
                (bool) $request->validated('completed', false),
            );
        } catch (ResourceNotFoundException) {
            return response()->json(['success' => false], 404);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['success' => false], 500);
        }

        return response()->json(['success' => true]);
    }
}
