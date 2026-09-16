<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleResource;
use App\Repositories\ArticleRepository;
use App\Support\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;

class ArticleController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ArticleRepository $repository
    ) {}

    /**
     * Paginated list of published articles.
     * Filters: category_id (integer), tag (integer tag id), q (string search, min 2 chars).
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => ['sometimes', 'integer', 'min:1'],
            'tag' => ['sometimes', 'integer', 'min:1'],
            'q' => ['sometimes', 'string', 'min:2', 'max:255'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'locale' => ['sometimes', 'string', Rule::in(enabled_content_locales())],
        ]);

        $perPage = (int) ($validated['per_page'] ?? 20);
        $locale = $validated['locale'] ?? app()->getLocale();

        $filters = ['status' => 'published'];

        if (isset($validated['category_id'])) {
            $filters['category_id'] = (int) $validated['category_id'];
        }

        if (isset($validated['locale'])) {
            $filters['locale'] = $validated['locale'];
        }

        if (isset($validated['tag'])) {
            $paginator = $this->repository->getArticlesByTag((int) $validated['tag'], $perPage);

            return $this->paginatedResponse(
                $this->asResources($paginator),
                'Articles retrieved successfully'
            );
        }

        $search = isset($validated['q']) ? (string) $validated['q'] : null;

        $paginator = $this->repository->getAllWithFilters(
            filters: $filters,
            search: $search,
            perPage: $perPage,
            sort: 'created_at',
            order: 'desc'
        );

        return $this->paginatedResponse($this->asResources($paginator), 'Articles retrieved successfully');
    }

    /**
     * Featured articles (is_featured = true, published only).
     */
    public function featured(Request $request): JsonResponse
    {
        $limit = $request->integer('limit', 10);
        $limit = max(1, min(50, $limit));
        $articles = $this->repository->getFeaturedArticles($limit);

        return $this->successResponse(
            ArticleResource::collection($articles),
            'Featured articles retrieved successfully'
        );
    }

    /**
     * Popular articles ordered by views, published only.
     */
    public function popular(Request $request): JsonResponse
    {
        $limit = $request->integer('limit', 10);
        $limit = max(1, min(50, $limit));
        $days = $request->integer('days', 30);
        $days = max(1, min(365, $days));
        $articles = $this->repository->getPopularArticles($days, $limit);

        return $this->successResponse(
            ArticleResource::collection($articles),
            'Popular articles retrieved successfully'
        );
    }

    /**
     * Recent articles ordered by created_at desc, published only.
     */
    public function recent(Request $request): JsonResponse
    {
        $limit = $request->integer('limit', 10);
        $limit = max(1, min(50, $limit));
        $articles = $this->repository->getRecentArticles($limit);

        return $this->successResponse(
            ArticleResource::collection($articles),
            'Recent articles retrieved successfully'
        );
    }

    /**
     * Search articles by keyword (min 2 chars).
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:255'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'locale' => ['sometimes', 'string', Rule::in(enabled_content_locales())],
        ]);

        $locale = $validated['locale'] ?? app()->getLocale();
        $perPage = (int) ($validated['per_page'] ?? 20);
        $paginator = $this->repository->searchArticles(
            query: $validated['q'],
            locale: $locale,
            perPage: $perPage
        );

        return $this->paginatedResponse($this->asResources($paginator), 'Search results retrieved successfully');
    }

    /**
     * Single published article by slug.
     * Increments view counter on retrieval.
     */
    public function show(string $slug): JsonResponse
    {
        $locale = app()->getLocale();
        $article = $this->repository->findBySlug($slug, $locale);

        if ($article === null) {
            return $this->notFoundResponse('Article not found');
        }

        $this->repository->incrementViews($article->id);

        return $this->successResponse(
            new ArticleResource($article),
            'Article retrieved successfully'
        );
    }

    /**
     * Replace the paginator's models with ArticleResource instances.
     *
     * ApiResponse::paginatedResponse serialises $paginator->items() verbatim, so
     * the items must already be resources: raw models would emit every column
     * of every eager-loaded relation — including writer.email — on what is a
     * public, unauthenticated endpoint.
     */
    private function asResources(LengthAwarePaginator $paginator): LengthAwarePaginator
    {
        return $paginator->through(static fn ($article): ArticleResource => new ArticleResource($article));
    }
}
