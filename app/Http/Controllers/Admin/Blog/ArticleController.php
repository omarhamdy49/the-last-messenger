<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Blog;

use App\Domain\Blog\Actions\CreateArticleAction;
use App\Domain\Blog\Actions\UpdateArticleAction;
use App\Domain\Blog\DTOs\CreateArticleData;
use App\Domain\Blog\DTOs\UpdateArticleData;
use App\Domain\Shared\Services\VersionService;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Article\StoreArticleRequest;
use App\Http\Requests\Admin\Article\UpdateArticleRequest;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use App\Models\ArticleLocale;
use App\Repositories\ArticleRepository;
use App\Services\MediaService;
use App\Utils\DateUtil;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ArticleController extends AdminController
{
    protected array $locales;

    public function __construct(
        protected ArticleRepository $repository,
        protected VersionService $versionService,
        private readonly CreateArticleAction $createAction,
        private readonly UpdateArticleAction $updateAction,
        private readonly MediaService $mediaService,
    ) {
        parent::__construct();

        $this->locales = array_keys(config('app.supported_locales', ['ar', 'en']));
    }

    /**
     * Display a listing of articles.
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->authorizeAdmin('view articles');

        $filters = $this->getFilters($request, [
            'status', 'category_id', 'author_id', 'is_featured', 'is_pinned', 'locale', 'date_from', 'date_to',
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

        if (method_exists($articles, 'getCollection')) {
            $articles->getCollection()->load([
                'locales:id,article_id,locale,title,slug,is_ready',
                'writer:id,name',
                'category:id,slug,type,status',
                'category.translations:id,category_id,locale,name',
            ]);
        }

        $categories = $this->repository->getFormCategories();
        $authors = $this->repository->getFormWriters();
        $articleCounts = $this->repository->getIndexStats();

        $stats = [
            'total' => (int) ($articleCounts->total ?? 0),
            'published' => (int) ($articleCounts->published ?? 0),
            'published_change' => 0,
            'drafts' => (int) ($articleCounts->drafts ?? 0),
            'scheduled' => (int) ($articleCounts->scheduled ?? 0),
            'featured' => (int) ($articleCounts->featured ?? 0),
        ];

        $articles = $this->transformArticlesForTable($articles);

        return view('admin.articles.index', compact('articles', 'categories', 'authors', 'stats'));
    }

    /**
     * Show the form for creating a new article.
     */
    public function create(): View
    {
        $this->authorizeAdmin('create articles');

        $categories = $this->repository->getFormCategories();
        $writers = $this->repository->getFormWriters();
        $tags = $this->repository->getFormTags();
        $locales = $this->locales;
        $currentHijriDate = DateUtil::getCurrentHijriDate();

        return view('admin.articles.create', compact('categories', 'writers', 'tags', 'locales', 'currentHijriDate'));
    }

    /**
     * Store a newly created article.
     */
    public function store(StoreArticleRequest $request): RedirectResponse|JsonResponse
    {
        $this->authorizeAdmin('create articles');

        $libraryThumb = $this->resolveLibraryThumb($request);
        $libraryCover = $this->resolveLibraryThumb($request, 'cover');
        $libraryGallery = $this->resolveLibraryGallery($request);

        try {
            $article = $this->createAction->execute(
                CreateArticleData::fromRequest($request, $libraryThumb, $libraryGallery, $libraryCover),
                $this->locales
            );

            $this->logActivity('created', 'Article', $article->id, [
                'title' => $request->validated()['title']['ar'] ?? ($request->validated()['title']['en'] ?? null),
            ]);

            $article->load($this->articleRelations());

            if ($this->expectsJson(request())) {
                return $this->createdResponse(new ArticleResource($article), 'Article created successfully');
            }

            return redirect()
                ->route('admin.articles.index')
                ->with('success', __('articles.messages.created'));

        } catch (\Throwable $e) {
            Log::error('article.create_failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($this->expectsJson(request())) {
                return $this->serverErrorResponse(__('admin.article_create_failed'));
            }

            return back()->withInput()->withErrors(['error' => __('admin.article_create_failed')]);
        } finally {
            $this->cleanupTemporaryThumb($libraryThumb);
            $this->cleanupTemporaryThumb($libraryCover);
            $this->cleanupTemporaryGallery($libraryGallery);
        }
    }

    /**
     * Display the specified article.
     */
    public function show(Article $article): View|JsonResponse
    {
        $this->authorizeAdmin('view articles');

        $article->load($this->articleRelations());

        if ($this->expectsJson(request())) {
            return $this->successResponse(new ArticleResource($article), 'Article retrieved successfully');
        }

        return view('admin.articles.show', compact('article'));
    }

    /**
     * Show the form for editing the article.
     */
    public function edit(Article $article): View
    {
        $this->authorizeAdmin('edit articles');

        $article->load([
            'locales:id,article_id,locale,title,slug,description,excerpt,content,seo_metadata,is_ready',
            'categories:id,slug,type,status',
            'categories.translations:id,category_id,locale,name',
            'tags:id,slug,type',
            'tags.translations:id,tag_id,locale,name',
            // public_key/disk/metadata feed the thumbnail_url accessor
            'mediaItems:id,public_key,disk,metadata,filename,original_filename,path,mime_type,size,type',
            'gallery:id,article_id,image,caption,order',
            'episodes:id,program_id,slug,video_url,season_number,episode_number,is_published',
            'episodes.translations:id,media_episode_id,locale,title',
            'episodes.program:id,slug',
        ]);

        $categories = $this->repository->getFormCategories();
        $writers = $this->repository->getFormWriters();
        $tags = $this->repository->getFormTags();
        $locales = $this->locales;
        $currentHijriDate = DateUtil::getCurrentHijriDate();
        $formArticle = $this->transformArticleForForm($article);

        return view('admin.articles.edit', compact(
            'article', 'categories', 'writers', 'tags', 'locales', 'currentHijriDate', 'formArticle'
        ));
    }

    /**
     * Update the specified article.
     */
    public function update(UpdateArticleRequest $request, Article $article): RedirectResponse|JsonResponse
    {
        $this->authorizeAdmin('edit articles');

        $libraryThumb = $this->resolveLibraryThumb($request);
        $libraryCover = $this->resolveLibraryThumb($request, 'cover');
        $libraryGallery = $request->has('gallery') ? $this->resolveLibraryGallery($request) : null;

        try {
            $this->updateAction->execute(
                $article,
                UpdateArticleData::fromRequest($request, $article, $libraryThumb, $libraryGallery, $libraryCover),
                $this->locales
            );

            $this->logActivity('updated', 'Article', $article->id, [
                'title' => $request->validated()['title']['ar'] ?? ($request->validated()['title']['en'] ?? null),
            ]);

            $article->load($this->articleRelations());

            if ($this->expectsJson(request())) {
                return $this->successResponse(new ArticleResource($article), 'Article updated successfully');
            }

            return redirect()
                ->route('admin.articles.index')
                ->with('success', __('articles.messages.updated'));

        } catch (\Throwable $e) {
            Log::error('article.update_failed', [
                'user_id' => auth()->id(),
                'article_id' => $article->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($this->expectsJson(request())) {
                return $this->serverErrorResponse(__('admin.article_update_failed'));
            }

            return back()->withInput()->withErrors(['error' => __('admin.article_update_failed')]);
        } finally {
            $this->cleanupTemporaryThumb($libraryThumb);
            $this->cleanupTemporaryThumb($libraryCover);
            $this->cleanupTemporaryGallery($libraryGallery ?? []);
        }
    }

    /**
     * Resolve the thumbnail to store, always via the media library. A library
     * pick (thumb_id) is materialized as a plaintext copy; a direct `thumb`
     * upload is first registered in the library (which applies the library's
     * optimization and size presets) and then materialized the same way. The
     * caller owns the returned file's temp path and must clean it up after
     * the action runs.
     */
    private function resolveLibraryThumb(StoreArticleRequest|UpdateArticleRequest $request, string $field = 'thumb'): ?UploadedFile
    {
        if ($request->hasFile($field)) {
            $media = $this->mediaService->upload($request->file($field), [
                'collection_name' => 'articles',
            ]);
            $copy = $this->mediaService->temporaryOriginalCopyById($media->id);

            // Fall back to the raw upload if the fresh library copy can't be read.
            return $copy === null
                ? $request->file($field)
                : new UploadedFile($copy->path, $copy->filename, null, null, true);
        }

        if (! $request->filled($field.'_id')) {
            return null;
        }

        $copy = $this->mediaService->temporaryOriginalCopyById((int) $request->input($field.'_id'));
        if ($copy === null) {
            return null;
        }

        return new UploadedFile($copy->path, $copy->filename, null, null, true);
    }

    private function cleanupTemporaryThumb(?UploadedFile $thumb): void
    {
        $path = $thumb?->getPathname();
        if ($path !== null && is_file($path)) {
            @unlink($path);
        }
    }

    /**
     * The gallery tab posts media-library ids, not uploads. Decrypt each one to
     * a disposable temp file so the action can store it like any other upload.
     * Order is preserved: the editor's arrangement is the gallery order.
     *
     * @return array<int, array{media_id: int, file: UploadedFile}>
     */
    private function resolveLibraryGallery(StoreArticleRequest|UpdateArticleRequest $request): array
    {
        $mediaIds = $request->validated()['gallery'] ?? [];

        if (! is_array($mediaIds) || $mediaIds === []) {
            return [];
        }

        $items = [];

        foreach ($mediaIds as $mediaId) {
            $copy = $this->mediaService->temporaryOriginalCopyById((int) $mediaId);

            if ($copy === null) {
                continue;
            }

            $items[] = [
                'media_id' => (int) $mediaId,
                'file' => new UploadedFile($copy->path, $copy->filename, null, null, true),
            ];
        }

        return $items;
    }

    /**
     * @param  array<int, array{media_id: int, file: UploadedFile}>  $items
     */
    private function cleanupTemporaryGallery(array $items): void
    {
        foreach ($items as $item) {
            $path = $item['file']->getPathname();
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    /**
     * Soft delete the specified article.
     */
    public function destroy(Article $article): RedirectResponse|JsonResponse
    {
        $this->authorizeAdmin('delete articles');

        try {
            $title = $article->locales->first()?->title ?? 'Untitled';
            $article->delete();

            $this->logActivity('deleted', 'Article', $article->id, ['title' => $title]);

            if ($this->expectsJson(request())) {
                return $this->successResponse(null, 'Article deleted successfully');
            }

            return redirect()
                ->route('admin.articles.index')
                ->with('success', __('articles.messages.deleted'));

        } catch (\Throwable $e) {
            Log::error('article.delete_failed', [
                'user_id' => auth()->id(),
                'article_id' => $article->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($this->expectsJson(request())) {
                return $this->serverErrorResponse(__('admin.article_delete_failed'));
            }

            return back()->withErrors(['error' => __('admin.article_delete_failed')]);
        }
    }

    /**
     * Bulk delete multiple articles.
     */
    public function bulkDestroy(Request $request): JsonResponse
    {
        $this->authorizeAdmin('delete articles');

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:articles,id',
        ]);

        $results = $this->handleBulkOperation($request, 'delete', function (int $id): void {
            $this->repository->deleteById($id);
        });

        return $this->successResponse($results, "{$results['success']} articles deleted successfully");
    }

    /**
     * Bulk publish multiple articles.
     */
    public function bulkPublish(Request $request): JsonResponse
    {
        $this->authorizeAdmin('edit articles');

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:articles,id',
        ]);

        $results = $this->handleBulkOperation($request, 'publish', function (int $id): void {
            $this->repository->publishById($id);
        });

        return $this->successResponse($results, "{$results['success']} articles published successfully");
    }

    /**
     * Duplicate an article.
     */
    public function duplicate(Article $article): RedirectResponse|JsonResponse
    {
        $this->authorizeAdmin('create articles');

        try {
            $article->load(['locales', 'categories', 'tags']);

            $newArticle = $article->replicate();
            $newArticle->is_featured = false;
            $newArticle->is_pinned = false;
            $newArticle->views = 0;
            $newArticle->save();

            foreach ($article->locales as $locale) {
                $newLocale = $locale->replicate();
                $newLocale->article_id = $newArticle->id;
                $newLocale->slug = $locale->slug.'-copy-'.Str::random(6);
                $newLocale->is_ready = false;
                $newLocale->save();
            }

            $newArticle->categories()->sync($article->categories->pluck('id'));
            $newArticle->tags()->sync($article->tags->pluck('id'));

            $this->logActivity('duplicated', 'Article', $newArticle->id, [
                'original_id' => $article->id,
            ]);

            if ($this->expectsJson(request())) {
                return $this->createdResponse(new ArticleResource($newArticle), 'Article duplicated successfully');
            }

            return redirect()
                ->route('admin.articles.edit', $newArticle)
                ->with('success', 'Article duplicated successfully!');

        } catch (\Throwable $e) {
            Log::error('article.duplicate_failed', [
                'user_id' => auth()->id(),
                'article_id' => $article->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($this->expectsJson(request())) {
                return $this->serverErrorResponse(__('admin.article_duplicate_failed'));
            }

            return back()->withErrors(['error' => __('admin.article_duplicate_failed')]);
        }
    }

    /**
     * Preview an article (before publishing).
     */
    public function preview(Article $article, Request $request): View
    {
        $locale = $request->get('locale', 'ar');
        abort_unless(in_array($locale, enabled_content_locales(), true), 404);
        $article->load($this->articleRelations());
        abort_unless($article->locales->contains('locale', $locale), 404, 'Article not available in this language');

        // One preview surface: the show page, opened on the requested language.
        return view('admin.articles.show', ['article' => $article, 'initialLocale' => $locale]);
    }

    /**
     * Restore a soft-deleted article.
     */
    public function restore(int $id): RedirectResponse|JsonResponse
    {
        $this->authorizeAdmin('edit articles');

        try {
            $article = $this->repository->restoreById($id);

            $this->logActivity('restored', 'Article', $article->id);

            if ($this->expectsJson(request())) {
                return $this->successResponse(new ArticleResource($article), 'Article restored successfully');
            }

            return redirect()
                ->route('admin.articles.index')
                ->with('success', 'Article restored successfully!');

        } catch (\Throwable $e) {
            Log::error('article.restore_failed', [
                'user_id' => auth()->id(),
                'article_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($this->expectsJson(request())) {
                return $this->serverErrorResponse(__('admin.article_restore_failed'));
            }

            return back()->withErrors(['error' => __('admin.article_restore_failed')]);
        }
    }

    /**
     * Permanently delete an article.
     */
    public function forceDelete(int $id): RedirectResponse|JsonResponse
    {
        $this->authorizeAdmin('delete articles');

        try {
            $files = $this->repository->forceDeleteById($id);

            foreach (array_filter([$files['thumb'], $files['cover']]) as $image) {
                Storage::disk('public')->delete($image);
            }

            foreach ($files['gallery'] as $galleryImage) {
                Storage::disk('public')->delete($galleryImage);
            }

            $this->logActivity('force_deleted', 'Article', $id);

            if ($this->expectsJson(request())) {
                return $this->successResponse(null, 'Article permanently deleted');
            }

            return redirect()
                ->route('admin.articles.index')
                ->with('success', 'Article permanently deleted!');

        } catch (\Throwable $e) {
            Log::error('article.force_delete_failed', [
                'user_id' => auth()->id(),
                'article_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($this->expectsJson(request())) {
                return $this->serverErrorResponse(__('admin.article_force_delete_failed'));
            }

            return back()->withErrors(['error' => __('admin.article_force_delete_failed')]);
        }
    }

    /**
     * Get article statistics.
     */
    public function statistics(): JsonResponse
    {
        $stats = $this->repository->getStatistics();

        return $this->successResponse($stats, 'Statistics retrieved successfully');
    }

    /**
     * Toggle featured status.
     */
    public function toggleFeature(Article $article): JsonResponse
    {
        $this->authorizeAdmin('edit articles');

        $article->update(['is_featured' => ! $article->is_featured]);

        return $this->successResponse(['is_featured' => $article->is_featured], 'Featured status updated');
    }

    /**
     * Toggle pinned status.
     */
    public function togglePin(Article $article): JsonResponse
    {
        $this->authorizeAdmin('edit articles');

        $article->update(['is_pinned' => ! $article->is_pinned]);

        return $this->successResponse(['is_pinned' => $article->is_pinned], 'Pinned status updated');
    }

    /**
     * Increment article views.
     */
    public function incrementViews(Article $article): JsonResponse
    {
        $this->repository->incrementViews($article->id);

        return $this->successResponse(['views' => $article->fresh()->views], 'Views incremented');
    }

    /**
     * Get trashed articles.
     */
    public function trash(Request $request): View|JsonResponse
    {
        $this->authorizeAdmin('view articles');

        $articles = $this->repository->getTrashed(15);

        if ($this->expectsJson($request)) {
            return $this->paginatedResponse($articles, 'Trashed articles retrieved');
        }

        return view('admin.articles.trash', compact('articles'));
    }

    // -------------------------------------------------------------------------
    // Protected helpers (presentation / view-model concerns)
    // -------------------------------------------------------------------------

    /**
     * Relations required for admin article detail responses.
     *
     * @return array<int, string>
     */
    /**
     * Poster frame for an episode's YouTube URL, or null when the URL is not a
     * recognisable YouTube link.
     */
    private function episodeThumb(string $url): ?string
    {
        $patterns = [
            '~youtu\\.be/([A-Za-z0-9_-]{11})~',
            '~youtube(?:-nocookie)?\\.com/(?:embed|v|shorts|live)/([A-Za-z0-9_-]{11})~',
            '~youtube(?:-nocookie)?\\.com/watch\\?(?:.*&)?v=([A-Za-z0-9_-]{11})~',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $m) === 1) {
                return 'https://i.ytimg.com/vi/'.$m[1].'/mqdefault.jpg';
            }
        }

        return null;
    }

    protected function articleRelations(): array
    {
        return [
            'locales:id,article_id,locale,title,slug,description,excerpt,content,seo_metadata,is_ready',
            'writer:id,name',
            'category:id,slug,type,status',
            'category.translations:id,category_id,locale,name',
            'categories:id,slug,type,status',
            'categories.translations:id,category_id,locale,name',
            'tags:id,slug,type',
            'tags.translations:id,tag_id,locale,name',
            // public_key/disk/metadata feed the thumbnail_url accessor
            'mediaItems:id,public_key,disk,metadata,filename,original_filename,path,mime_type,size,type',
            'gallery:id,article_id,image,caption,order',
            'episodes:id,program_id,slug,video_url,season_number,episode_number,is_published',
            'episodes.translations:id,media_episode_id,locale,title',
            'episodes.program:id,slug',
            'versions:id,versionable_id,versionable_type,created_at',
        ];
    }

    /**
     * Transform articles paginator to flat format suitable for the data-table component.
     */
    protected function transformArticlesForTable(mixed $paginator): mixed
    {
        $currentLocale = app()->getLocale();

        return $paginator->through(function (Article $article) use ($currentLocale): array {
            $primaryLocale = $article->locales->firstWhere('locale', $currentLocale)
                ?? $article->locales->firstWhere('locale', 'ar')
                ?? $article->locales->first();

            $categoryName = $this->resolveCategoryName($article, $currentLocale);

            return [
                'id' => $article->id,
                'title' => $primaryLocale?->title ?? 'Untitled',
                'thumb' => $article->thumb ? asset('storage/'.$article->thumb) : null,
                'writer' => $article->writer?->name ?? '-',
                'category' => $categoryName,
                'category_id' => $article->category_id,
                'read_time' => $article->read_time,
                'views' => $article->views,
                'is_featured' => $article->is_featured,
                'is_pinned' => $article->is_pinned,
                'status' => $article->status ?? 'draft',
                'scheduled_at' => $article->scheduled_at?->format('Y-m-d H:i'),
                'created_at' => $article->created_at?->toIso8601String(),
                'updated_at' => $article->updated_at?->toIso8601String(),
            ];
        });
    }

    /**
     * Resolve a display-ready category name for the given article and locale.
     */
    private function resolveCategoryName(Article $article, string $locale): string
    {
        if (! $article->category) {
            return '-';
        }

        if (method_exists($article->category, 'getNameInLanguage')) {
            return $article->category->getNameInLanguage($locale)
                ?? $article->category->getNameInLanguage('ar')
                ?? '-';
        }

        return $article->category->name ?? $article->category->slug ?? '-';
    }

    /**
     * Normalize an article for the Alpine edit form payload.
     *
     * @return array<string, mixed>
     */
    protected function transformArticleForForm(Article $article): array
    {
        return [
            'id' => $article->id,
            'thumb' => $article->thumb,
            'cover' => $article->featured_image,
            'writer_id' => $article->writer_id,
            'category_id' => $article->category_id,
            'is_featured' => $article->is_featured,
            'is_pinned' => $article->is_pinned,
            'hijri_date' => $article->hijri_date,
            'read_time' => $article->read_time,
            'status' => $article->status,
            'scheduled_at' => $article->scheduled_at?->format('Y-m-d\TH:i'),
            // `id` is the media-library id the picker works in, not the
            // articles_gallery row id — the form posts these back as gallery[].
            'gallery' => $article->gallery->map(fn ($item) => [
                'id' => $item->media_id,
                'gallery_id' => $item->id,
                'url' => $item->image ? Storage::disk('public')->url($item->image) : null,
                'image' => $item->image,
                'caption' => $item->caption,
                'order' => $item->order,
            ])->filter(fn (array $item): bool => $item['id'] !== null)->values()->all(),
            'media_items' => $article->mediaItems->map(fn ($media) => [
                'id' => $media->id,
                'thumbnail_url' => $media->thumbnail_url,
                'filename' => $media->filename,
                'original_filename' => $media->original_filename,
                'path' => $media->path,
                'mime_type' => $media->mime_type,
                'size' => $media->size,
                'type' => $media->type,
            ])->values()->all(),
            // Video queue, already in pivot order — the picker renders these
            // as the current selection and posts them back as episodes[].
            'episodes' => $article->episodes->map(fn ($episode) => [
                'id' => $episode->id,
                'title' => $episode->getTranslation('title', app()->getLocale())
                    ?: $episode->getTranslation('title', 'ar')
                    ?: $episode->slug,
                'program' => $episode->program?->slug,
                'season' => $episode->season_number,
                'episode' => $episode->episode_number,
                'thumb' => $this->episodeThumb((string) $episode->video_url),
            ])->values()->all(),
            'locales' => $article->locales->mapWithKeys(fn (ArticleLocale $locale) => [
                $locale->locale => [
                    'title' => $locale->title,
                    'slug' => $locale->slug,
                    'description' => $locale->description,
                    'excerpt' => $locale->excerpt,
                    'content' => $locale->content,
                    'seo' => [
                        'title' => $locale->seo_title,
                        'description' => $locale->seo_description,
                    ],
                    'is_ready' => $locale->is_ready,
                ],
            ])->all(),
        ];
    }

    /**
     * Search articles for AJAX selectors (e.g. series article picker).
     *
     * GET /su/portal/v1/admin/articles/search?q=...
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        $excludeIds = $request->input('exclude', []);

        $articles = $this->repository->searchForSelector($query, $excludeIds, 20)
            ->map(function ($article) {
                $arLocale = $article->locales->firstWhere('locale', 'ar');
                $enLocale = $article->locales->firstWhere('locale', 'en');

                return [
                    'id' => $article->id,
                    'title' => $arLocale?->title ?? $enLocale?->title ?? __('admin.untitled'),
                    'status' => $article->status,
                ];
            });

        return response()->json(['data' => $articles]);
    }
}
