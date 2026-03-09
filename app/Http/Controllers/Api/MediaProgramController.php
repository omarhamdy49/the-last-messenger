<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Media Program API Controller (Stub)
 *
 * The media programs module (video series / episodes) has not yet been
 * implemented as a full domain model. These endpoints are scaffolded so
 * the routes can be enabled without breaking the application.
 *
 * Once the MediaProgram / Episode domain models and repositories are
 * created, replace the stub responses with real query logic following
 * the same patterns used in ArticleController and HadithController.
 */
class MediaProgramController extends Controller
{
    use ApiResponse;

    /**
     * Paginated list of media programs.
     */
    public function index(Request $request): JsonResponse
    {
        return $this->successResponse([], 'Media programs coming soon');
    }

    /**
     * Popular media programs.
     */
    public function popular(Request $request): JsonResponse
    {
        return $this->successResponse([], 'Media programs coming soon');
    }

    /**
     * Most recently added programs.
     */
    public function recent(Request $request): JsonResponse
    {
        return $this->successResponse([], 'Media programs coming soon');
    }

    /**
     * Search media programs by keyword.
     */
    public function search(Request $request): JsonResponse
    {
        return $this->successResponse([], 'Media programs coming soon');
    }

    /**
     * Programs filtered by category.
     */
    public function byCategory(int $categoryId): JsonResponse
    {
        return $this->successResponse([], 'Media programs coming soon');
    }

    /**
     * Single program by slug.
     */
    public function show(string $slug): JsonResponse
    {
        return $this->successResponse([], 'Media programs coming soon');
    }

    /**
     * Episodes list for a program.
     */
    public function episodes(string $slug): JsonResponse
    {
        return $this->successResponse([], 'Media programs coming soon');
    }

    /**
     * Single episode within a program, identified by episode number.
     */
    public function episode(string $slug, int $episodeNumber): JsonResponse
    {
        return $this->successResponse([], 'Media programs coming soon');
    }
}
