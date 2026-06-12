<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Blog\Repositories\CategoryRepositoryInterface;
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

    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository,
    ) {}

    /**
     * Display a listing of categories
     *
     * GET /api/categories
     */
    public function index(): JsonResponse
    {
        $categories = $this->categoryRepository->listRoots();

        return $this->successResponse(CategoryResource::collection($categories));
    }

    /**
     * Display the specified category with articles
     *
     * GET /api/categories/{slug}
     */
    public function show(string $slug): JsonResponse
    {
        $category = $this->categoryRepository->findBySlug($slug);

        if (! $category) {
            return $this->notFoundResponse('Category not found');
        }

        return $this->successResponse(new CategoryResource($category));
    }
}
