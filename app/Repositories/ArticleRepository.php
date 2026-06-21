<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Domain\Blog\Models\Category;
use App\Domain\Blog\Models\Tag;
use App\Models\Article;
use App\Models\ArticleLocale;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ArticleRepository
{
    /**
     * Get all articles with filters
     */
    public function getAllWithFilters(
        array $filters = [],
        ?string $search = null,
        int $perPage = 15,
        string $sort = 'created_at',
        string $order = 'desc'
    ): LengthAwarePaginator {
        $query = Article::query()
            ->with(['locales:id,article_id,locale,title,slug,is_ready', 'writer:id,name,email', 'category:id,slug,type,status', 'category.translations:id,category_id,locale,name', 'categories:id,slug,type,status', 'categories.translations:id,category_id,locale,name']);

        // Apply filters
        $this->applyFilters($query, $filters);

        // Apply search
        if ($search) {
            $this->applySearch($query, $search, $filters['locale'] ?? 'ar');
        }

        // Apply sorting
        $query->orderBy($sort, $order);

        return $query->paginate($perPage);
    }

    /**
     * Search articles by title, excerpt, or content
     */
    public function searchArticles(string $query, string $locale = 'ar', int $perPage = 15): LengthAwarePaginator
    {
        return Article::query()
            ->where('status', 'published')
            ->with(['locales:id,article_id,locale,title,slug,is_ready', 'writer:id,name', 'category:id,slug,type,status', 'category.translations:id,category_id,locale,name'])
            ->whereHas('locales', function ($q) use ($query, $locale) {
                $q->where('locale', $locale)
                    ->where(function ($subQ) use ($query) {
                        $subQ->where('title', 'LIKE', "%{$query}%")
                            ->orWhere('excerpt', 'LIKE', "%{$query}%")
                            ->orWhere('description', 'LIKE', "%{$query}%")
                            ->orWhereRaw("JSON_EXTRACT(content, '$.body') LIKE ?", ["%{$query}%"]);
                    });
            })
            ->paginate($perPage);
    }

    /**
     * Get articles by status
     */
    public function getArticlesByStatus(string $status, int $perPage = 15): LengthAwarePaginator
    {
        $query = Article::query()->with(['locales:id,article_id,locale,title,slug,is_ready', 'writer:id,name', 'category:id,slug,type,status', 'category.translations:id,category_id,locale,name']);

        switch ($status) {
            case 'draft':
                $query->whereDoesntHave('locales', function ($q) {
                    $q->where('is_ready', true);
                });
                break;

            case 'published':
                $query->whereHas('locales', function ($q) {
                    $q->where('is_ready', true);
                });
                break;

            case 'featured':
                $query->where('is_featured', true);
                break;

            case 'pinned':
                $query->where('is_pinned', true);
                break;
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get articles by category
     */
    public function getArticlesByCategory(int $categoryId, int $perPage = 15): LengthAwarePaginator
    {
        return Article::query()
            ->with(['locales:id,article_id,locale,title,slug,is_ready', 'writer:id,name', 'category:id,slug,type,status', 'category.translations:id,category_id,locale,name'])
            ->where(function ($query) use ($categoryId) {
                $query->where('category_id', $categoryId)
                    ->orWhereHas('categories', function ($q) use ($categoryId) {
                        $q->where('categories.id', $categoryId);
                    });
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get articles by author
     */
    public function getArticlesByAuthor(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return Article::query()
            ->with(['locales:id,article_id,locale,title,slug,is_ready', 'category:id,slug,type,status', 'category.translations:id,category_id,locale,name', 'categories:id,slug,type,status', 'categories.translations:id,category_id,locale,name'])
            ->where('writer_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get featured articles
     */
    public function getFeaturedArticles(int $limit = 10): Collection
    {
        return Cache::remember('featured_articles', 3600, function () use ($limit) {
            return Article::query()
                ->with(['locales:id,article_id,locale,title,slug,is_ready', 'writer:id,name', 'category:id,slug,type,status', 'category.translations:id,category_id,locale,name'])
                ->where('is_featured', true)
                ->whereHas('locales', function ($q) {
                    $q->where('is_ready', true);
                })
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get related articles based on category and tags
     */
    public function getRelatedArticles(int $articleId, int $limit = 5): Collection
    {
        $article = Article::with(['categories', 'tags'])->findOrFail($articleId);

        $categoryIds = $article->categories->pluck('id')->toArray();
        $tagIds = $article->tags->pluck('id')->toArray();

        return Article::query()
            ->with(['locales:id,article_id,locale,title,slug,is_ready', 'writer:id,name', 'category:id,slug,type,status', 'category.translations:id,category_id,locale,name'])
            ->where('id', '!=', $articleId)
            ->where(function ($query) use ($categoryIds, $tagIds) {
                if (! empty($categoryIds)) {
                    $query->whereHas('categories', function ($q) use ($categoryIds) {
                        $q->whereIn('categories.id', $categoryIds);
                    });
                }
                if (! empty($tagIds)) {
                    $query->orWhereHas('tags', function ($q) use ($tagIds) {
                        $q->whereIn('tags.id', $tagIds);
                    });
                }
            })
            ->whereHas('locales', function ($q) {
                $q->where('is_ready', true);
            })
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get published articles for the public index, with only the columns the
     * card payload needs (no content/SEO/statistics JSON blobs).
     *
     * Locale rows are constrained to the active locale + the Arabic fallback,
     * and tags are eager-loaded so the cards can power client-side tag filters.
     */
    public function getPublishedForPublicIndex(string $locale, int $limit = 500): Collection
    {
        return Article::query()
            ->select(['id', 'slug', 'thumb', 'writer_id', 'category_id', 'read_time', 'views', 'is_featured', 'hijri_date', 'created_at'])
            ->with([
                'locales' => fn ($q) => $q
                    ->select(['id', 'article_id', 'locale', 'title', 'slug', 'excerpt', 'description', 'is_ready'])
                    ->whereIn('locale', array_unique([$locale, 'ar'])),
                'writer:id,name',
                'category:id,slug,type,status',
                'category.translations:id,category_id,locale,name',
                'tags:tags.id,tags.slug',
                'tags.translations:id,tag_id,locale,name',
            ])
            ->where('status', 'published')
            ->whereHas('locales', function ($q) use ($locale) {
                $q->where('locale', $locale)->where('is_ready', true);
            })
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Published articles for the XML sitemap: id + updated_at (lastmod) and the
     * slug of every READY locale (for hreflang alternates in the sitemap).
     *
     * @return Collection<int, Article>
     */
    public function getPublishedForSitemap(): Collection
    {
        return Article::query()
            ->select(['id', 'updated_at'])
            ->where('status', 'published')
            ->whereHas('locales', fn ($q) => $q->where('is_ready', true))
            ->with(['locales' => fn ($q) => $q
                ->where('is_ready', true)
                ->select(['id', 'article_id', 'locale', 'slug']),
            ])
            ->orderByDesc('updated_at')
            ->get();
    }

    /**
     * Get related published articles for the public single-article page.
     *
     * Same-category matches (primary or pivot) rank first, then shared-tag
     * matches; the source article is excluded and results are capped.
     */
    public function getRelatedForPublic(int $articleId, int $limit = 6): Collection
    {
        $article = Article::query()
            ->select(['id', 'category_id'])
            ->with(['categories:categories.id', 'tags:tags.id'])
            ->find($articleId);

        if ($article === null) {
            return new Collection;
        }

        $categoryIds = $article->categories->pluck('id')
            ->when($article->category_id !== null, fn ($ids) => $ids->push($article->category_id))
            ->unique()
            ->values()
            ->all();
        $tagIds = $article->tags->pluck('id')->all();

        if ($categoryIds === [] && $tagIds === []) {
            return new Collection;
        }

        $query = Article::query()
            ->select(['id', 'slug', 'thumb', 'writer_id', 'category_id', 'read_time', 'views', 'is_featured', 'hijri_date', 'created_at'])
            ->with([
                'locales:id,article_id,locale,title,slug,excerpt,description,is_ready',
                'writer:id,name',
                'category:id,slug,type,status',
                'category.translations:id,category_id,locale,name',
            ])
            ->whereKeyNot($articleId)
            ->where('status', 'published')
            ->where(function (Builder $q) use ($categoryIds, $tagIds) {
                if ($categoryIds !== []) {
                    $q->whereIn('category_id', $categoryIds)
                        ->orWhereHas('categories', function ($c) use ($categoryIds) {
                            $c->whereIn('categories.id', $categoryIds);
                        });
                }
                if ($tagIds !== []) {
                    $q->orWhereHas('tags', function ($t) use ($tagIds) {
                        $t->whereIn('tags.id', $tagIds);
                    });
                }
            })
            ->whereHas('locales', function ($q) {
                $q->where('is_ready', true);
            });

        if ($categoryIds !== [] && $tagIds !== []) {
            // Same-category matches first, then shared-tag-only matches.
            $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
            $query->orderByRaw(
                "CASE WHEN articles.category_id IN ({$placeholders}) OR EXISTS (
                    SELECT 1 FROM article_category
                    WHERE article_category.article_id = articles.id
                      AND article_category.category_id IN ({$placeholders})
                ) THEN 0 ELSE 1 END",
                array_merge($categoryIds, $categoryIds),
            );
        }

        return $query->orderByDesc('created_at')->limit($limit)->get();
    }

    /**
     * Resolve the previous (older) and next (newer) ready+published articles
     * relative to the given one, ordered by created_at. Used for the in-article
     * prev/next reader navigation.
     *
     * @return array{prev: ?Article, next: ?Article}
     */
    public function getAdjacentForPublic(int $articleId, ?\Carbon\CarbonInterface $createdAt): array
    {
        if ($createdAt === null) {
            return ['prev' => null, 'next' => null];
        }

        $base = fn () => Article::query()
            ->select(['id', 'slug', 'thumb', 'writer_id', 'category_id', 'read_time', 'views', 'is_featured', 'hijri_date', 'created_at'])
            ->with([
                'locales:id,article_id,locale,title,slug,excerpt,description,is_ready',
                'writer:id,name',
                'category:id,slug,type,status',
                'category.translations:id,category_id,locale,name',
            ])
            ->whereKeyNot($articleId)
            ->where('status', 'published')
            ->whereHas('locales', fn ($q) => $q->where('is_ready', true));

        $prev = $base()
            ->where('created_at', '<', $createdAt)
            ->orderByDesc('created_at')
            ->first();

        $next = $base()
            ->where('created_at', '>', $createdAt)
            ->orderBy('created_at')
            ->first();

        return ['prev' => $prev, 'next' => $next];
    }

    /**
     * Get categories that have at least one published article, with the real
     * published-article count (primary category — matches the public index
     * filter, which filters cards by their primary categoryId).
     *
     * @return Collection<int, Category>
     */
    public function getPublishedCategoryFacets(): Collection
    {
        $published = function ($q): void {
            $q->where('articles.status', 'published')
                ->whereHas('locales', fn ($l) => $l->where('is_ready', true));
        };

        return Category::query()
            ->select(['id', 'slug'])
            ->with(['translations:id,category_id,locale,name'])
            ->withCount(['articles as published_articles_count' => $published])
            ->whereHas('articles', $published)
            ->orderByDesc('published_articles_count')
            ->get();
    }

    /**
     * Get tags attached to at least one published article, with the real
     * published-article count, for the public index tag cloud.
     *
     * @return Collection<int, Tag>
     */
    public function getPublishedTagFacets(int $limit = 20): Collection
    {
        $published = function ($q): void {
            $q->where('articles.status', 'published')
                ->whereHas('locales', fn ($l) => $l->where('is_ready', true));
        };

        return Tag::query()
            ->select(['id', 'slug'])
            ->with(['translations:id,tag_id,locale,name'])
            ->withCount(['articles as published_articles_count' => $published])
            ->whereHas('articles', $published)
            ->orderByDesc('published_articles_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Increment article views
     */
    public function incrementViews(int $articleId): int
    {
        Article::where('id', $articleId)->increment('views');

        // Clear statistics cache
        Cache::forget('article_statistics');

        return (int) (Article::where('id', $articleId)->value('views') ?? 0);
    }

    /**
     * Publish every scheduled article whose scheduled_at has passed: flip it to
     * published, stamp published_at, and clear scheduled_at. Busts the public
     * caches so the article appears on the next request. Returns the count.
     */
    public function publishDueScheduled(): int
    {
        $due = Article::query()
            ->where('status', 'scheduled')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->with('locales:id,article_id,locale,slug')
            ->get(['id']);

        if ($due->isEmpty()) {
            return 0;
        }

        Article::whereKey($due->pluck('id'))->update([
            'status' => 'published',
            'published_at' => now(),
            'scheduled_at' => null,
        ]);

        $this->forgetPublicCaches($due);

        return $due->count();
    }

    /**
     * Forget the cached public index + show payloads affected by a status change
     * so newly published articles surface immediately (the show cache may hold a
     * null/404 entry from when the article was still scheduled).
     *
     * @param  \Illuminate\Support\Collection<int, Article>  $articles
     */
    private function forgetPublicCaches($articles): void
    {
        $locales = array_keys((array) config('app.supported_locales', ['ar' => []]));

        foreach ($locales as $locale) {
            Cache::forget("public_articles_index_{$locale}");
        }

        foreach ($articles as $article) {
            foreach ($article->locales as $localeRow) {
                foreach ($locales as $locale) {
                    Cache::forget("public_article_show_{$localeRow->slug}_{$locale}");
                }
            }
        }
    }

    /**
     * Get article statistics
     */
    public function getStatistics(): array
    {
        return Cache::remember('article_statistics', 1800, function () {
            $totalArticles = Article::count();
            $publishedArticles = Article::whereHas('locales', function ($q) {
                $q->where('is_ready', true);
            })->count();

            return [
                'total_articles' => $totalArticles,
                'published_articles' => $publishedArticles,
                'draft_articles' => $totalArticles - $publishedArticles,
                'featured_articles' => Article::where('is_featured', true)->count(),
                'pinned_articles' => Article::where('is_pinned', true)->count(),
                'total_views' => Article::sum('views'),
                'average_read_time' => (int) Article::avg('read_time'),
                'recent_articles' => Article::where('created_at', '>=', now()->subDays(7))->count(),
                'by_category' => $this->getArticleCountByCategory(),
                'by_locale' => $this->getArticleCountByLocale(),
                'top_viewed' => $this->getTopViewedArticles(5),
                'recent' => $this->getRecentArticles(5),
            ];
        });
    }

    /**
     * Get article count by category
     */
    public function getArticleCountByCategory(): array
    {
        return DB::table('article_category')
            ->join('categories', 'categories.id', '=', 'article_category.category_id')
            ->leftJoin('category_translations', 'category_translations.category_id', '=', 'categories.id')
            ->where('category_translations.locale', app()->getLocale())
            ->select('categories.id', 'category_translations.name', DB::raw('COUNT(*) as count'))
            ->groupBy('categories.id', 'category_translations.name')
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get article count by locale
     */
    public function getArticleCountByLocale(): array
    {
        return DB::table('article_locale')
            ->select('locale', DB::raw('COUNT(*) as count'))
            ->where('is_ready', true)
            ->groupBy('locale')
            ->get()
            ->map(function ($item) {
                return [
                    'locale' => $item->locale,
                    'count' => $item->count,
                    'name' => $this->getLocaleName($item->locale),
                ];
            })
            ->toArray();
    }

    /**
     * Get top viewed articles
     */
    public function getTopViewedArticles(int $limit = 10): Collection
    {
        return Article::query()
            ->with(['locales' => function ($q) {
                $q->where('is_ready', true);
            }, 'writer:id,name', 'category:id,slug,type,status', 'category.translations:id,category_id,locale,name'])
            ->whereHas('locales', function ($q) {
                $q->where('is_ready', true);
            })
            ->orderBy('views', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get recent articles
     */
    public function getRecentArticles(int $limit = 10): Collection
    {
        return Article::query()
            ->with(['locales' => function ($q) {
                $q->where('is_ready', true);
            }, 'writer:id,name', 'category:id,slug,type,status', 'category.translations:id,category_id,locale,name'])
            ->where('status', 'published')
            ->whereHas('locales', function ($q) {
                $q->where('is_ready', true);
            })
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get articles by tag
     */
    public function getArticlesByTag(int $tagId, int $perPage = 15): LengthAwarePaginator
    {
        return Article::query()
            ->with(['locales:id,article_id,locale,title,slug,is_ready', 'writer:id,name', 'category:id,slug,type,status', 'category.translations:id,category_id,locale,name'])
            ->where('status', 'published')
            ->whereHas('tags', function ($q) use ($tagId) {
                $q->where('tags.id', $tagId);
            })
            ->whereHas('locales', function ($q) {
                $q->where('is_ready', true);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get articles by date range
     */
    public function getArticlesByDateRange(string $dateFrom, string $dateTo, int $perPage = 15): LengthAwarePaginator
    {
        return Article::query()
            ->with(['locales:id,article_id,locale,title,slug,is_ready', 'writer:id,name', 'category:id,slug,type,status', 'category.translations:id,category_id,locale,name'])
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Find article by slug and locale
     */
    public function findBySlug(string $slug, string $locale = 'ar'): ?Article
    {
        $articleLocale = ArticleLocale::where('slug', $slug)
            ->where('locale', $locale)
            ->where('is_ready', true)
            ->first();

        if (! $articleLocale) {
            return null;
        }

        return Article::query()
            ->where('status', 'published')
            ->with([
                'locales:id,article_id,locale,title,slug,description,excerpt,content,seo_metadata,is_ready',
                'writer:id,name',
                'category:id,slug,type,status',
                'category.translations:id,category_id,locale,name',
                'categories:id,slug,type,status',
                'categories.translations:id,category_id,locale,name',
                'tags:id,slug',
                'tags.translations:id,tag_id,locale,name',
                'mediaItems',
                'gallery:id,article_id,image,caption,order',
            ])->find($articleLocale->article_id);
    }

    /**
     * Get popular articles based on views
     */
    public function getPopularArticles(int $days = 30, int $limit = 10): Collection
    {
        return Article::query()
            ->with(['locales:id,article_id,locale,title,slug,is_ready', 'writer:id,name', 'category:id,slug,type,status', 'category.translations:id,category_id,locale,name'])
            ->where('created_at', '>=', now()->subDays($days))
            ->whereHas('locales', function ($q) {
                $q->where('is_ready', true);
            })
            ->orderBy('views', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get trending articles (high views in recent time)
     */
    public function getTrendingArticles(int $limit = 10): Collection
    {
        return Cache::remember('trending_articles', 1800, function () use ($limit) {
            return Article::query()
                ->with(['locales:id,article_id,locale,title,slug,is_ready', 'writer:id,name', 'category:id,slug,type,status', 'category.translations:id,category_id,locale,name'])
                ->where('created_at', '>=', now()->subDays(7))
                ->whereHas('locales', function ($q) {
                    $q->where('is_ready', true);
                })
                ->orderByRaw('views / DATEDIFF(NOW(), created_at) DESC')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get aggregate stats for the admin index dashboard strip.
     *
     * Returns a plain object with keys: total, published, drafts, scheduled, featured.
     * All counts exclude soft-deleted rows.
     */
    public function getIndexStats(): object
    {
        return DB::table('articles')
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published,
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as drafts,
                SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled,
                SUM(CASE WHEN is_featured = 1 THEN 1 ELSE 0 END) as featured
            ")
            ->whereNull('deleted_at')
            ->first();
    }

    /**
     * Get all categories with their translations for article form dropdowns.
     *
     * @return Collection<int, Category>
     */
    public function getFormCategories(): Collection
    {
        return Category::with(['translations:id,category_id,locale,name'])
            ->select(['id', 'slug', 'type', 'status'])
            ->orderBy('id')
            ->get();
    }

    /**
     * Get all users with the 'writer' role for article author dropdowns.
     *
     * @return Collection<int, User>
     */
    public function getFormWriters(): Collection
    {
        return User::role('writer')
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();
    }

    /**
     * Get all article-type tags with their translations for article form dropdowns.
     *
     * @return Collection<int, Tag>
     */
    public function getFormTags(): Collection
    {
        return Tag::with(['translations:id,tag_id,locale,name'])
            ->where('type', 'article')
            ->select(['id', 'slug', 'type'])
            ->orderBy('id')
            ->get();
    }

    /**
     * Soft-delete an article by ID.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function deleteById(int $id): void
    {
        Article::findOrFail($id)->delete();
    }

    /**
     * Mark all locales of an article as published (is_ready = true).
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function publishById(int $id): void
    {
        Article::findOrFail($id)->locales()->update(['is_ready' => true]);
    }

    /**
     * Restore a soft-deleted article.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function restoreById(int $id): Article
    {
        $article = Article::withTrashed()->findOrFail($id);
        $article->restore();

        return $article;
    }

    /**
     * Force-delete an article and return its file paths for cleanup.
     * Returns ['thumb' => string|null, 'gallery' => array<string>].
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function forceDeleteById(int $id): array
    {
        $article = Article::withTrashed()->with('gallery')->findOrFail($id);

        $thumb = $article->thumb;
        $gallery = $article->gallery->pluck('image')->filter()->values()->all();

        $article->forceDelete();

        return compact('thumb', 'gallery');
    }

    /**
     * Get paginated trashed articles for the admin trash view.
     */
    public function getTrashed(int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Article::onlyTrashed()
            ->with([
                'locales:id,article_id,locale,title,slug,is_ready',
                'writer:id,name',
                'category:id,name,slug',
            ])
            ->orderBy('deleted_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Search articles for AJAX selectors (series article picker, SeerahEvent form).
     *
     * @param  array<int>  $excludeIds
     */
    public function searchForSelector(string $search, array $excludeIds = [], int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        return Article::query()
            ->select(['id', 'status', 'created_at'])
            ->with(['locales:id,article_id,locale,title'])
            ->when($search, function ($q) use ($search) {
                $q->whereHas('locales', function ($lq) use ($search) {
                    $lq->where('title', 'like', "%{$search}%");
                });
            })
            ->when(! empty($excludeIds), function ($q) use ($excludeIds) {
                $q->whereNotIn('id', $excludeIds);
            })
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get articles for form dropdowns (SeerahEvent create/edit).
     */
    public function getForDropdown(): \Illuminate\Database\Eloquent\Collection
    {
        return Article::query()
            ->select(['id', 'status', 'created_at'])
            ->with(['locales:id,article_id,locale,title,slug,is_ready'])
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Apply filters to query
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        if (isset($filters['status'])) {
            switch ($filters['status']) {
                case 'draft':
                    $query->whereDoesntHave('locales', function ($q) {
                        $q->where('is_ready', true);
                    });
                    break;
                case 'published':
                    $query->where('status', 'published')
                        ->whereHas('locales', function ($q) {
                            $q->where('is_ready', true);
                        });
                    break;
            }
        }

        if (isset($filters['category_id'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('category_id', $filters['category_id'])
                    ->orWhereHas('categories', function ($subQ) use ($filters) {
                        $subQ->where('categories.id', $filters['category_id']);
                    });
            });
        }

        if (isset($filters['author_id'])) {
            $query->where('writer_id', $filters['author_id']);
        }

        if (isset($filters['is_featured'])) {
            $query->where('is_featured', (bool) $filters['is_featured']);
        }

        if (isset($filters['is_pinned'])) {
            $query->where('is_pinned', (bool) $filters['is_pinned']);
        }

        if (isset($filters['locale'])) {
            $query->whereHas('locales', function ($q) use ($filters) {
                $q->where('locale', $filters['locale'])
                    ->where('is_ready', true);
            });
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }
    }

    /**
     * Apply search to query
     */
    protected function applySearch(Builder $query, string $search, string $locale = 'ar'): void
    {
        $query->whereHas('locales', function ($q) use ($search, $locale) {
            $q->where('locale', $locale)
                ->where(function ($subQ) use ($search) {
                    $subQ->where('title', 'LIKE', "%{$search}%")
                        ->orWhere('excerpt', 'LIKE', "%{$search}%")
                        ->orWhere('description', 'LIKE', "%{$search}%");
                });
        });
    }

    /**
     * Get locale name
     */
    protected function getLocaleName(string $locale): string
    {
        return match ($locale) {
            'ar' => 'Arabic',
            'en' => 'English',
            'fr' => 'French',
            'tr' => 'Turkish',
            'ur' => 'Urdu',
            default => $locale,
        };
    }
}
