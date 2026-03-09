<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Article;
use App\Models\ArticleLocale;
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
                if (!empty($categoryIds)) {
                    $query->whereHas('categories', function ($q) use ($categoryIds) {
                        $q->whereIn('categories.id', $categoryIds);
                    });
                }
                if (!empty($tagIds)) {
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
     * Increment article views
     */
    public function incrementViews(int $articleId): void
    {
        Article::where('id', $articleId)->increment('views');

        // Clear statistics cache
        Cache::forget('article_statistics');
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

        if (!$articleLocale) {
            return null;
        }

        return Article::with([
            'locales:id,article_id,locale,title,slug,description,excerpt,content,seo_metadata,is_ready',
            'writer:id,name',
            'category:id,slug,type,status',
            'category.translations:id,category_id,locale,name',
            'categories:id,slug,type,status',
            'categories.translations:id,category_id,locale,name',
            'tags:id,slug',
            'tags.translations:id,tag_id,locale,name',
            'media',
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
                    $query->whereHas('locales', function ($q) {
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
