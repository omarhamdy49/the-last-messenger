<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Blog\Models\Category;
use App\Domain\Blog\Models\Tag;
use App\Domain\Shared\Services\VersionService;
use App\Http\Requests\Admin\Article\StoreArticleRequest;
use App\Http\Requests\Admin\Article\UpdateArticleRequest;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use App\Models\ArticleLocale;
use App\Models\User;
use App\Repositories\ArticleRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ArticleController extends AdminController
{
    /**
     * Supported locales
     */
    protected array $locales = ['ar', 'en', 'fr', 'tr', 'ur'];

    public function __construct(
        protected ArticleRepository $repository,
        protected VersionService $versionService
    ) {
        parent::__construct();
    }

    /**
     * Display a listing of articles
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->authorizeAdmin('view articles');

        $filters = $this->getFilters($request, [
            'status',
            'category_id',
            'author_id',
            'is_featured',
            'is_pinned',
            'locale',
            'date_from',
            'date_to'
        ]);

        $search = $this->getSearchQuery($request);
        $pagination = $this->getPaginationParams($request);

        $articles = $this->repository->getAllWithFilters(
            $filters,
            $search,
            $pagination['per_page'],
            $pagination['sort'],
            $pagination['order']
        );

        if ($this->expectsJson($request)) {
            return $this->paginatedResponse($articles, 'Articles retrieved successfully');
        }

        $categories = Category::all();
        $authors = User::role('writer')->get();

        return view('admin.articles.index', compact('articles', 'categories', 'authors'));
    }

    /**
     * Show the form for creating a new article
     */
    public function create(): View
    {
        $this->authorizeAdmin('create articles');

        $categories = Category::all();
        $writers = User::role('writer')->get();
        $tags = Tag::where('type', 'article')->get();
        $locales = $this->locales;

        return view('admin.articles.create', compact('categories', 'writers', 'tags', 'locales'));
    }

    /**
     * Store a newly created article
     */
    public function store(StoreArticleRequest $request): RedirectResponse|JsonResponse
    {
        $this->authorizeAdmin('create articles');

        DB::beginTransaction();
        try {
            $validated = $request->validated();

            // Handle thumbnail upload
            $thumbPath = null;
            if ($request->hasFile('thumb')) {
                $thumbPath = $request->file('thumb')->store('articles/thumbs', 'public');
            }

            // Create article
            $article = Article::create([
                'thumb' => $thumbPath,
                'writer_id' => $validated['writer_id'] ?? auth()->id(),
                'read_time' => $validated['read_time'] ?? $this->calculateReadTime($validated['content']['ar'] ?? ''),
                'views' => 0,
                'is_featured' => $request->boolean('is_featured'),
                'is_pinned' => $request->boolean('is_pinned'),
                'category_id' => $validated['category_id'] ?? null,
                'hijri_date' => $validated['hijri_date'] ?? null,
            ]);

            // Create locales
            foreach ($this->locales as $locale) {
                if (isset($validated['title'][$locale])) {
                    $slug = $this->generateUniqueSlug($validated['title'][$locale], $locale);

                    $article->locales()->create([
                        'locale' => $locale,
                        'title' => $validated['title'][$locale],
                        'slug' => $validated['slug'][$locale] ?? $slug,
                        'description' => $validated['description'][$locale] ?? null,
                        'excerpt' => $validated['excerpt'][$locale] ?? null,
                        'content' => [
                            'body' => $validated['content'][$locale] ?? null,
                            'toc' => $this->generateTableOfContents($validated['content'][$locale] ?? ''),
                            'summary' => $validated['summary'][$locale] ?? null,
                        ],
                        'seo_title' => $validated['seo_title'][$locale] ?? null,
                        'seo_description' => $validated['seo_description'][$locale] ?? null,
                        'is_ready' => $validated['is_ready'][$locale] ?? false,
                    ]);
                }
            }

            // Attach primary category
            if (!empty($validated['category_id'])) {
                $article->categories()->attach($validated['category_id']);
            }

            // Attach additional categories
            if (!empty($validated['additional_categories'])) {
                $article->categories()->syncWithoutDetaching($validated['additional_categories']);
            }

            // Attach tags
            if (!empty($validated['tags'])) {
                $article->tags()->attach($validated['tags']);
            }

            // Attach gallery media
            if (!empty($validated['gallery'])) {
                $article->media()->syncWithoutDetaching($validated['gallery']);
            }

            // Handle scheduled publishing
            if ($validated['status'] === 'scheduled' && isset($validated['published_at'])) {
                // Queue the publishing job
                \App\Jobs\PublishScheduledContent::dispatch($article, $validated['published_at']);
            }

            // Create initial version
            $this->versionService->createVersion(
                $article,
                $article->toArray(),
                auth()->id(),
                'Initial version created'
            );

            DB::commit();

            $this->logActivity('created', 'Article', $article->id, [
                'title' => $validated['title']['ar'] ?? $validated['title']['en'],
            ]);

            if ($this->expectsJson(request())) {
                return $this->createdResponse(
                    new ArticleResource($article),
                    'Article created successfully'
                );
            }

            return redirect()
                ->route('admin.articles.index')
                ->with('success', 'Article created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();

            // Delete uploaded thumbnail if exists
            if (isset($thumbPath) && $thumbPath) {
                Storage::disk('public')->delete($thumbPath);
            }

            if ($this->expectsJson(request())) {
                return $this->serverErrorResponse('Failed to create article: ' . $e->getMessage());
            }

            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create article: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified article
     */
    public function show(Article $article): View|JsonResponse
    {
        $this->authorizeAdmin('view articles');

        $article->load(['locales', 'writer', 'category', 'categories', 'tags', 'media', 'versions']);

        if ($this->expectsJson(request())) {
            return $this->successResponse(
                new ArticleResource($article),
                'Article retrieved successfully'
            );
        }

        return view('admin.articles.show', compact('article'));
    }

    /**
     * Show the form for editing the article
     */
    public function edit(Article $article): View
    {
        $this->authorizeAdmin('edit articles');

        $article->load(['locales', 'categories', 'tags']);

        $categories = Category::all();
        $writers = User::role('writer')->get();
        $tags = Tag::where('type', 'article')->get();
        $locales = $this->locales;

        return view('admin.articles.edit', compact('article', 'categories', 'writers', 'tags', 'locales'));
    }

    /**
     * Update the specified article
     */
    public function update(UpdateArticleRequest $request, Article $article): RedirectResponse|JsonResponse
    {
        $this->authorizeAdmin('edit articles');

        DB::beginTransaction();
        try {
            $validated = $request->validated();
            $oldData = $article->toArray();

            // Handle thumbnail upload
            if ($request->hasFile('thumb')) {
                // Delete old thumbnail
                if ($article->thumb) {
                    Storage::disk('public')->delete($article->thumb);
                }
                $validated['thumb'] = $request->file('thumb')->store('articles/thumbs', 'public');
            }

            // Update article
            $article->update([
                'thumb' => $validated['thumb'] ?? $article->thumb,
                'writer_id' => $validated['writer_id'] ?? $article->writer_id,
                'read_time' => $validated['read_time'] ?? $article->read_time,
                'is_featured' => $request->boolean('is_featured'),
                'is_pinned' => $request->boolean('is_pinned'),
                'category_id' => $validated['category_id'] ?? null,
                'hijri_date' => $validated['hijri_date'] ?? null,
            ]);

            // Update or create locales
            foreach ($this->locales as $locale) {
                if (isset($validated['title'][$locale])) {
                    $slug = $validated['slug'][$locale] ?? $this->generateUniqueSlug($validated['title'][$locale], $locale, $article->id);

                    $article->locales()->updateOrCreate(
                        ['locale' => $locale],
                        [
                            'title' => $validated['title'][$locale],
                            'slug' => $slug,
                            'description' => $validated['description'][$locale] ?? null,
                            'excerpt' => $validated['excerpt'][$locale] ?? null,
                            'content' => [
                                'body' => $validated['content'][$locale] ?? null,
                                'toc' => $this->generateTableOfContents($validated['content'][$locale] ?? ''),
                                'summary' => $validated['summary'][$locale] ?? null,
                            ],
                            'seo_title' => $validated['seo_title'][$locale] ?? null,
                            'seo_description' => $validated['seo_description'][$locale] ?? null,
                            'is_ready' => $validated['is_ready'][$locale] ?? false,
                        ]
                    );
                }
            }

            // Sync categories
            $categoryIds = array_merge(
                [$validated['category_id']],
                $validated['additional_categories'] ?? []
            );
            $article->categories()->sync(array_filter($categoryIds));

            // Sync tags
            $article->tags()->sync($validated['tags'] ?? []);

            // Sync gallery media
            $article->media()->sync($validated['gallery'] ?? []);

            // Create version
            $this->versionService->createVersion(
                $article,
                $article->toArray(),
                auth()->id(),
                'Article updated'
            );

            DB::commit();

            $this->logActivity('updated', 'Article', $article->id, [
                'title' => $validated['title']['ar'] ?? $validated['title']['en'],
            ]);

            if ($this->expectsJson(request())) {
                return $this->successResponse(
                    new ArticleResource($article),
                    'Article updated successfully'
                );
            }

            return redirect()
                ->route('admin.articles.index')
                ->with('success', 'Article updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();

            if ($this->expectsJson(request())) {
                return $this->serverErrorResponse('Failed to update article: ' . $e->getMessage());
            }

            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to update article: ' . $e->getMessage()]);
        }
    }

    /**
     * Soft delete the specified article
     */
    public function destroy(Article $article): RedirectResponse|JsonResponse
    {
        $this->authorizeAdmin('delete articles');

        try {
            $article->delete();

            $this->logActivity('deleted', 'Article', $article->id, [
                'title' => $article->locales->first()->title ?? 'Untitled',
            ]);

            if ($this->expectsJson(request())) {
                return $this->successResponse(null, 'Article deleted successfully');
            }

            return redirect()
                ->route('admin.articles.index')
                ->with('success', 'Article deleted successfully!');

        } catch (\Exception $e) {
            if ($this->expectsJson(request())) {
                return $this->serverErrorResponse('Failed to delete article: ' . $e->getMessage());
            }

            return back()
                ->withErrors(['error' => 'Failed to delete article: ' . $e->getMessage()]);
        }
    }

    /**
     * Bulk delete multiple articles
     */
    public function bulkDestroy(Request $request): JsonResponse
    {
        $this->authorizeAdmin('delete articles');

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:articles,id',
        ]);

        $results = $this->handleBulkOperation($request, 'delete', function ($id) {
            $article = Article::findOrFail($id);
            $article->delete();
        });

        return $this->successResponse($results, "{$results['success']} articles deleted successfully");
    }

    /**
     * Bulk publish multiple articles
     */
    public function bulkPublish(Request $request): JsonResponse
    {
        $this->authorizeAdmin('edit articles');

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:articles,id',
        ]);

        $results = $this->handleBulkOperation($request, 'publish', function ($id) {
            $article = Article::findOrFail($id);
            $article->locales()->update(['is_ready' => true]);
        });

        return $this->successResponse($results, "{$results['success']} articles published successfully");
    }

    /**
     * Duplicate an article
     */
    public function duplicate(Article $article): RedirectResponse|JsonResponse
    {
        $this->authorizeAdmin('create articles');

        DB::beginTransaction();
        try {
            // Create a copy of the article
            $newArticle = $article->replicate();
            $newArticle->is_featured = false;
            $newArticle->is_pinned = false;
            $newArticle->views = 0;
            $newArticle->save();

            // Copy locales
            foreach ($article->locales as $locale) {
                $newLocale = $locale->replicate();
                $newLocale->article_id = $newArticle->id;
                $newLocale->slug = $locale->slug . '-copy-' . Str::random(6);
                $newLocale->is_ready = false;
                $newLocale->save();
            }

            // Copy relationships
            $newArticle->categories()->sync($article->categories->pluck('id'));
            $newArticle->tags()->sync($article->tags->pluck('id'));

            DB::commit();

            $this->logActivity('duplicated', 'Article', $newArticle->id, [
                'original_id' => $article->id,
            ]);

            if ($this->expectsJson(request())) {
                return $this->createdResponse(
                    new ArticleResource($newArticle),
                    'Article duplicated successfully'
                );
            }

            return redirect()
                ->route('admin.articles.edit', $newArticle)
                ->with('success', 'Article duplicated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();

            if ($this->expectsJson(request())) {
                return $this->serverErrorResponse('Failed to duplicate article: ' . $e->getMessage());
            }

            return back()
                ->withErrors(['error' => 'Failed to duplicate article: ' . $e->getMessage()]);
        }
    }

    /**
     * Preview an article (before publishing)
     */
    public function preview(Article $article, Request $request): View
    {
        $locale = $request->get('locale', 'ar');
        $articleLocale = $article->locales()->where('locale', $locale)->first();

        if (!$articleLocale) {
            abort(404, 'Article not available in this language');
        }

        return view('admin.articles.preview', compact('article', 'articleLocale'));
    }

    /**
     * Restore a soft-deleted article
     */
    public function restore(int $id): RedirectResponse|JsonResponse
    {
        $this->authorizeAdmin('edit articles');

        try {
            $article = Article::withTrashed()->findOrFail($id);
            $article->restore();

            $this->logActivity('restored', 'Article', $article->id);

            if ($this->expectsJson(request())) {
                return $this->successResponse(
                    new ArticleResource($article),
                    'Article restored successfully'
                );
            }

            return redirect()
                ->route('admin.articles.index')
                ->with('success', 'Article restored successfully!');

        } catch (\Exception $e) {
            if ($this->expectsJson(request())) {
                return $this->serverErrorResponse('Failed to restore article: ' . $e->getMessage());
            }

            return back()
                ->withErrors(['error' => 'Failed to restore article: ' . $e->getMessage()]);
        }
    }

    /**
     * Permanently delete an article
     */
    public function forceDelete(int $id): RedirectResponse|JsonResponse
    {
        $this->authorizeAdmin('delete articles');

        try {
            $article = Article::withTrashed()->findOrFail($id);

            // Delete thumbnail
            if ($article->thumb) {
                Storage::disk('public')->delete($article->thumb);
            }

            // Delete gallery images
            foreach ($article->gallery as $galleryItem) {
                if ($galleryItem->image) {
                    Storage::disk('public')->delete($galleryItem->image);
                }
            }

            $article->forceDelete();

            $this->logActivity('force_deleted', 'Article', $id);

            if ($this->expectsJson(request())) {
                return $this->successResponse(null, 'Article permanently deleted');
            }

            return redirect()
                ->route('admin.articles.index')
                ->with('success', 'Article permanently deleted!');

        } catch (\Exception $e) {
            if ($this->expectsJson(request())) {
                return $this->serverErrorResponse('Failed to permanently delete article: ' . $e->getMessage());
            }

            return back()
                ->withErrors(['error' => 'Failed to permanently delete article: ' . $e->getMessage()]);
        }
    }

    /**
     * Get article statistics
     */
    public function statistics(): JsonResponse
    {
        $stats = $this->repository->getStatistics();
        return $this->successResponse($stats, 'Statistics retrieved successfully');
    }

    /**
     * Toggle featured status
     */
    public function toggleFeature(Article $article): JsonResponse
    {
        $this->authorizeAdmin('edit articles');

        $article->update(['is_featured' => !$article->is_featured]);

        return $this->successResponse([
            'is_featured' => $article->is_featured
        ], 'Featured status updated');
    }

    /**
     * Toggle pinned status
     */
    public function togglePin(Article $article): JsonResponse
    {
        $this->authorizeAdmin('edit articles');

        $article->update(['is_pinned' => !$article->is_pinned]);

        return $this->successResponse([
            'is_pinned' => $article->is_pinned
        ], 'Pinned status updated');
    }

    /**
     * Increment article views
     */
    public function incrementViews(Article $article): JsonResponse
    {
        $this->repository->incrementViews($article->id);

        return $this->successResponse([
            'views' => $article->fresh()->views
        ], 'Views incremented');
    }

    /**
     * Get trashed articles
     */
    public function trash(Request $request): View|JsonResponse
    {
        $this->authorizeAdmin('view articles');

        $articles = Article::onlyTrashed()
            ->with(['locales', 'writer', 'category'])
            ->orderBy('deleted_at', 'desc')
            ->paginate(15);

        if ($this->expectsJson($request)) {
            return $this->paginatedResponse($articles, 'Trashed articles retrieved');
        }

        return view('admin.articles.trash', compact('articles'));
    }

    /**
     * Generate unique slug
     */
    protected function generateUniqueSlug(string $title, string $locale, ?int $excludeArticleId = null): string
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $counter = 1;

        while (true) {
            $query = ArticleLocale::where('slug', $slug)->where('locale', $locale);

            if ($excludeArticleId) {
                $query->where('article_id', '!=', $excludeArticleId);
            }

            if (!$query->exists()) {
                break;
            }

            $slug = $originalSlug . '-' . $counter++;
        }

        return $slug;
    }

    /**
     * Calculate read time from content
     */
    protected function calculateReadTime(string $content): int
    {
        $wordCount = str_word_count(strip_tags($content));
        return max(1, (int) ceil($wordCount / 200)); // Assuming 200 words per minute
    }

    /**
     * Generate table of contents from HTML content
     */
    protected function generateTableOfContents(string $content): array
    {
        $toc = [];

        // Extract headings using regex
        preg_match_all('/<h([2-3])[^>]*>(.*?)<\/h[2-3]>/i', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $index => $match) {
            $level = (int) $match[1];
            $text = strip_tags($match[2]);
            $id = Str::slug($text);

            $toc[] = [
                'id' => $id,
                'text' => $text,
                'level' => $level,
            ];
        }

        return $toc;
    }
}
