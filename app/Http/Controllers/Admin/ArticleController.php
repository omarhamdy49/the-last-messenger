<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Blog\Services\ArticleService;
use App\Domain\Shared\Services\AdminLoggerService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreArticleRequest;
use App\Http\Requests\Admin\UpdateArticleRequest;
use App\Models\Article;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin Article Controller
 *
 * Handles comprehensive article management for The Last Messenger admin dashboard.
 * Provides full CRUD operations, workflow management (publish/review/approve/reject),
 * multi-language support, bulk operations, and activity logging.
 *
 * @package App\Http\Controllers\Admin
 */
class ArticleController extends Controller
{
    /**
     * Constructor
     *
     * @param ArticleService $articleService Service layer for article business logic
     * @param AdminLoggerService $logger Service for logging all admin actions
     */
    public function __construct(
        protected ArticleService $articleService,
        protected AdminLoggerService $logger
    ) {
        // Apply authorization middleware via route (admin.php already has auth middleware)
        // No additional middleware needed here as routes/admin.php handles it
    }

    /**
     * Display paginated list of articles with filters
     *
     * Supports filtering by:
     * - Status (draft, under_review, published, archived, rejected)
     * - Category ID
     * - Author ID
     * - Series ID
     * - Tag ID
     * - Date range (start_date, end_date)
     * - Featured flag
     * - Search query
     *
     * Returns JSON for AJAX requests or view for regular requests.
     *
     * GET /su/portal/v1/admin/articles
     *
     * @param Request $request HTTP request with filter parameters
     * @return View|JsonResponse View with articles or JSON response for AJAX
     */
    public function index(Request $request): View|JsonResponse
    {
        // Authorization is handled by middleware in routes/admin.php
        $perPage = $request->integer('per_page', 50);

        // Build filters array from request
        $filters = [
            'status' => $request->input('status'),
            'category_id' => $request->input('category_id'),
            'author_id' => $request->input('author_id'),
            'series_id' => $request->input('series_id'),
            'tag_id' => $request->input('tag_id'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'is_featured' => $request->input('is_featured'),
            'search' => $request->input('search'),
        ];

        // Remove empty filters
        $filters = array_filter($filters, fn ($value) => $value !== null && $value !== '');

        // Get articles with filters
        if (! empty($filters)) {
            $articles = $this->articleService->paginateWithFilters($filters, $perPage);
        } else {
            $articles = $this->articleService->paginate(
                $perPage,
                ['*'],
                ['author', 'category', 'tags']
            );
        }

        // Calculate statistics
        $stats = [
            'total' => $this->articleService->count(),
            'published' => $this->articleService->countByStatus('published'),
            'draft' => $this->articleService->countByStatus('draft'),
            'under_review' => $this->articleService->countByStatus('under_review'),
            'archived' => $this->articleService->countByStatus('archived'),
            'rejected' => $this->articleService->countByStatus('rejected'),
        ];

        // Return JSON for AJAX requests
        if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'success' => true,
                'data' => $articles->items(),
                'stats' => $stats,
                'meta' => [
                    'current_page' => $articles->currentPage(),
                    'per_page' => $articles->perPage(),
                    'total' => $articles->total(),
                    'last_page' => $articles->lastPage(),
                ],
            ]);
        }

        return view('admin.articles.index', compact('articles', 'stats', 'filters'));
    }

    /**
     * Show the form for creating a new article
     *
     * GET /su/portal/v1/admin/articles/create
     *
     * @return View Article creation form
     */
    public function create(): View
    {
        // Authorization is handled by middleware in routes/admin.php
        return view('admin.articles.create');
    }

    /**
     * Store a newly created article
     *
     * Validates input using StoreArticleRequest, creates article,
     * syncs tags, and logs the action.
     *
     * POST /su/portal/v1/admin/articles
     *
     * @param StoreArticleRequest $request Validated request
     * @return RedirectResponse Redirect to articles index with success message
     */
    public function store(StoreArticleRequest $request): RedirectResponse
    {
        try {
            // Create article
            $article = $this->articleService->createArticle($request->validated());

            // Handle featured image (file upload or media picker selection)
            if ($request->hasFile('featured_image')) {
                $this->articleService->handleFeaturedImage(
                    $article,
                    $request->file('featured_image')
                );
            } elseif ($request->has('featured_image_media_id')) {
                $this->articleService->handleFeaturedImage(
                    $article,
                    null,
                    $request->input('featured_image_media_id')
                );
            }

            // Handle OG image (file upload or media picker selection)
            if ($request->hasFile('og_image')) {
                $this->articleService->handleOgImage(
                    $article,
                    $request->file('og_image')
                );
            } elseif ($request->has('og_image_media_id')) {
                $this->articleService->handleOgImage(
                    $article,
                    null,
                    $request->input('og_image_media_id')
                );
            }

            // Handle gallery images (file uploads or media picker selections)
            if ($request->hasFile('gallery')) {
                $this->articleService->handleGalleryImages(
                    $article,
                    $request->file('gallery')
                );
            }
            if ($request->has('gallery_media_ids')) {
                $this->articleService->handleGalleryImages(
                    $article,
                    null,
                    $request->input('gallery_media_ids')
                );
            }

            // Sync tags if provided
            if ($request->has('tags')) {
                $this->articleService->syncTags($article->id, $request->input('tags'));
            }

            // Log the creation
            $this->logger->logCreate($article, $request->user());

            return redirect()
                ->route('admin.articles.index')
                ->with('success', __('Article created successfully'));
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', __('Failed to create article: :message', ['message' => $e->getMessage()]));
        }
    }

    /**
     * Display the specified article with full details
     *
     * GET /su/portal/v1/admin/articles/{article}
     *
     * @param Article $article Article model (route model binding)
     * @return View Article details view
     */
    public function show(Article $article): View
    {
        // Authorization is handled by middleware in routes/admin.php
        // Load relationships
        $article->load(['author', 'reviewer', 'category', 'tags', 'series']);

        return view('admin.articles.show', compact('article'));
    }

    /**
     * Show the form for editing the specified article
     *
     * GET /su/portal/v1/admin/articles/{article}/edit
     *
     * @param Article $article Article model (route model binding)
     * @return View Article edit form
     */
    public function edit(Article $article): View
    {
        // Authorization is handled by middleware in routes/admin.php
        // Load relationships for form population
        $article->load(['author', 'category', 'tags', 'series']);

        return view('admin.articles.edit', compact('article'));
    }

    /**
     * Update the specified article
     *
     * Validates input, updates article, syncs tags, logs the action.
     * Captures old values for audit trail.
     *
     * PUT/PATCH /su/portal/v1/admin/articles/{article}
     *
     * @param UpdateArticleRequest $request Validated request
     * @param Article $article Article model (route model binding)
     * @return RedirectResponse Redirect to articles index with success message
     */
    public function update(UpdateArticleRequest $request, Article $article): RedirectResponse
    {
        // Authorization check

        try {
            // Capture old values before update
            $oldValues = $article->toArray();

            // Perform update
            $this->articleService->updateArticle($article->id, $request->validated());

            // Reload article with fresh data for media operations
            $article->refresh();

            // Handle featured image (file upload or media picker selection)
            if ($request->hasFile('featured_image')) {
                $this->articleService->handleFeaturedImage(
                    $article,
                    $request->file('featured_image')
                );
            } elseif ($request->has('featured_image_media_id')) {
                $this->articleService->handleFeaturedImage(
                    $article,
                    null,
                    $request->input('featured_image_media_id')
                );
            }

            // Handle OG image (file upload or media picker selection)
            if ($request->hasFile('og_image')) {
                $this->articleService->handleOgImage(
                    $article,
                    $request->file('og_image')
                );
            } elseif ($request->has('og_image_media_id')) {
                $this->articleService->handleOgImage(
                    $article,
                    null,
                    $request->input('og_image_media_id')
                );
            }

            // Handle gallery images (file uploads or media picker selections)
            if ($request->hasFile('gallery')) {
                $this->articleService->handleGalleryImages(
                    $article,
                    $request->file('gallery')
                );
            }
            if ($request->has('gallery_media_ids')) {
                $this->articleService->handleGalleryImages(
                    $article,
                    null,
                    $request->input('gallery_media_ids')
                );
            }

            // Sync tags if provided
            if ($request->has('tags')) {
                $this->articleService->syncTags($article->id, $request->input('tags'));
            }

            // Reload article with fresh data
            $article->refresh();
            $article->load(['author', 'category', 'tags']);

            // Log the update with diff
            $this->logger->logUpdate($article, $oldValues, $article->toArray(), $request->user());

            return redirect()
                ->route('admin.articles.index')
                ->with('success', __('Article updated successfully'));
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', __('Failed to update article: :message', ['message' => $e->getMessage()]));
        }
    }

    /**
     * Remove the specified article (soft delete)
     *
     * DELETE /su/portal/v1/admin/articles/{article}
     *
     * @param Article $article Article model (route model binding)
     * @return RedirectResponse Redirect to articles index with success message
     */
    public function destroy(Article $article): RedirectResponse
    {
        // Authorization check

        try {
            // Clear all media collections before deletion
            $this->articleService->clearMediaCollection($article, 'featured_image');
            $this->articleService->clearMediaCollection($article, 'og_image');
            $this->articleService->clearMediaCollection($article, 'gallery');

            // Perform soft deletion
            $this->articleService->deleteArticle($article->id);

            // Log the deletion
            $this->logger->logDelete($article, request()->user());

            return redirect()
                ->route('admin.articles.index')
                ->with('success', __('Article deleted successfully'));
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', __('Failed to delete article: :message', ['message' => $e->getMessage()]));
        }
    }

    /**
     * Publish an article
     *
     * Changes status to 'published' and sets publication date if not set.
     * Only admins and editors can publish articles.
     *
     * POST /su/portal/v1/admin/articles/{article}/publish
     *
     * @param Article $article Article model (route model binding)
     * @return RedirectResponse Redirect with success or error message
     */
    public function publish(Article $article): RedirectResponse
    {
        // Authorization check - only admins and editors can publish
        if (! request()->user()->hasAnyRole(['admin', 'super_admin', 'editor'])) {
            abort(403, __('Unauthorized to publish articles'));
        }

        try {
            $this->articleService->publishArticle($article->id);

            // Log the action
            $this->logger->logCustomAction(
                'published',
                $article,
                request()->user(),
                ['status' => $article->status],
                ['status' => 'published', 'published_at' => now()]
            );

            return redirect()
                ->back()
                ->with('success', __('Article published successfully'));
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', __('Failed to publish article: :message', ['message' => $e->getMessage()]));
        }
    }

    /**
     * Send article to review
     *
     * Changes status from 'draft' to 'under_review'.
     * Authors can submit their own articles for review.
     *
     * POST /su/portal/v1/admin/articles/{article}/send-to-review
     *
     * @param Article $article Article model (route model binding)
     * @return RedirectResponse Redirect with success or error message
     */
    public function sendToReview(Article $article): RedirectResponse
    {
        try {
            // Check if article is in draft status
            if ($article->status !== Article::STATUS_DRAFT) {
                return redirect()
                    ->back()
                    ->with('error', __('Only draft articles can be sent for review'));
            }

            // Check if user is the author or has admin role
            $user = request()->user();
            if ($article->user_id !== $user->id && ! $user->hasAnyRole(['admin', 'super_admin', 'editor'])) {
                abort(403, __('Unauthorized to submit this article for review'));
            }

            // Send to review
            $article->sendToReview();

            // Log the action
            $this->logger->logCustomAction(
                'sent_to_review',
                $article,
                $user,
                ['status' => Article::STATUS_DRAFT],
                ['status' => Article::STATUS_UNDER_REVIEW]
            );

            return redirect()
                ->back()
                ->with('success', __('Article submitted for review successfully'));
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', __('Failed to submit article for review: :message', ['message' => $e->getMessage()]));
        }
    }

    /**
     * Approve an article
     *
     * Changes status from 'under_review' to 'published'.
     * Only admins and editors can approve articles.
     * Sets reviewer_id to current user.
     *
     * POST /su/portal/v1/admin/articles/{article}/approve
     *
     * @param Article $article Article model (route model binding)
     * @return RedirectResponse Redirect with success or error message
     */
    public function approve(Article $article): RedirectResponse
    {
        // Authorization check - only admins and editors can approve
        $user = request()->user();
        if (! $user->hasAnyRole(['admin', 'super_admin', 'editor'])) {
            abort(403, __('Unauthorized to approve articles'));
        }

        try {
            // Check if article is under review
            if ($article->status !== Article::STATUS_UNDER_REVIEW) {
                return redirect()
                    ->back()
                    ->with('error', __('Only articles under review can be approved'));
            }

            // Approve article
            $article->approve($user);

            // Log the action
            $this->logger->logCustomAction(
                'approved',
                $article,
                $user,
                ['status' => Article::STATUS_UNDER_REVIEW, 'reviewer_id' => null],
                ['status' => Article::STATUS_PUBLISHED, 'reviewer_id' => $user->id, 'published_at' => now()]
            );

            return redirect()
                ->back()
                ->with('success', __('Article approved and published successfully'));
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', __('Failed to approve article: :message', ['message' => $e->getMessage()]));
        }
    }

    /**
     * Reject an article
     *
     * Changes status from 'under_review' to 'rejected'.
     * Only admins and editors can reject articles.
     * Sets reviewer_id to current user.
     *
     * POST /su/portal/v1/admin/articles/{article}/reject
     *
     * @param Request $request HTTP request with optional rejection reason
     * @param Article $article Article model (route model binding)
     * @return RedirectResponse Redirect with success or error message
     */
    public function reject(Request $request, Article $article): RedirectResponse
    {
        // Authorization check - only admins and editors can reject
        $user = $request->user();
        if (! $user->hasAnyRole(['admin', 'super_admin', 'editor'])) {
            abort(403, __('Unauthorized to reject articles'));
        }

        try {
            // Check if article is under review
            if ($article->status !== Article::STATUS_UNDER_REVIEW) {
                return redirect()
                    ->back()
                    ->with('error', __('Only articles under review can be rejected'));
            }

            // Reject article
            $article->reject($user);

            // Log the action with optional reason
            $this->logger->logCustomAction(
                'rejected',
                $article,
                $user,
                ['status' => Article::STATUS_UNDER_REVIEW, 'reviewer_id' => null],
                [
                    'status' => Article::STATUS_REJECTED,
                    'reviewer_id' => $user->id,
                    'rejection_reason' => $request->input('reason'),
                ]
            );

            return redirect()
                ->back()
                ->with('success', __('Article rejected successfully'));
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', __('Failed to reject article: :message', ['message' => $e->getMessage()]));
        }
    }

    /**
     * Bulk delete multiple articles
     *
     * Soft deletes multiple articles at once.
     * Requires 'ids' array in request.
     *
     * POST /su/portal/v1/admin/articles/bulk-delete
     *
     * @param Request $request HTTP request with article IDs
     * @return RedirectResponse|JsonResponse Redirect or JSON response
     */
    public function bulkDelete(Request $request): RedirectResponse|JsonResponse
    {
        // Validate input
        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:articles,id'],
        ]);

        try {
            $ids = $request->input('ids');
            $deleted = 0;

            foreach ($ids as $id) {
                try {
                    $article = Article::findOrFail($id);

                    // Authorization check for each article

                    $this->articleService->deleteArticle($id);
                    $deleted++;
                } catch (\Exception $e) {
                    // Continue deleting other articles
                    continue;
                }
            }

            // Log bulk action
            $this->logger->logBulkAction(
                'bulk_delete',
                Article::class,
                $ids,
                $request->user()
            );

            $message = __(':count article(s) deleted successfully', ['count' => $deleted]);

            // Return JSON for AJAX requests
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'deleted_count' => $deleted,
                ]);
            }

            return redirect()
                ->route('admin.articles.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('Failed to delete articles: :message', ['message' => $e->getMessage()]),
                ], 500);
            }

            return redirect()
                ->back()
                ->with('error', __('Failed to delete articles: :message', ['message' => $e->getMessage()]));
        }
    }

    /**
     * Restore a soft-deleted article
     *
     * POST /su/portal/v1/admin/articles/{id}/restore
     *
     * @param int $id Article ID to restore
     * @return RedirectResponse Redirect with success or error message
     */
    public function restore(int $id): RedirectResponse
    {
        try {
            $article = Article::withTrashed()->findOrFail($id);

            // Authorization check

            // Restore the article
            $article->restore();

            // Log the restoration
            $this->logger->logRestore($article, request()->user());

            return redirect()
                ->route('admin.articles.index')
                ->with('success', __('Article restored successfully'));
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', __('Failed to restore article: :message', ['message' => $e->getMessage()]));
        }
    }

    /**
     * Search articles (AJAX endpoint)
     *
     * GET /su/portal/v1/admin/articles/search
     *
     * @param Request $request HTTP request with search query
     * @return JsonResponse JSON response with search results
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'min:2'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        try {
            $query = $request->input('q');
            $perPage = $request->integer('per_page', 15);

            $articles = $this->articleService->searchArticles($query, $perPage);

            return response()->json([
                'success' => true,
                'data' => $articles->items(),
                'meta' => [
                    'current_page' => $articles->currentPage(),
                    'per_page' => $articles->perPage(),
                    'total' => $articles->total(),
                    'last_page' => $articles->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Search failed: :message', ['message' => $e->getMessage()]),
            ], 500);
        }
    }

    /**
     * Get all categories for selector (AJAX endpoint)
     *
     * GET /su/portal/v1/admin/articles/categories
     *
     * @return JsonResponse JSON response with categories
     */
    public function getCategories(): JsonResponse
    {
        try {
            $categories = \App\Models\Category::select('id', 'name', 'parent_id')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $categories,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Failed to fetch categories'),
            ], 500);
        }
    }

    /**
     * Get all tags for selector (AJAX endpoint)
     *
     * GET /su/portal/v1/admin/articles/tags
     *
     * @return JsonResponse JSON response with tags
     */
    public function getTags(): JsonResponse
    {
        try {
            $tags = \App\Models\Tag::select('id', 'name')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $tags,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Failed to fetch tags'),
            ], 500);
        }
    }

    /**
     * Get all series for selector (AJAX endpoint)
     *
     * GET /su/portal/v1/admin/articles/series
     *
     * @return JsonResponse JSON response with series
     */
    public function getSeries(): JsonResponse
    {
        try {
            $series = \App\Models\Series::select('id', 'title', 'slug')
                ->orderBy('title')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $series,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Failed to fetch series'),
            ], 500);
        }
    }

    /**
     * Get all authors (users with editor/admin roles) for selector (AJAX endpoint)
     *
     * GET /su/portal/v1/admin/articles/authors
     *
     * @return JsonResponse JSON response with authors
     */
    public function getAuthors(): JsonResponse
    {
        try {
            $authors = \App\Models\User::select('id', 'name', 'email')
                ->whereHas('roles', function ($query) {
                    $query->whereIn('name', ['admin', 'super_admin', 'editor']);
                })
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $authors,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Failed to fetch authors'),
            ], 500);
        }
    }
}
