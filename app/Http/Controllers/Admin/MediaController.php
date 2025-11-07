<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\Media\StoreMediaRequest;
use App\Http\Requests\Admin\Media\UpdateMediaRequest;
use App\Http\Resources\MediaResource;
use App\Models\Media;
use App\Repositories\MediaRepository;
use App\Services\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Media Controller
 *
 * Manages media uploads, updates, and deletions using Spatie Media Library
 */
class MediaController extends AdminController
{
    protected MediaRepository $repository;
    protected MediaService $mediaService;

    public function __construct(MediaRepository $repository, MediaService $mediaService)
    {
        parent::__construct();
        $this->repository = $repository;
        $this->mediaService = $mediaService;
    }

    /**
     * Display a listing of the media
     *
     * GET /admin/media
     *
     * Query Parameters:
     * - search: Search by filename, alt text, caption
     * - type: Filter by type (image, video, document)
     * - collection: Filter by collection name
     * - mime_type: Filter by mime type
     * - tag: Filter by tag
     * - date_from, date_to: Date range filter
     * - size_min, size_max: Size range filter (bytes)
     * - unused: Show only unused media (true/false)
     * - images_only, videos_only, documents_only: Type filters
     * - sort: Sort field (default: created_at)
     * - order: Sort order (asc/desc, default: desc)
     * - per_page: Items per page (default: 15, max: 100)
     *
     * @param Request $request
     * @return JsonResponse|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $this->authorizeAdmin('view media');

        // Get pagination and filter parameters
        $params = $this->getPaginationParams($request);
        $filters = $this->getFilters($request, [
            'search',
            'type',
            'collection',
            'mime_type',
            'tag',
            'date_from',
            'date_to',
            'size_min',
            'size_max',
            'unused',
            'images_only',
            'videos_only',
            'documents_only',
        ]);

        // Merge pagination params with filters
        $filters = array_merge($filters, [
            'sort' => $params['sort'],
            'order' => $params['order'],
        ]);

        $media = $this->repository->getAllWithFilters($filters, $params['per_page']);

        // For AJAX/JSON requests
        if ($this->expectsJson($request)) {
            return $this->successResponse([
                'media' => MediaResource::collection($media),
                'meta' => [
                    'current_page' => $media->currentPage(),
                    'last_page' => $media->lastPage(),
                    'per_page' => $media->perPage(),
                    'total' => $media->total(),
                ],
            ]);
        }

        // For web requests, return view
        return view('admin.simple-media.index', [
            'media' => $media,
            'filters' => $filters,
        ]);
    }

    /**
     * Upload a new media file
     *
     * POST /admin/media
     *
     * Request Body:
     * - file: The file to upload (required)
     * - collection_name: Collection name (optional)
     * - alt_text: Alt text for images (optional)
     * - caption: Media caption (optional)
     * - tags: Array of tags (optional)
     * - optimize: Optimize images (default: true)
     * - generate_thumbnails: Generate thumbnails (default: true)
     *
     * @param StoreMediaRequest $request
     * @return JsonResponse
     */
    public function store(StoreMediaRequest $request): JsonResponse
    {
        try {
            $file = $request->file('file');

            // Upload media using service
            $media = $this->mediaService->upload($file, [
                'collection_name' => $request->input('collection_name', 'default'),
                'alt_text' => $request->input('alt_text'),
                'caption' => $request->input('caption'),
                'tags' => $request->input('tags', []),
                'optimize' => $request->boolean('optimize', true),
                'generate_thumbnails' => $request->boolean('generate_thumbnails', true),
            ]);

            // Log activity
            $this->logActivity('created', 'Media', $media->id, [
                'filename' => $media->filename,
                'size' => $media->size,
                'type' => $media->type,
            ]);

            return $this->successResponse(
                new MediaResource($media),
                'Media uploaded successfully',
                201
            );
        } catch (\Exception $e) {
            Log::error('Media upload failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return $this->errorResponse(
                'Upload failed: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Display the specified media
     *
     * GET /admin/media/{id}
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $this->authorizeAdmin('view media');

        try {
            $media = $this->repository->findById($id);

            return $this->successResponse(new MediaResource($media));
        } catch (\Exception $e) {
            return $this->errorResponse('Media not found', 404);
        }
    }

    /**
     * Update the specified media metadata
     *
     * PUT/PATCH /admin/media/{id}
     *
     * Request Body:
     * - alt_text: Alt text (optional)
     * - caption: Caption (optional)
     * - collection_name: Collection (optional)
     * - tags: Array of tags (optional)
     * - metadata: Additional metadata (optional)
     *
     * @param UpdateMediaRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateMediaRequest $request, int $id): JsonResponse
    {
        try {
            $media = $this->repository->update($id, $request->validated());

            // Log activity
            $this->logActivity('updated', 'Media', $media->id, [
                'changes' => $request->validated(),
            ]);

            return $this->successResponse(
                new MediaResource($media),
                'Media updated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Update failed: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Remove the specified media
     *
     * DELETE /admin/media/{id}
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $this->authorizeAdmin('delete media');

        try {
            $media = $this->repository->findById($id);

            // Check if media is in use
            $usage = $this->repository->getMediaUsage($id);
            if ($usage['total_count'] > 0) {
                return $this->errorResponse(
                    'Cannot delete media that is currently in use. Remove it from ' .
                    $usage['total_count'] . ' location(s) first.',
                    422
                );
            }

            $filename = $media->filename;
            $this->repository->delete($id);

            // Log activity
            $this->logActivity('deleted', 'Media', $id, [
                'filename' => $filename,
            ]);

            return $this->successResponse(
                null,
                'Media deleted successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Delete failed: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Bulk delete media files
     *
     * POST /admin/media/bulk-destroy
     *
     * Request Body:
     * - ids: Array of media IDs to delete
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkDestroy(Request $request): JsonResponse
    {
        $this->authorizeAdmin('delete media');

        $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:media,id'],
        ]);

        $results = $this->repository->bulkDelete($request->input('ids'));

        // Log activity
        $this->logActivity('bulk_deleted', 'Media', $request->input('ids'), [
            'success' => $results['success'],
            'failed' => $results['failed'],
        ]);

        return $this->successResponse($results, [
            'Deleted ' . $results['success'] . ' media file(s).',
            $results['failed'] > 0 ? 'Failed to delete ' . $results['failed'] . ' file(s).' : null,
        ]);
    }

    /**
     * Bulk update tags for media files
     *
     * POST /admin/media/update-tags
     *
     * Request Body:
     * - ids: Array of media IDs
     * - tags: Array of tags to apply
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateTags(Request $request): JsonResponse
    {
        $this->authorizeAdmin('edit media');

        $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:media,id'],
            'tags' => ['required', 'array'],
            'tags.*' => ['string', 'max:50'],
        ]);

        $results = $this->repository->bulkUpdateTags(
            $request->input('ids'),
            $request->input('tags')
        );

        // Log activity
        $this->logActivity('bulk_updated_tags', 'Media', $request->input('ids'), [
            'tags' => $request->input('tags'),
            'success' => $results['success'],
            'failed' => $results['failed'],
        ]);

        return $this->successResponse($results, [
            'Updated tags for ' . $results['success'] . ' media file(s).',
            $results['failed'] > 0 ? 'Failed to update ' . $results['failed'] . ' file(s).' : null,
        ]);
    }

    /**
     * Search media (AJAX endpoint)
     *
     * GET /admin/media/search
     *
     * Query Parameters:
     * - q: Search query (required)
     * - type: Filter by type (optional)
     * - limit: Max results (default: 20, max: 100)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $this->authorizeAdmin('view media');

        $request->validate([
            'q' => ['required', 'string', 'min:2'],
            'type' => ['nullable', 'string', 'in:image,video,document'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $results = $this->repository->searchMedia(
            $request->input('q'),
            $request->input('type'),
            $request->integer('limit', 20)
        );

        return $this->successResponse(MediaResource::collection($results));
    }

    /**
     * Get media usage information
     *
     * GET /admin/media/{id}/usage
     *
     * @param int $id
     * @return JsonResponse
     */
    public function usage(int $id): JsonResponse
    {
        $this->authorizeAdmin('view media');

        try {
            $usage = $this->repository->getMediaUsage($id);

            return $this->successResponse($usage);
        } catch (\Exception $e) {
            return $this->errorResponse('Media not found', 404);
        }
    }

    /**
     * Optimize an existing media file
     *
     * POST /admin/media/{id}/optimize
     *
     * @param int $id
     * @return JsonResponse
     */
    public function optimize(int $id): JsonResponse
    {
        $this->authorizeAdmin('edit media');

        try {
            $media = $this->repository->findById($id);

            $success = $this->mediaService->optimizeImage($media);

            if ($success) {
                // Log activity
                $this->logActivity('optimized', 'Media', $media->id, [
                    'filename' => $media->filename,
                ]);

                return $this->successResponse(
                    new MediaResource($media->fresh()),
                    'Media optimized successfully'
                );
            }

            return $this->errorResponse(
                'Optimization failed or not applicable',
                400
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Optimization failed: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Generate thumbnail for media file
     *
     * POST /admin/media/{id}/thumbnail
     *
     * Request Body:
     * - width: Thumbnail width (optional, default: 300)
     * - height: Thumbnail height (optional, default: 300)
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function thumbnail(Request $request, int $id): JsonResponse
    {
        $this->authorizeAdmin('edit media');

        $request->validate([
            'width' => ['nullable', 'integer', 'min:50', 'max:2000'],
            'height' => ['nullable', 'integer', 'min:50', 'max:2000'],
        ]);

        try {
            $media = $this->repository->findById($id);

            $thumbnailPath = $this->mediaService->createThumbnail(
                $media,
                $request->integer('width', 300),
                $request->integer('height', 300)
            );

            if ($thumbnailPath) {
                return $this->successResponse([
                    'thumbnail_path' => $thumbnailPath,
                    'thumbnail_url' => asset('storage/' . $thumbnailPath),
                ], 'Thumbnail created successfully');
            }

            return $this->errorResponse(
                'Thumbnail creation failed or not applicable',
                400
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Thumbnail creation failed: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Get media statistics
     *
     * GET /admin/media/statistics
     *
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        $this->authorizeAdmin('view media');

        $stats = $this->repository->getStatistics();

        return $this->successResponse($stats);
    }

    /**
     * Get all collections
     *
     * GET /admin/media/collections
     *
     * @return JsonResponse
     */
    public function collections(): JsonResponse
    {
        $this->authorizeAdmin('view media');

        $collections = $this->repository->getAllCollections();

        return $this->successResponse($collections);
    }

    /**
     * Get all tags
     *
     * GET /admin/media/tags
     *
     * @return JsonResponse
     */
    public function tags(): JsonResponse
    {
        $this->authorizeAdmin('view media');

        $tags = $this->repository->getAllTags();

        return $this->successResponse($tags);
    }
}
