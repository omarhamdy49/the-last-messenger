<?php

declare(strict_types=1);

namespace App\Domain\Blog\Services;

use App\Domain\Blog\Models\Article;
use App\Domain\Blog\Repositories\ArticleRepositoryInterface;
use App\Domain\Shared\Services\BaseService;
use App\Exceptions\Domain\ResourceNotFoundException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Article Service
 * Handles business logic for articles
 */
class ArticleService extends BaseService
{
    /**
     * Constructor
     */
    public function __construct(
        ArticleRepositoryInterface $repository
    ) {
        $this->repository = $repository;
    }

    /**
     * Get published articles with pagination
     */
    public function getPublishedArticles(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getPublished($perPage, ['author', 'category', 'tags']);
    }

    /**
     * Get articles by category
     */
    public function getArticlesByCategory(int $categoryId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getByCategory($categoryId, $perPage, ['author', 'category', 'tags']);
    }

    /**
     * Get articles by series
     */
    public function getArticlesBySeries(int $seriesId): Collection
    {
        return $this->repository->getBySeries($seriesId, ['author', 'category']);
    }

    /**
     * Get featured articles
     */
    public function getFeaturedArticles(int $limit = 5): Collection
    {
        return $this->repository->getFeatured($limit, ['author', 'category']);
    }

    /**
     * Get popular articles
     */
    public function getPopularArticles(int $limit = 10): Collection
    {
        return $this->repository->getPopular($limit, ['author', 'category']);
    }

    /**
     * Get recent articles
     */
    public function getRecentArticles(int $limit = 10): Collection
    {
        return $this->repository->getRecent($limit, ['author', 'category']);
    }

    /**
     * Search articles
     */
    public function searchArticles(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->search($query, $perPage, ['author', 'category', 'tags']);
    }

    /**
     * Find article by slug
     *
     * @throws ResourceNotFoundException
     */
    public function findBySlug(string $slug): Article
    {
        $article = $this->repository->findBySlug($slug, ['author', 'category', 'series', 'tags']);

        if (! $article) {
            throw new ResourceNotFoundException('Article', $slug);
        }

        return $article;
    }

    /**
     * Get article by slug and increment views
     *
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
     * @param  array<string, mixed>  $data
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
     * @param  array<string, mixed>  $data
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
     */
    public function deleteArticle(int $id): bool
    {
        return $this->repository->delete($id);
    }

    /**
     * Publish article
     */
    public function publishArticle(int $id): bool
    {
        $article = $this->repository->findOrFail($id);

        return $article->publish();
    }

    /**
     * Unpublish article (set to draft)
     */
    public function unpublishArticle(int $id): bool
    {
        $article = $this->repository->findOrFail($id);

        return $article->unpublish();
    }

    /**
     * Archive article
     */
    public function archiveArticle(int $id): bool
    {
        $article = $this->repository->findOrFail($id);

        return $article->archive();
    }

    /**
     * Toggle favorite for user
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
     * @param  array<string, string>  $content
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
     * @param  array<int>  $tagIds
     */
    public function syncTags(int $articleId, array $tagIds): void
    {
        $article = $this->repository->findOrFail($articleId);
        $article->tags()->sync($tagIds);
        $this->repository->clearCache();
    }

    /**
     * Count articles by status
     */
    public function countByStatus(string $status): int
    {
        return $this->repository->countByStatus($status);
    }

    /**
     * Filter articles by category
     */
    public function filterByCategory(int $categoryId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getByCategory($categoryId, $perPage, ['author', 'category', 'tags']);
    }

    /**
     * Filter articles by author
     */
    public function filterByAuthor(int $authorId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getByAuthor($authorId, $perPage, ['author', 'category', 'tags']);
    }

    /**
     * Filter articles by date range
     */
    public function filterByDateRange(string $startDate, string $endDate, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getByDateRange($startDate, $endDate, $perPage, ['author', 'category', 'tags']);
    }

    /**
     * Filter articles by series
     */
    public function filterBySeries(int $seriesId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getBySeries($seriesId);
    }

    /**
     * Filter articles by status
     */
    public function filterByStatus(string $status, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage, ['*'], ['author', 'category', 'tags']);
    }

    /**
     * Filter articles by tag
     */
    public function filterByTag(int $tagId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getByTag($tagId, $perPage, ['author', 'category', 'tags']);
    }

    /**
     * Filter articles with multiple criteria
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->filterArticles($filters, $perPage, ['author', 'category', 'tags', 'series']);
    }

    /**
     * Handle featured image upload or media selection
     *
     * @param Article $article
     * @param UploadedFile|null $file Uploaded file
     * @param int|null $mediaId Existing media ID
     * @return void
     */
    public function handleFeaturedImage(Article $article, ?UploadedFile $file = null, ?int $mediaId = null): void
    {
        if ($file) {
            // Upload new file
            $article->addMedia($file)
                ->toMediaCollection('featured_image', 'public');
        } elseif ($mediaId) {
            // Copy existing media from library
            $media = Media::find($mediaId);
            if ($media) {
                $media->copy($article, 'featured_image');
            }
        }
    }

    /**
     * Handle OG image upload or media selection
     *
     * @param Article $article
     * @param UploadedFile|null $file Uploaded file
     * @param int|null $mediaId Existing media ID
     * @return void
     */
    public function handleOgImage(Article $article, ?UploadedFile $file = null, ?int $mediaId = null): void
    {
        if ($file) {
            // Upload new file
            $article->addMedia($file)
                ->toMediaCollection('og_image', 'public');
        } elseif ($mediaId) {
            // Copy existing media from library
            $media = Media::find($mediaId);
            if ($media) {
                $media->copy($article, 'og_image');
            }
        }
    }

    /**
     * Handle gallery images upload or media selection
     *
     * @param Article $article
     * @param array|null $files Array of uploaded files
     * @param array|null $mediaIds Array of existing media IDs
     * @return void
     */
    public function handleGalleryImages(Article $article, ?array $files = null, ?array $mediaIds = null): void
    {
        if ($files) {
            // Upload new files
            foreach ($files as $file) {
                $article->addMedia($file)
                    ->toMediaCollection('gallery', 'public');
            }
        }

        if ($mediaIds) {
            // Copy existing media from library
            foreach ($mediaIds as $mediaId) {
                $media = Media::find($mediaId);
                if ($media) {
                    $media->copy($article, 'gallery');
                }
            }
        }
    }

    /**
     * Clear all images from a specific collection
     *
     * @param Article $article
     * @param string $collectionName Collection name (featured_image, og_image, gallery)
     * @return void
     */
    public function clearMediaCollection(Article $article, string $collectionName): void
    {
        $article->clearMediaCollection($collectionName);
    }
}
