<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Blog\Services\ArticleService;
use App\Domain\Shared\Services\AdminLoggerService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreArticleRequest;
use App\Http\Requests\Admin\UpdateArticleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin Article Controller
 * Handles article management for admin portal
 */
class ArticleController extends Controller
{
    /**
     * Constructor
     */
    public function __construct(
        protected ArticleService $articleService,
        protected AdminLoggerService $logger
    ) {}

    /**
     * Display a listing of all articles (including draft and archived)
     *
     * GET /su/portal/v1/admin/articles
     */
    public function index(Request $request): View
    {
        $perPage = $request->integer('per_page', 50);
        $status = $request->input('status');
        $categoryId = $request->integer('category_id');
        $search = $request->input('search');

        $articles = $this->articleService->paginate(
            $perPage,
            ['*'],
            ['author', 'category', 'tags']
        );

        // Calculate stats
        $stats = [
            'total' => $this->articleService->count(),
            'published' => $this->articleService->countByStatus('published'),
            'draft' => $this->articleService->countByStatus('draft'),
            'archived' => $this->articleService->countByStatus('archived'),
        ];

        return view('admin.articles.index', compact('articles', 'stats'));
    }

    /**
     * Show the form for creating a new article
     *
     * GET /su/portal/v1/admin/articles/create
     */
    public function create(): View
    {
        return view('admin.articles.create');
    }

    /**
     * Store a newly created article
     *
     * POST /su/portal/v1/admin/articles
     */
    public function store(StoreArticleRequest $request): RedirectResponse
    {
        $article = $this->articleService->createArticle($request->validated());

        if ($request->has('tags')) {
            $this->articleService->syncTags($article->id, $request->input('tags'));
        }

        // Log the creation
        $this->logger->logCreate($article, $request->user());

        return redirect()
            ->route('admin.articles.index')
            ->with('success', 'تم إنشاء المقال بنجاح');
    }

    /**
     * Display the specified article
     *
     * GET /su/portal/v1/admin/articles/{id}
     */
    public function show(int $id): View
    {
        $article = $this->articleService->find($id, ['author', 'category', 'tags', 'series']);

        if (! $article) {
            abort(404, 'Article not found');
        }

        return view('admin.articles.show', compact('article'));
    }

    /**
     * Show the form for editing the specified article
     *
     * GET /su/portal/v1/admin/articles/{id}/edit
     */
    public function edit(int $id): View
    {
        $article = $this->articleService->find($id, ['author', 'category', 'tags', 'series']);

        if (! $article) {
            abort(404, 'Article not found');
        }

        return view('admin.articles.edit', compact('article'));
    }

    /**
     * Update the specified article
     *
     * PUT/PATCH /su/portal/v1/admin/articles/{id}
     */
    public function update(UpdateArticleRequest $request, int $id): RedirectResponse
    {
        // Get old values before update
        $oldArticle = $this->articleService->find($id);

        if (! $oldArticle) {
            abort(404, 'Article not found');
        }

        $oldValues = $oldArticle->toArray();

        // Perform update
        $this->articleService->updateArticle($id, $request->validated());

        if ($request->has('tags')) {
            $this->articleService->syncTags($id, $request->input('tags'));
        }

        $article = $this->articleService->find($id, ['author', 'category', 'tags']);

        // Log the update with diff
        $this->logger->logUpdate($article, $oldValues, $article->toArray(), $request->user());

        return redirect()
            ->route('admin.articles.index')
            ->with('success', 'تم تحديث المقال بنجاح');
    }

    /**
     * Remove the specified article
     *
     * DELETE /su/portal/v1/admin/articles/{id}
     */
    public function destroy(int $id): RedirectResponse
    {
        // Get model before deletion
        $article = $this->articleService->find($id);

        if (! $article) {
            abort(404, 'Article not found');
        }

        // Perform deletion
        $this->articleService->deleteArticle($id);

        // Log the deletion
        $this->logger->logDelete($article, request()->user());

        return redirect()
            ->route('admin.articles.index')
            ->with('success', 'تم حذف المقال بنجاح');
    }

    /**
     * Bulk delete articles
     *
     * POST /su/portal/v1/admin/articles/bulk-delete
     */
    public function bulkDelete(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:articles,id'],
        ]);

        $ids = $request->input('ids');
        $deleted = 0;
        foreach ($ids as $id) {
            try {
                $this->articleService->delete($id);
                $deleted++;
            } catch (\Exception $e) {
                // Continue deleting other articles
            }
        }

        // Log bulk action
        $this->logger->logBulkAction(
            'bulk_delete',
            \App\Domain\Blog\Models\Article::class,
            $ids,
            $request->user()
        );

        return redirect()
            ->route('admin.articles.index')
            ->with('success', "تم حذف {$deleted} مقال بنجاح");
    }

    /**
     * Bulk publish articles
     *
     * POST /su/portal/v1/admin/articles/bulk-publish
     */
    public function bulkPublish(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:articles,id'],
        ]);

        $ids = $request->input('ids');
        $published = 0;
        foreach ($ids as $id) {
            try {
                $this->articleService->publishArticle($id);
                $published++;
            } catch (\Exception $e) {
                // Continue publishing other articles
            }
        }

        // Log bulk action
        $this->logger->logBulkAction(
            'bulk_publish',
            \App\Domain\Blog\Models\Article::class,
            $ids,
            $request->user()
        );

        return redirect()
            ->route('admin.articles.index')
            ->with('success', "تم نشر {$published} مقال بنجاح");
    }

    /**
     * Bulk archive articles
     *
     * POST /su/portal/v1/admin/articles/bulk-archive
     */
    public function bulkArchive(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:articles,id'],
        ]);

        $ids = $request->input('ids');
        $archived = 0;
        foreach ($ids as $id) {
            try {
                $this->articleService->archiveArticle($id);
                $archived++;
            } catch (\Exception $e) {
                // Continue archiving other articles
            }
        }

        // Log bulk action
        $this->logger->logBulkAction(
            'bulk_archive',
            \App\Domain\Blog\Models\Article::class,
            $ids,
            $request->user()
        );

        return redirect()
            ->route('admin.articles.index')
            ->with('success', "تم أرشفة {$archived} مقال بنجاح");
    }

    /**
     * Search articles (AJAX endpoint)
     *
     * GET /su/portal/v1/admin/articles/search
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => ['required', 'string', 'min:2'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

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
    }
}
