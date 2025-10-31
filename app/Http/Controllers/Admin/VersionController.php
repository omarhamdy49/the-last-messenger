<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Shared\Services\VersionService;
use App\Http\Controllers\Controller;
use App\Http\Requests\RevertVersionRequest;
use App\Http\Resources\ContentVersionResource;
use App\Support\Traits\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Version Controller
 *
 * Manages content version history and operations
 */
class VersionController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected VersionService $versionService
    ) {}

    /**
     * Get versions for a specific content type and ID.
     *
     * GET /admin/versions/{type}/{id}
     *
     * @param  string  $type  Model type (e.g., 'articles', 'hadith')
     * @param  int  $id  Model ID
     */
    public function getVersions(Request $request, string $type, int $id): JsonResponse
    {
        try {
            $model = $this->resolveModel($type, $id);

            $perPage = $request->integer('per_page', 15);
            $versions = $this->versionService->getVersions($model, $perPage);

            return $this->paginatedResponse(
                $versions->setCollection(
                    $versions->getCollection()->map(fn ($version) => new ContentVersionResource($version))
                )
            );
        } catch (ModelNotFoundException $e) {
            return $this->notFoundResponse('Content not found');
        } catch (\Exception $e) {
            Log::error('Failed to get versions', [
                'type' => $type,
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Failed to retrieve versions', 500);
        }
    }

    /**
     * Get a specific version by ID.
     *
     * GET /admin/versions/{versionId}
     */
    public function getVersion(int $versionId): JsonResponse
    {
        try {
            $version = $this->versionService->getVersion($versionId);

            if (! $version) {
                return $this->notFoundResponse('Version not found');
            }

            return $this->successResponse(new ContentVersionResource($version));
        } catch (\Exception $e) {
            Log::error('Failed to get version', [
                'version_id' => $versionId,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Failed to retrieve version', 500);
        }
    }

    /**
     * Compare two versions.
     *
     * GET /admin/versions/{v1}/{v2}/compare
     */
    public function compare(int $v1, int $v2): JsonResponse
    {
        try {
            $diff = $this->versionService->compareVersions($v1, $v2);

            if (empty($diff)) {
                return $this->errorResponse('Versions not found or no differences', 404);
            }

            return $this->successResponse([
                'version_1' => $v1,
                'version_2' => $v2,
                'differences' => $diff,
                'changes_count' => count($diff),
            ], 'Versions compared successfully');
        } catch (\Exception $e) {
            Log::error('Failed to compare versions', [
                'v1' => $v1,
                'v2' => $v2,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Failed to compare versions', 500);
        }
    }

    /**
     * Revert content to a specific version.
     *
     * POST /admin/versions/{id}/revert
     */
    public function revert(RevertVersionRequest $request): JsonResponse
    {
        try {
            $versionId = $request->validated('version_id');
            $version = $this->versionService->getVersion($versionId);

            if (! $version) {
                return $this->notFoundResponse('Version not found');
            }

            // Get the model
            $model = $version->versionable;

            if (! $model) {
                return $this->notFoundResponse('Content not found');
            }

            // Revert to version
            $success = $this->versionService->revertToVersion(
                $model,
                $versionId,
                auth()->id()
            );

            if (! $success) {
                return $this->errorResponse('Failed to revert to version', 500);
            }

            Log::info('Content reverted to version', [
                'model' => get_class($model),
                'model_id' => $model->id,
                'version_id' => $versionId,
                'user_id' => auth()->id(),
            ]);

            return $this->successResponse([
                'version' => new ContentVersionResource($version),
                'model' => [
                    'type' => class_basename(get_class($model)),
                    'id' => $model->id,
                ],
            ], 'Content reverted to version successfully');
        } catch (\Exception $e) {
            Log::error('Failed to revert version', [
                'version_id' => $request->validated('version_id'),
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Failed to revert to version', 500);
        }
    }

    /**
     * Get version timeline for a specific content.
     *
     * GET /admin/versions/{type}/{id}/timeline
     */
    public function getTimeline(string $type, int $id): JsonResponse
    {
        try {
            $model = $this->resolveModel($type, $id);

            $timeline = $this->versionService->getVersionTimeline($model);

            return $this->successResponse([
                'timeline' => ContentVersionResource::collection($timeline),
                'total_versions' => $timeline->count(),
                'model' => [
                    'type' => class_basename(get_class($model)),
                    'id' => $model->id,
                ],
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->notFoundResponse('Content not found');
        } catch (\Exception $e) {
            Log::error('Failed to get version timeline', [
                'type' => $type,
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Failed to retrieve timeline', 500);
        }
    }

    /**
     * Get version statistics.
     *
     * GET /admin/versions/stats
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = $this->versionService->getVersionStats();

            return $this->successResponse($stats);
        } catch (\Exception $e) {
            Log::error('Failed to get version stats', [
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Failed to retrieve statistics', 500);
        }
    }

    /**
     * Prune old versions for a model.
     *
     * POST /admin/versions/{type}/{id}/prune
     */
    public function prune(Request $request, string $type, int $id): JsonResponse
    {
        try {
            $model = $this->resolveModel($type, $id);

            $keepCount = $request->integer('keep_count', 10);
            $deleted = $this->versionService->pruneOldVersions($model, $keepCount);

            return $this->successResponse([
                'deleted_count' => $deleted,
                'kept_count' => $keepCount,
            ], "Pruned {$deleted} old versions");
        } catch (ModelNotFoundException $e) {
            return $this->notFoundResponse('Content not found');
        } catch (\Exception $e) {
            Log::error('Failed to prune versions', [
                'type' => $type,
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Failed to prune versions', 500);
        }
    }

    /**
     * Resolve model from type string and ID.
     *
     * @throws ModelNotFoundException
     */
    protected function resolveModel(string $type, int $id)
    {
        $modelClass = match ($type) {
            'articles' => \App\Domain\Blog\Models\Article::class,
            'hadith' => \App\Domain\Hadith\Models\Hadith::class,
            'seerah' => \App\Domain\Seerah\Models\SeerahEvent::class,
            'sahaba' => \App\Domain\Sahaba\Models\Sahabi::class,
            'media-programs' => \App\Domain\Media\Models\MediaProgram::class,
            'media-episodes' => \App\Domain\Media\Models\MediaEpisode::class,
            'quran-verses' => \App\Domain\Quran\Models\QuranVerse::class,
            'people' => \App\Domain\Genealogy\Models\Person::class,
            default => throw new \InvalidArgumentException("Invalid model type: {$type}"),
        };

        return $modelClass::findOrFail($id);
    }
}
