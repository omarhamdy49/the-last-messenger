<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Blog\Models\Category;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleLocale;
use App\Models\User;
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
}
