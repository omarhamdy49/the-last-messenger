<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Blog\Models\Article;
use App\Domain\Media\Models\MediaProgram;
use App\Domain\Sahaba\Models\Sahabi;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UploadMediaRequest;
use App\Support\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Media Upload Controller
 * Handles file uploads for admin panel using Spatie Media Library
 */
class MediaUploadController extends Controller
{
    use ApiResponse;

    /**
     * Model class mapping for polymorphic models
     *
     * @var array<string, class-string>
     */
    private const MODEL_MAP = [
        'article' => Article::class,
        'media_program' => MediaProgram::class,
        'sahabi' => Sahabi::class,
    ];

    /**
     * Upload media file to model
     *
     * POST /su/portal/v1/admin/media/upload
     */
    public function upload(UploadMediaRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            // Get model instance
            $modelClass = self::MODEL_MAP[$validated['model_type']] ?? null;

            if (! $modelClass) {
                return $this->errorResponse('Invalid model type', 400);
            }

            $model = $modelClass::findOrFail($validated['model_id']);

            // Add media file to collection
            $media = $model->addMedia($validated['file'])
                ->usingFileName($this->sanitizeFilename($validated['file']->getClientOriginalName()))
                ->toMediaCollection($validated['collection']);

            // Prepare response data
            $responseData = [
                'id' => $media->id,
                'file_name' => $media->file_name,
                'mime_type' => $media->mime_type,
                'size' => $media->size,
                'human_readable_size' => $media->human_readable_size,
                'url' => $media->getUrl(),
                'conversions' => [],
            ];

            // Add conversion URLs if they exist
            foreach (['thumb', 'medium', 'large'] as $conversion) {
                if ($media->hasGeneratedConversion($conversion)) {
                    $responseData['conversions'][$conversion] = $media->getUrl($conversion);
                }
            }

            return $this->successResponse($responseData, 'File uploaded successfully', 201);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Model not found', 404);
        } catch (\Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist $e) {
            return $this->errorResponse('File does not exist', 400);
        } catch (\Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig $e) {
            return $this->errorResponse('File is too large. Maximum size is 100MB', 413);
        } catch (\Spatie\MediaLibrary\MediaCollections\Exceptions\MimeTypeNotAllowed $e) {
            return $this->errorResponse('File type not allowed', 415);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to upload file: '.$e->getMessage(),
                500
            );
        }
    }

    /**
     * Delete media by ID
     *
     * DELETE /su/portal/v1/admin/media/{id}
     */
    public function delete(Request $request, int $id): JsonResponse
    {
        try {
            $media = Media::findOrFail($id);

            // Check if user has permission to delete this media
            // (Additional authorization logic can be added here)

            $media->delete();

            return $this->successResponse(
                ['id' => $id],
                'Media deleted successfully'
            );

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Media not found', 404);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to delete media: '.$e->getMessage(),
                500
            );
        }
    }

    /**
     * List all media for a model
     *
     * GET /su/portal/v1/admin/media/list
     */
    public function list(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'model_type' => 'required|string|in:article,media_program,sahabi',
                'model_id' => 'required|integer|min:1',
                'collection' => 'nullable|string|in:featured_image,gallery,thumbnail,banner,profile_image',
            ]);

            // Get model instance
            $modelClass = self::MODEL_MAP[$validated['model_type']] ?? null;

            if (! $modelClass) {
                return $this->errorResponse('Invalid model type', 400);
            }

            $model = $modelClass::findOrFail($validated['model_id']);

            // Get media from collection or all media
            $mediaQuery = isset($validated['collection'])
                ? $model->getMedia($validated['collection'])
                : $model->getMedia();

            // Transform media collection
            $mediaList = $mediaQuery->map(function (Media $media) {
                return [
                    'id' => $media->id,
                    'collection_name' => $media->collection_name,
                    'file_name' => $media->file_name,
                    'mime_type' => $media->mime_type,
                    'size' => $media->size,
                    'human_readable_size' => $media->human_readable_size,
                    'url' => $media->getUrl(),
                    'conversions' => [
                        'thumb' => $media->hasGeneratedConversion('thumb') ? $media->getUrl('thumb') : null,
                        'medium' => $media->hasGeneratedConversion('medium') ? $media->getUrl('medium') : null,
                        'large' => $media->hasGeneratedConversion('large') ? $media->getUrl('large') : null,
                    ],
                    'order' => $media->order_column,
                    'created_at' => $media->created_at?->toISOString(),
                ];
            });

            return $this->successResponse(
                ['media' => $mediaList, 'count' => $mediaList->count()],
                'Media list retrieved successfully'
            );

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Model not found', 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse(
                'Validation failed',
                422,
                ['errors' => $e->errors()]
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve media list: '.$e->getMessage(),
                500
            );
        }
    }

    /**
     * Reorder media items in a collection
     *
     * POST /su/portal/v1/admin/media/reorder
     */
    public function reorder(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'media_ids' => 'required|array|min:1',
                'media_ids.*' => 'required|integer|exists:media,id',
            ]);

            // Update order for each media item
            foreach ($validated['media_ids'] as $order => $mediaId) {
                Media::where('id', $mediaId)->update([
                    'order_column' => $order + 1,
                ]);
            }

            return $this->successResponse(
                ['reordered_count' => count($validated['media_ids'])],
                'Media reordered successfully'
            );

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse(
                'Validation failed',
                422,
                ['errors' => $e->errors()]
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to reorder media: '.$e->getMessage(),
                500
            );
        }
    }

    /**
     * Sanitize filename to prevent security issues
     */
    private function sanitizeFilename(string $filename): string
    {
        // Remove any directory traversal attempts
        $filename = basename($filename);

        // Replace spaces with underscores
        $filename = str_replace(' ', '_', $filename);

        // Remove any characters that aren't alphanumeric, underscore, dash, or dot
        $filename = preg_replace('/[^a-zA-Z0-9_\-.]/', '', $filename);

        // Ensure filename is not empty after sanitization
        if (empty($filename)) {
            $filename = 'file_'.time();
        }

        return $filename;
    }
}
