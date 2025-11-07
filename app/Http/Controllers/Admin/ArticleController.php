<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Blog\Models\Category;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleLocale;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ArticleController extends Controller
{
    /**
     * Supported locales
     */
    protected array $locales = ['ar', 'en', 'fr', 'tr', 'ur'];

    /**
     * Display a listing of articles
     */
    public function index(Request $request): View
    {
        $query = Article::with(['locales', 'writer', 'category', 'categories'])
            ->orderBy('created_at', 'desc');

        // Filter by featured
        if ($request->has('featured')) {
            $query->where('is_featured', true);
        }

        // Filter by pinned
        if ($request->has('pinned')) {
            $query->where('is_pinned', true);
        }

        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $articles = $query->paginate(20);

        $categories = Category::all();

        return view('admin.articles.index', compact('articles', 'categories'));
    }

    /**
     * Show the form for creating a new article
     */
    public function create(): View
    {
        $categories = Category::all();
        $writers = User::all();
        $locales = $this->locales;

        return view('admin.articles.create', compact('categories', 'writers', 'locales'));
    }

    /**
     * Store a newly created article
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'thumb' => 'nullable|image|max:5120',
            'writer_id' => 'nullable|exists:users,id',
            'read_time' => 'required|integer|min:0',
            'is_featured' => 'boolean',
            'is_pinned' => 'boolean',
            'category_id' => 'nullable|exists:categories,id',
            'hijri_date' => 'nullable|string|max:255',
            'categories' => 'nullable|array',
            'categories.*' => 'exists:categories,id',

            // Locales
            'locales' => 'required|array',
            'locales.*.locale' => 'required|in:' . implode(',', $this->locales),
            'locales.*.title' => 'required|string|max:500',
            'locales.*.description' => 'nullable|string',
            'locales.*.excerpt' => 'nullable|string',
            'locales.*.body' => 'nullable|string',
            'locales.*.summary' => 'nullable|string',
            'locales.*.is_ready' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            // Handle thumbnail upload
            $thumbPath = null;
            if ($request->hasFile('thumb')) {
                $thumbPath = $request->file('thumb')->store('articles/thumbs', 'public');
            }

            // Create article
            $article = Article::create([
                'thumb' => $thumbPath,
                'writer_id' => $validated['writer_id'] ?? null,
                'read_time' => $validated['read_time'],
                'views' => 0,
                'is_featured' => $request->boolean('is_featured'),
                'is_pinned' => $request->boolean('is_pinned'),
                'category_id' => $validated['category_id'] ?? null,
                'hijri_date' => $validated['hijri_date'] ?? null,
            ]);

            // Create locales
            foreach ($validated['locales'] as $localeData) {
                $slug = Str::slug($localeData['title']) . '-' . Str::random(6);

                $article->locales()->create([
                    'locale' => $localeData['locale'],
                    'title' => $localeData['title'],
                    'slug' => $slug,
                    'description' => $localeData['description'] ?? null,
                    'excerpt' => $localeData['excerpt'] ?? null,
                    'content' => [
                        'body' => $localeData['body'] ?? null,
                        'toc' => [],
                        'summary' => $localeData['summary'] ?? null,
                    ],
                    'is_ready' => $localeData['is_ready'] ?? false,
                ]);
            }

            // Attach categories
            if (!empty($validated['categories'])) {
                $article->categories()->attach($validated['categories']);
            }

            DB::commit();

            return redirect()
                ->route('admin.articles.index')
                ->with('success', 'Article created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();

            // Delete uploaded thumbnail if exists
            if ($thumbPath) {
                Storage::disk('public')->delete($thumbPath);
            }

            return back()
                ->withInput()
                ->with('error', 'Failed to create article: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified article
     */
    public function show(Article $article): View
    {
        $article->load(['locales', 'writer', 'category', 'categories', 'gallery']);

        return view('admin.articles.show', compact('article'));
    }

    /**
     * Show the form for editing the article
     */
    public function edit(Article $article): View
    {
        $article->load(['locales', 'categories']);

        $categories = Category::all();
        $writers = User::all();
        $locales = $this->locales;

        return view('admin.articles.edit', compact('article', 'categories', 'writers', 'locales'));
    }

    /**
     * Update the specified article
     */
    public function update(Request $request, Article $article): RedirectResponse
    {
        $validated = $request->validate([
            'thumb' => 'nullable|image|max:5120',
            'writer_id' => 'nullable|exists:users,id',
            'read_time' => 'required|integer|min:0',
            'is_featured' => 'boolean',
            'is_pinned' => 'boolean',
            'category_id' => 'nullable|exists:categories,id',
            'hijri_date' => 'nullable|string|max:255',
            'categories' => 'nullable|array',
            'categories.*' => 'exists:categories,id',

            // Locales
            'locales' => 'required|array',
            'locales.*.id' => 'nullable|exists:article_locale,id',
            'locales.*.locale' => 'required|in:' . implode(',', $this->locales),
            'locales.*.title' => 'required|string|max:500',
            'locales.*.description' => 'nullable|string',
            'locales.*.excerpt' => 'nullable|string',
            'locales.*.body' => 'nullable|string',
            'locales.*.summary' => 'nullable|string',
            'locales.*.is_ready' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
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
                'writer_id' => $validated['writer_id'] ?? null,
                'read_time' => $validated['read_time'],
                'is_featured' => $request->boolean('is_featured'),
                'is_pinned' => $request->boolean('is_pinned'),
                'category_id' => $validated['category_id'] ?? null,
                'hijri_date' => $validated['hijri_date'] ?? null,
            ]);

            // Update or create locales
            foreach ($validated['locales'] as $localeData) {
                $slug = Str::slug($localeData['title']) . '-' . Str::random(6);

                if (!empty($localeData['id'])) {
                    // Update existing locale
                    ArticleLocale::where('id', $localeData['id'])->update([
                        'title' => $localeData['title'],
                        'slug' => $slug,
                        'description' => $localeData['description'] ?? null,
                        'excerpt' => $localeData['excerpt'] ?? null,
                        'content' => [
                            'body' => $localeData['body'] ?? null,
                            'toc' => [],
                            'summary' => $localeData['summary'] ?? null,
                        ],
                        'is_ready' => $localeData['is_ready'] ?? false,
                    ]);
                } else {
                    // Create new locale
                    $article->locales()->create([
                        'locale' => $localeData['locale'],
                        'title' => $localeData['title'],
                        'slug' => $slug,
                        'description' => $localeData['description'] ?? null,
                        'excerpt' => $localeData['excerpt'] ?? null,
                        'content' => [
                            'body' => $localeData['body'] ?? null,
                            'toc' => [],
                            'summary' => $localeData['summary'] ?? null,
                        ],
                        'is_ready' => $localeData['is_ready'] ?? false,
                    ]);
                }
            }

            // Sync categories
            $article->categories()->sync($validated['categories'] ?? []);

            DB::commit();

            return redirect()
                ->route('admin.articles.index')
                ->with('success', 'Article updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'Failed to update article: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified article
     */
    public function destroy(Article $article): RedirectResponse
    {
        try {
            // Delete thumbnail if exists
            if ($article->thumb) {
                Storage::disk('public')->delete($article->thumb);
            }

            // Delete gallery images
            foreach ($article->gallery as $galleryItem) {
                if ($galleryItem->image) {
                    Storage::disk('public')->delete($galleryItem->image);
                }
            }

            // Delete article (cascade will handle related records)
            $article->delete();

            return redirect()
                ->route('admin.articles.index')
                ->with('success', 'Article deleted successfully!');

        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to delete article: ' . $e->getMessage());
        }
    }

    /**
     * API endpoint for AJAX article listing
     */
    public function api(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 12);
            $locale = $request->input('locale', 'en');

            $query = Article::with([
                'locales' => function ($q) use ($locale) {
                    $q->where('locale', $locale);
                },
                'writer:id,name,email',
                'category:id,name',
                'categories:id,name'
            ]);

            // Search by title/excerpt in article_locale table
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->whereHas('locales', function ($q) use ($search, $locale) {
                    $q->where('locale', $locale)
                        ->where(function ($subQ) use ($search) {
                            $subQ->where('title', 'LIKE', "%{$search}%")
                                ->orWhere('excerpt', 'LIKE', "%{$search}%")
                                ->orWhere('description', 'LIKE', "%{$search}%");
                        });
                });
            }

            // Filter by category
            if ($request->filled('category_id')) {
                $categoryId = $request->input('category_id');
                $query->where(function ($q) use ($categoryId) {
                    $q->where('category_id', $categoryId)
                        ->orWhereHas('categories', function ($subQ) use ($categoryId) {
                            $subQ->where('categories.id', $categoryId);
                        });
                });
            }

            // Filter by status
            if ($request->filled('status')) {
                $status = $request->input('status');
                switch ($status) {
                    case 'featured':
                        $query->where('is_featured', true);
                        break;
                    case 'pinned':
                        $query->where('is_pinned', true);
                        break;
                    case 'draft':
                        $query->whereDoesntHave('locales', function ($q) {
                            $q->where('is_ready', true);
                        });
                        break;
                    case 'all':
                    default:
                        // No filter
                        break;
                }
            }

            // Filter by locale (articles with ready translations in that locale)
            $query->whereHas('locales', function ($q) use ($locale) {
                $q->where('locale', $locale)
                    ->where('is_ready', true);
            });

            // Sort
            $sort = $request->input('sort', 'latest');
            switch ($sort) {
                case 'popular':
                    $query->orderBy('views', 'desc');
                    break;
                case 'alphabetical':
                    $query->whereHas('locales', function ($q) use ($locale) {
                        $q->where('locale', $locale);
                    })->get()->sortBy(function ($article) use ($locale) {
                        return $article->locales->where('locale', $locale)->first()->title ?? '';
                    });
                    break;
                case 'oldest':
                    $query->orderBy('created_at', 'asc');
                    break;
                case 'latest':
                default:
                    $query->orderBy('created_at', 'desc');
                    break;
            }

            // Paginate
            $articles = $query->paginate($perPage);

            // Get stats
            $stats = [
                'total' => Article::count(),
                'total_views' => Article::sum('views'),
                'featured_count' => Article::where('is_featured', true)->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $articles->items(),
                'meta' => [
                    'current_page' => $articles->currentPage(),
                    'last_page' => $articles->lastPage(),
                    'from' => $articles->firstItem(),
                    'to' => $articles->lastItem(),
                    'total' => $articles->total(),
                    'per_page' => $articles->perPage(),
                ],
                'stats' => $stats,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch articles: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk actions on multiple articles
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'article_ids' => 'required|array',
            'article_ids.*' => 'exists:articles,id',
            'action' => 'required|in:feature,unfeature,pin,unpin,archive,delete',
        ]);

        DB::beginTransaction();
        try {
            $articleIds = $validated['article_ids'];
            $action = $validated['action'];
            $affectedCount = 0;

            switch ($action) {
                case 'feature':
                    $affectedCount = Article::whereIn('id', $articleIds)
                        ->update(['is_featured' => true]);
                    $message = "{$affectedCount} articles marked as featured";
                    break;

                case 'unfeature':
                    $affectedCount = Article::whereIn('id', $articleIds)
                        ->update(['is_featured' => false]);
                    $message = "{$affectedCount} articles unmarked as featured";
                    break;

                case 'pin':
                    $affectedCount = Article::whereIn('id', $articleIds)
                        ->update(['is_pinned' => true]);
                    $message = "{$affectedCount} articles pinned";
                    break;

                case 'unpin':
                    $affectedCount = Article::whereIn('id', $articleIds)
                        ->update(['is_pinned' => false]);
                    $message = "{$affectedCount} articles unpinned";
                    break;

                case 'archive':
                    // Mark all locales as not ready (draft status)
                    $affectedCount = ArticleLocale::whereIn('article_id', $articleIds)
                        ->update(['is_ready' => false]);
                    $message = "{$affectedCount} article translations archived";
                    break;

                case 'delete':
                    $articles = Article::whereIn('id', $articleIds)->get();

                    // Delete thumbnails and gallery images
                    foreach ($articles as $article) {
                        if ($article->thumb) {
                            Storage::disk('public')->delete($article->thumb);
                        }

                        foreach ($article->gallery as $galleryItem) {
                            if ($galleryItem->image) {
                                Storage::disk('public')->delete($galleryItem->image);
                            }
                        }
                    }

                    $affectedCount = Article::whereIn('id', $articleIds)->delete();
                    $message = "{$affectedCount} articles deleted";
                    break;

                default:
                    throw new \Exception('Invalid action');
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $message,
                'affected_count' => $affectedCount,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Bulk action failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle featured status
     */
    public function toggleFeature(Article $article): JsonResponse
    {
        try {
            $article->update([
                'is_featured' => !$article->is_featured,
            ]);

            return response()->json([
                'success' => true,
                'message' => $article->is_featured ? 'Article marked as featured' : 'Article unmarked as featured',
                'is_featured' => $article->is_featured,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle featured status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle pinned status
     */
    public function togglePin(Article $article): JsonResponse
    {
        try {
            $article->update([
                'is_pinned' => !$article->is_pinned,
            ]);

            return response()->json([
                'success' => true,
                'message' => $article->is_pinned ? 'Article pinned' : 'Article unpinned',
                'is_pinned' => $article->is_pinned,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle pinned status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get article statistics
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = [
                'total_articles' => Article::count(),
                'total_views' => Article::sum('views'),
                'featured_count' => Article::where('is_featured', true)->count(),
                'pinned_count' => Article::where('is_pinned', true)->count(),
                'recent_count' => Article::where('created_at', '>=', now()->subDays(7))->count(),
                'draft_count' => Article::whereDoesntHave('locales', function ($q) {
                    $q->where('is_ready', true);
                })->count(),
                'by_category' => Category::withCount('articles')->get()->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'count' => $category->articles_count,
                    ];
                }),
                'by_locale' => collect($this->locales)->map(function ($locale) {
                    return [
                        'locale' => $locale,
                        'count' => ArticleLocale::where('locale', $locale)
                            ->where('is_ready', true)
                            ->count(),
                    ];
                }),
                'recent_articles' => Article::with(['locales', 'writer:id,name', 'category:id,name'])
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get(),
                'top_viewed' => Article::with(['locales', 'writer:id,name', 'category:id,name'])
                    ->orderBy('views', 'desc')
                    ->limit(5)
                    ->get(),
            ];

            return response()->json([
                'success' => true,
                'stats' => $stats,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics: ' . $e->getMessage(),
            ], 500);
        }
    }
}
