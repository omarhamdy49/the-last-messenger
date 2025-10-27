<?php

declare(strict_types=1);

namespace App\Domain\Blog\Services;

use App\Domain\Blog\Models\Article;
use App\Domain\Blog\Repositories\ArticleRepositoryInterface;
use App\Domain\Shared\Services\BaseService;
use App\Exceptions\Domain\ResourceNotFoundException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Article Service
 * Handles business logic for articles
 */
class ArticleService extends BaseService
{
    /**
     * Constructor
     *
     * @param ArticleRepositoryInterface $repository
     */
    public function __construct(
        protected ArticleRepositoryInterface $repository
    ) {
    }

    /**
     * Get published articles with pagination
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPublishedArticles(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getPublished($perPage, ['author', 'category', 'tags']);
    }

    /**
     * Get articles by category
     *
     * @param int $categoryId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getArticlesByCategory(int $categoryId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getByCategory($categoryId, $perPage, ['author', 'category', 'tags']);
    }

    /**
     * Get articles by series
     *
     * @param int $seriesId
     * @return Collection
     */
    public function getArticlesBySeries(int $seriesId): Collection
    {
        return $this->repository->getBySeries($seriesId, ['author', 'category']);
    }

    /**
     * Get featured articles
     *
     * @param int $limit
     * @return Collection
     */
    public function getFeaturedArticles(int $limit = 5): Collection
    {
        return $this->repository->getFeatured($limit, ['author', 'category']);
    }

    /**
     * Get popular articles
     *
     * @param int $limit
     * @return Collection
     */
    public function getPopularArticles(int $limit = 10): Collection
    {
        return $this->repository->getPopular($limit, ['author', 'category']);
    }

    /**
     * Get recent articles
     *
     * @param int $limit
     * @return Collection
     */
    public function getRecentArticles(int $limit = 10): Collection
    {
        return $this->repository->getRecent($limit, ['author', 'category']);
    }

    /**
     * Search articles
     *
     * @param string $query
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function searchArticles(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->search($query, $perPage, ['author', 'category', 'tags']);
    }

    /**
     * Find article by slug
     *
     * @param string $slug
     * @return Article
     * @throws ResourceNotFoundException
     */
    public function findBySlug(string $slug): Article
    {
        $article = $this->repository->findBySlug($slug, ['author', 'category', 'series', 'tags']);

        if (!$article) {
            throw new ResourceNotFoundException('Article', $slug);
        }

        return $article;
    }

    /**
     * Get article by slug and increment views
     *
     * @param string $slug
     * @return Article
     * @throws ResourceNotFoundException
     */
    public function getArticleBySlug(string $slug): Article
    {
        $article = $this->findBySlug($slug);
        
        // Increment views asynchronously (can be moved to queue later)
        $this->repository->incrementViews($article->id);

        return $article;
    }

    /**
     * Create new article
     *
     * @param array<string, mixed> $data
     * @return Article
     */
    public function createArticle(array $data): Article
    {
        // Calculate reading time if content provided
        if (isset($data['content'])) {
            $data['reading_time_minutes'] = $this->calculateReadingTime($data['content']);
        }

        return $this->repository->create($data);
    }

    /**
     * Update article
     *
     * @param int $id
     * @param array<string, mixed> $data
     * @return bool
     */
    public function updateArticle(int $id, array $data): bool
    {
        // Recalculate reading time if content changed
        if (isset($data['content'])) {
            $data['reading_time_minutes'] = $this->calculateReadingTime($data['content']);
        }

        return $this->repository->update($id, $data);
    }

    /**
     * Delete article
     *
     * @param int $id
     * @return bool
     */
    public function deleteArticle(int $id): bool
    {
        return $this->repository->delete($id);
    }

    /**
     * Publish article
     *
     * @param int $id
     * @return bool
     */
    public function publishArticle(int $id): bool
    {
        $article = $this->repository->findOrFail($id);
        
        return $article->publish();
    }

    /**
     * Unpublish article (set to draft)
     *
     * @param int $id
     * @return bool
     */
    public function unpublishArticle(int $id): bool
    {
        $article = $this->repository->findOrFail($id);
        
        return $article->unpublish();
    }

    /**
     * Archive article
     *
     * @param int $id
     * @return bool
     */
    public function archiveArticle(int $id): bool
    {
        $article = $this->repository->findOrFail($id);
        
        return $article->archive();
    }

    /**
     * Toggle favorite for user
     *
     * @param int $articleId
     * @param int $userId
     * @return bool
     */
    public function toggleFavorite(int $articleId, int $userId): bool
    {
        $article = $this->repository->findOrFail($articleId);
        
        $favorite = $article->favorites()
            ->where('user_id', $userId)
            ->first();

        if ($favorite) {
            $favorite->delete();
            $this->repository->decrementFavorites($articleId);
            return false;
        }

        $article->favorites()->create(['user_id' => $userId]);
        $this->repository->incrementFavorites($articleId);
        return true;
    }

    /**
     * Calculate reading time based on content
     * Assumes average reading speed of 200 words per minute
     *
     * @param array<string, string> $content
     * @return int
     */
    protected function calculateReadingTime(array $content): int
    {
        $wordCount = 0;
        
        foreach ($content as $text) {
            $wordCount += str_word_count(strip_tags($text));
        }

        return max(1, (int) ceil($wordCount / 200));
    }

    /**
     * Sync tags for article
     *
     * @param int $articleId
     * @param array<int> $tagIds
     * @return void
     */
    public function syncTags(int $articleId, array $tagIds): void
    {
        $article = $this->repository->findOrFail($articleId);
        $article->tags()->sync($tagIds);
        $this->repository->clearCache();
    }
}

