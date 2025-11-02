<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MediaController extends Controller
{
    protected MediaService $mediaService;

    public function __construct(MediaService $mediaService)
    {
        $this->mediaService = $mediaService;
    }

    /**
     * Display a listing of the media.
     */
    public function index(Request $request)
    {
        // If it's an AJAX request, return JSON
        if ($request->ajax() || $request->wantsJson()) {
            $query = Media::query();

            // Filter by type
            if ($request->filled('type')) {
                $query->where('type', $request->type);
            }

            // Filter by collection
            if ($request->filled('collection')) {
                $query->where('collection_name', $request->collection);
            }

            // Search by filename
            if ($request->filled('search')) {
                $query->where(function ($q) use ($request) {
                    $q->where('filename', 'like', "%{$request->search}%")
                      ->orWhere('original_filename', 'like', "%{$request->search}%");
                });
            }

            // Order by newest first
            $query->orderBy('created_at', 'desc');

            // Paginate results
            $media = $query->paginate($request->get('per_page', 20));

            return response()->json($media);
        }

        // Return Blade view for regular requests
        return view('admin.simple-media.index');
    }

    /**
     * Upload a new media file.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:102400', // 100MB max
            'collection_name' => 'nullable|string|max:255',
            'alt_text' => 'nullable|string',
            'caption' => 'nullable|string',
            'optimize' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $media = $this->mediaService->upload($request->file('file'), [
                'collection_name' => $request->get('collection_name', 'default'),
                'alt_text' => $request->get('alt_text'),
                'caption' => $request->get('caption'),
                'optimize' => $request->get('optimize', true),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Media uploaded successfully',
                'data' => $media,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified media.
     */
    public function show(Media $media): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $media,
        ]);
    }

    /**
     * Update the specified media.
     */
    public function update(Request $request, Media $media): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'alt_text' => 'nullable|string',
            'caption' => 'nullable|string',
            'collection_name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $media->update($request->only(['alt_text', 'caption', 'collection_name']));

            return response()->json([
                'success' => true,
                'message' => 'Media updated successfully',
                'data' => $media,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Update failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified media.
     */
    public function destroy(Media $media): JsonResponse
    {
        try {
            $media->delete();

            return response()->json([
                'success' => true,
                'message' => 'Media deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Delete failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Optimize an existing media file.
     */
    public function optimize(Media $media): JsonResponse
    {
        try {
            $success = $this->mediaService->optimizeImage($media);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Media optimized successfully',
                    'data' => $media->fresh(),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Optimization failed or not applicable',
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Optimization failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a thumbnail for a media file.
     */
    public function thumbnail(Request $request, Media $media): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'width' => 'nullable|integer|min:50|max:2000',
            'height' => 'nullable|integer|min:50|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $thumbnailPath = $this->mediaService->createThumbnail(
                $media,
                $request->get('width', 300),
                $request->get('height', 300)
            );

            if ($thumbnailPath) {
                return response()->json([
                    'success' => true,
                    'message' => 'Thumbnail created successfully',
                    'data' => [
                        'thumbnail_path' => $thumbnailPath,
                        'thumbnail_url' => asset('storage/' . $thumbnailPath),
                    ],
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Thumbnail creation failed or not applicable',
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Thumbnail creation failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
