<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\Media\BulkUploadMediaRequest;
use App\Http\Requests\Admin\Media\CropMediaRequest;
use App\Http\Requests\Admin\Media\StoreMediaRequest;
use App\Http\Requests\Admin\Media\UpdateMediaRequest;
use App\Http\Resources\MediaResource;
use App\Models\Media;
use App\Repositories\MediaRepository;
use App\Services\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MediaController extends AdminController
{
    public function __construct(
        protected MediaRepository $repository,
        protected MediaService $mediaService
    ) {
        parent::__construct();
    }

    public function index(Request $request)
    {
        $this->authorizeAdmin('view media');

        $params = $this->getPaginationParams($request);
        $filters = array_merge(
            $this->getFilters($request, [
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
            ]),
            [
                'sort' => $params['sort'],
                'order' => $params['order'],
            ]
        );

        $media = $this->repository->getAllWithFilters($filters, $params['per_page']);

        if ($this->expectsJson($request)) {
            return response()->json([
                'success' => true,
                'message' => __('media.messages.library_loaded'),
                'data' => MediaResource::collection($media->getCollection())->resolve(),
                'meta' => [
                    'current_page' => $media->currentPage(),
                    'last_page' => $media->lastPage(),
                    'per_page' => $media->perPage(),
                    'total' => $media->total(),
                    'from' => $media->firstItem(),
                    'to' => $media->lastItem(),
                ],
            ]);
        }

        return view('admin.media.index', [
            'collections' => $this->repository->getAllCollections(),
            'tags' => $this->repository->getAllTags(),
            'stats' => $this->repository->getStatistics(),
            'initialFilters' => $filters,
        ]);
    }

    public function store(StoreMediaRequest $request): JsonResponse
    {
        $media = $this->mediaService->upload(
            $request->file('file'),
            $request->validated()
        );

        $this->logActivity('created', 'Media', $media->id, [
            'filename' => $media->filename,
            'type' => $media->type,
            'collection' => $media->collection_name,
        ]);

        return $this->createdResponse(
            new MediaResource($media),
            __('media.messages.media_uploaded')
        );
    }

    public function bulkUpload(BulkUploadMediaRequest $request): JsonResponse
    {
        $mediaItems = $this->mediaService->bulkUpload(
            $request->file('files', []),
            $request->validated()
        );

        $this->logActivity('bulk_upload', 'Media', $mediaItems->pluck('id')->all(), [
            'count' => $mediaItems->count(),
            'collection' => $request->input('collection_name', 'general'),
        ]);

        return $this->createdResponse(
            MediaResource::collection($mediaItems)->resolve(),
            __('media.messages.media_files_uploaded')
        );
    }

    public function show(Request $request, Media $media): JsonResponse|RedirectResponse
    {
        $this->authorizeAdmin('view media');

        if (! $this->expectsJson($request)) {
            return redirect()->route('admin.media-library.index', ['open' => $media->id]);
        }

        return $this->successResponse(new MediaResource($media));
    }

    public function update(UpdateMediaRequest $request, Media $media): JsonResponse
    {
        $this->authorizeAdmin('edit media');

        $updated = $this->mediaService->updateMetadata($media, $request->validated());

        $this->logActivity('updated', 'Media', $updated->id, [
            'changes' => $request->validated(),
        ]);

        return $this->successResponse(
            new MediaResource($updated),
            __('media.messages.media_updated')
        );
    }

    public function destroy(Request $request, Media $media): JsonResponse
    {
        $this->authorizeAdmin('delete media');

        $mediaId = $media->id;
        $filename = $media->filename;
        $media->delete();

        $this->logActivity('deleted', 'Media', $mediaId, [
            'filename' => $filename,
        ]);

        return $this->successResponse(null, __('media.messages.media_deleted'));
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $this->authorizeAdmin('delete media');

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:media,id'],
        ]);

        $results = $this->repository->bulkDelete($validated['ids']);

        $this->logActivity('bulk_deleted', 'Media', $validated['ids'], $results);

        return $this->successResponse($results, __('media.messages.bulk_delete_completed'));
    }

    public function updateTags(Request $request): JsonResponse
    {
        $this->authorizeAdmin('edit media');

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:media,id'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
        ]);

        $results = $this->repository->bulkUpdateTags(
            $validated['ids'],
            $validated['tags'] ?? []
        );

        $this->logActivity('bulk_updated_tags', 'Media', $validated['ids'], [
            'tags' => $validated['tags'] ?? [],
        ]);

        return $this->successResponse($results, __('media.messages.bulk_tags_updated'));
    }

    public function search(Request $request): JsonResponse
    {
        $this->authorizeAdmin('view media');

        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2'],
            'type' => ['nullable', 'string', 'in:image,video,audio,document'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $results = $this->repository->searchMedia(
            $validated['q'],
            $validated['type'] ?? null,
            (int) ($validated['limit'] ?? 20)
        );

        return $this->successResponse(
            MediaResource::collection($results)->resolve(),
            __('media.messages.search_results_loaded')
        );
    }

    public function usage(Media $media): JsonResponse
    {
        $this->authorizeAdmin('view media');

        return $this->successResponse($this->repository->getMediaUsage($media->id));
    }

    public function optimize(Media $media): JsonResponse
    {
        $this->authorizeAdmin('edit media');

        $success = $this->mediaService->optimizeImage($media);
        if (! $success) {
            return $this->errorResponse(__('media.messages.image_only_optimize'), 422);
        }

        $this->logActivity('optimized', 'Media', $media->id, [
            'filename' => $media->filename,
        ]);

        return $this->successResponse(
            new MediaResource($media->fresh()),
            __('media.messages.media_optimized')
        );
    }

    public function crop(CropMediaRequest $request, Media $media): JsonResponse
    {
        $this->authorizeAdmin('edit media');

        $cropped = $this->mediaService->crop($media, $request->validated());

        $this->logActivity('cropped', 'Media', $media->id, $request->validated());

        return $this->successResponse(
            new MediaResource($cropped),
            __('media.messages.media_cropped')
        );
    }

    public function regenerateVariants(Request $request, Media $media): JsonResponse
    {
        $this->authorizeAdmin('edit media');

        $validated = $request->validate([
            'generate_sizes' => ['nullable', 'boolean'],
        ]);

        $updated = $this->mediaService->regenerateVariants(
            $media,
            (bool) ($validated['generate_sizes'] ?? true)
        );

        return $this->successResponse(
            new MediaResource($updated),
            __('media.messages.variants_regenerated')
        );
    }

    public function statistics(): JsonResponse
    {
        $this->authorizeAdmin('view media');

        return $this->successResponse($this->repository->getStatistics());
    }

    public function collections(): JsonResponse
    {
        $this->authorizeAdmin('view media');

        return $this->successResponse($this->repository->getAllCollections());
    }

    public function tags(): JsonResponse
    {
        $this->authorizeAdmin('view media');

        return $this->successResponse($this->repository->getAllTags());
    }

    public function file(Media $media)
    {
        $this->authorizeAdmin('view media');

        return $this->mediaService->stream($media);
    }

    public function variant(Media $media, string $variant)
    {
        $this->authorizeAdmin('view media');

        return $this->mediaService->stream($media, $variant);
    }

    public function download(Media $media)
    {
        $this->authorizeAdmin('view media');

        return $this->mediaService->download($media);
    }
}
