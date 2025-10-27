<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Blog\Models\Category;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Support\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * Category API Controller
 * Handles HTTP requests for categories
 */
class CategoryController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of categories
     *
     * GET /api/categories
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $categories = Category::with(['children'])
            ->root()
            ->ordered()
            ->get();

        return $this->successResponse(CategoryResource::collection($categories));
    }

    /**
     * Display the specified category with articles
     *
     * GET /api/categories/{slug}
     *
     * @param string $slug
     * @return JsonResponse
     */
    public function show(string $slug): JsonResponse
    {
        $category = Category::with(['children', 'parent'])
            ->where('slug', $slug)
            ->first();

        if (!$category) {
            return $this->notFoundResponse('Category not found');
        }

        return $this->successResponse(new CategoryResource($category));
    }
}

