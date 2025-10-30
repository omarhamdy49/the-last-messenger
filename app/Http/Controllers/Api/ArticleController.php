<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Blog\Services\ArticleService;
use App\Exceptions\Domain\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreArticleRequest;
use App\Http\Requests\UpdateArticleRequest;
use App\Http\Resources\ArticleResource;
use App\Support\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Article API Controller
 * Handles HTTP requests for articles
 */
class ArticleController extends Controller
{
    use ApiResponse;

    /**
     * Constructor
     */
    public function __construct(
        protected ArticleService $articleService
    ) {
        $this->middleware('auth:sanctum')->except(['index', 'show', 'featured', 'popular', 'recent', 'search']);
    }

    /**
     * Display a listing of published articles
     *
     * GET /api/articles
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $categoryId = $request->input('category_id');

        $articles = $categoryId
            ? $this->articleService->getArticlesByCategory((int) $categoryId, (int) $perPage)
            : $this->articleService->getPublishedArticles((int) $perPage);

        return $this->paginatedResponse($articles->setCollection(
            $articles->getCollection()->map(fn ($article) => new ArticleResource($article))
        ));
    }

    /**
     * Display the specified article
     *
     * GET /api/articles/{slug}
     */
    public function show(string $slug): JsonResponse
    {
        try {
            $article = $this->articleService->getArticleBySlug($slug);

            return $this->successResponse(new ArticleResource($article));
        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        }
    }

    /**
     * Store a newly created article
     *
     * POST /api/articles
     */
    public function store(StoreArticleRequest $request): JsonResponse
    {
        $article = $this->articleService->createArticle($request->validated());

        // Sync tags if provided
        if ($request->has('tags')) {
            $this->articleService->syncTags($article->id, $request->input('tags'));
        }

        return $this->createdResponse(
            new ArticleResource($article->load(['author', 'category', 'tags'])),
            'Article created successfully'
        );
    }

    /**
     * Update the specified article
     *
     * PUT/PATCH /api/articles/{id}
     */
    public function update(UpdateArticleRequest $request, int $id): JsonResponse
    {
        try {
            $this->articleService->updateArticle($id, $request->validated());

            // Sync tags if provided
            if ($request->has('tags')) {
                $this->articleService->syncTags($id, $request->input('tags'));
            }

            $article = $this->articleService->find($id, ['author', 'category', 'tags']);

            return $this->successResponse(
                new ArticleResource($article),
                'Article updated successfully'
            );
        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        }
    }

    /**
     * Remove the specified article
     *
     * DELETE /api/articles/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->articleService->deleteArticle($id);

            return $this->successResponse(null, 'Article deleted successfully');
        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        }
    }

    /**
     * Get featured articles
     *
     * GET /api/articles/featured
     */
    public function featured(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 5);
        $articles = $this->articleService->getFeaturedArticles((int) $limit);

        return $this->successResponse(ArticleResource::collection($articles));
    }

    /**
     * Get popular articles
     *
     * GET /api/articles/popular
     */
    public function popular(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 10);
        $articles = $this->articleService->getPopularArticles((int) $limit);

        return $this->successResponse(ArticleResource::collection($articles));
    }

    /**
     * Get recent articles
     *
     * GET /api/articles/recent
     */
    public function recent(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 10);
        $articles = $this->articleService->getRecentArticles((int) $limit);

        return $this->successResponse(ArticleResource::collection($articles));
    }

    /**
     * Search articles
     *
     * GET /api/articles/search
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = $request->input('q');
        $perPage = $request->input('per_page', 15);

        $articles = $this->articleService->searchArticles($query, (int) $perPage);

        return $this->paginatedResponse($articles->setCollection(
            $articles->getCollection()->map(fn ($article) => new ArticleResource($article))
        ));
    }

    /**
     * Toggle favorite for authenticated user
     *
     * POST /api/articles/{id}/favorite
     */
    public function toggleFavorite(Request $request, int $id): JsonResponse
    {
        try {
            $isFavorited = $this->articleService->toggleFavorite($id, $request->user()->id);

            return $this->successResponse(
                ['is_favorited' => $isFavorited],
                $isFavorited ? 'Article added to favorites' : 'Article removed from favorites'
            );
        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        }
    }

    /**
     * Publish article
     *
     * POST /api/articles/{id}/publish
     */
    public function publish(int $id): JsonResponse
    {
        try {
            $this->articleService->publishArticle($id);

            return $this->successResponse(null, 'Article published successfully');
        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        }
    }

    /**
     * Unpublish article (set to draft)
     *
     * POST /api/articles/{id}/unpublish
     */
    public function unpublish(int $id): JsonResponse
    {
        try {
            $this->articleService->unpublishArticle($id);

            return $this->successResponse(null, 'Article unpublished successfully');
        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        }
    }

    /**
     * Archive article
     *
     * POST /api/articles/{id}/archive
     */
    public function archive(int $id): JsonResponse
    {
        try {
            $this->articleService->archiveArticle($id);

            return $this->successResponse(null, 'Article archived successfully');
        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        }
    }
}
