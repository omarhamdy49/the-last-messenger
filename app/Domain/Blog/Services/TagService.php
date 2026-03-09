<?php

declare(strict_types=1);

namespace App\Domain\Blog\Services;

use App\Domain\Blog\Models\Tag;
use App\Domain\Blog\Repositories\TagRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

/**
 * TagService
 *
 * Business logic for tag management
 */
class TagService
{
    /**
     * Constructor with repository injection
     *
     * @param TagRepositoryInterface $repository
     */
    public function __construct(
        protected TagRepositoryInterface $repository
    ) {
    }

    /**
     * Get paginated tags with search and filters
     *
     * @param int $perPage
     * @param string $search
     * @param array $filters
     * @param array $with
     * @return LengthAwarePaginator
     */
    public function paginate(
        int $perPage = 50,
        string $search = '',
        array $filters = [],
        array $with = []
    ): LengthAwarePaginator {
        return $this->repository->paginate(
            perPage: $perPage,
            search: $search,
            filters: $filters,
            with: $with
        );
    }

    /**
     * Create new tag
     *
     * @param array $data
     * @return Model
     */
    public function create(array $data): Model
    {
        // Separate translatable fields
        $translatableFields = ['name', 'description'];
        $translatableData = [];
        $nonTranslatableData = [];

        foreach ($data as $key => $value) {
            if (in_array($key, $translatableFields)) {
                $translatableData[$key] = $value;
            } else {
                $nonTranslatableData[$key] = $value;
            }
        }

        // Generate slug if not provided
        if (empty($nonTranslatableData['slug'])) {
            $titleForSlug = $translatableData['name']['en'] ?? $translatableData['name']['ar'] ?? 'tag';
            $nonTranslatableData['slug'] = \Illuminate\Support\Str::slug($titleForSlug);
        }

        // Create tag with non-translatable data only
        /** @var Tag */
        $tag = $this->repository->create($nonTranslatableData);

        // Sync translations
        if (!empty($translatableData)) {
            $tag->syncTranslations($translatableData);
        }

        return $tag;
    }

    /**
     * Update tag
     *
     * @param int $id
     * @param array $data
     * @return Model
     */
    public function update(int $id, array $data): Model
    {
        // Get current tag
        /** @var Tag */
        $tag = $this->repository->findOrFail($id, with: ['translations']);

        // Separate translatable fields
        $translatableFields = ['name', 'description'];
        $translatableData = [];
        $nonTranslatableData = [];

        foreach ($data as $key => $value) {
            if (in_array($key, $translatableFields)) {
                $translatableData[$key] = $value;
            } else {
                $nonTranslatableData[$key] = $value;
            }
        }

        // Generate slug if not provided
        if (empty($nonTranslatableData['slug'])) {
            if (!empty($translatableData['name'])) {
                $nonTranslatableData['slug'] = \Illuminate\Support\Str::slug(
                    $translatableData['name']['en'] ?? $translatableData['name']['ar']
                );
            }
        }

        // Update non-translatable fields
        if (!empty($nonTranslatableData)) {
            $this->repository->update($id, $nonTranslatableData);
        }

        // Sync translations
        if (!empty($translatableData)) {
            $tag->syncTranslations($translatableData);
        }

        return $this->repository->findOrFail($id);
    }

    /**
     * Delete tag
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }

    /**
     * Bulk delete multiple tags
     *
     * @param array $ids
     * @return int
     */
    public function bulkDelete(array $ids): int
    {
        return $this->repository->bulkDelete($ids);
    }

    /**
     * Find tag by ID or throw exception
     *
     * @param int $id
     * @return Model
     */
    public function findOrFail(int $id): Model
    {
        return $this->repository->findOrFail($id);
    }

    /**
     * Get total count of tags
     *
     * @return int
     */
    public function count(): int
    {
        return $this->repository->count();
    }

    /**
     * Get total usage count (how many articles use tags)
     *
     * @return int
     */
    public function getTotalUsageCount(): int
    {
        return $this->repository->getTotalUsageCount();
    }

    /**
     * Get most used tag
     *
     * @return Model|null
     */
    public function getMostUsedTag(): ?Model
    {
        return $this->repository->getMostUsedTag();
    }

    /**
     * Get recently added tags count
     *
     * @param int $days
     * @return int
     */
    public function getRecentlyAddedCount(int $days = 7): int
    {
        return $this->repository->getRecentlyAddedCount($days);
    }

    /**
     * Get usage count for a specific tag
     *
     * @param int $tagId
     * @return int
     */
    public function getUsageCount(int $tagId): int
    {
        return $this->repository->getUsageCount($tagId);
    }

    /**
     * Get published articles count for a tag
     *
     * @param int $tagId
     * @return int
     */
    public function getPublishedArticlesCount(int $tagId): int
    {
        return $this->repository->getPublishedArticlesCount($tagId);
    }

    /**
     * Get articles for a specific tag (paginated)
     *
     * @param int $tagId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getArticlesForTag(int $tagId, int $perPage = 20): LengthAwarePaginator
    {
        return $this->repository->getArticlesForTag($tagId, $perPage);
    }

    /**
     * Find duplicate tag by name
     *
     * @param array $name
     * @return Model|null
     */
    public function findDuplicate(array $name): ?Model
    {
        return $this->repository->findDuplicate($name);
    }

    /**
     * Merge two tags
     *
     * Move all articles from source tag to destination tag, then delete source
     *
     * @param int $sourceId
     * @param int $destinationId
     * @return int Number of articles moved
     * @throws \Exception
     */
    public function mergeTags(int $sourceId, int $destinationId): int
    {
        if ($sourceId === $destinationId) {
            throw new \Exception('Cannot merge a tag with itself');
        }

        $sourceTag = $this->findOrFail($sourceId);
        $destinationTag = $this->findOrFail($destinationId);

        // Get all articles from source tag
        $articles = $sourceTag->articles;
        $count = $articles->count();

        // Attach articles to destination tag (if not already attached)
        foreach ($articles as $article) {
            if (!$article->tags->contains($destinationId)) {
                $article->tags()->attach($destinationId);
            }
            // Detach from source tag
            $article->tags()->detach($sourceId);
        }

        // Delete source tag
        $this->delete($sourceId);

        return $count;
    }

    /**
     * Search tags for autocomplete
     *
     * @param string $query
     * @param string|null $type
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public function searchForAutocomplete(string $query, ?string $type = null, int $limit = 10): \Illuminate\Support\Collection
    {
        return $this->repository->searchForAutocomplete($query, $type, $limit);
    }

    /**
     * Get unused tags (zero usage_count)
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getUnusedTags(int $perPage = 50): LengthAwarePaginator
    {
        return $this->repository->getUnusedTags($perPage);
    }

    /**
     * Recalculate usage count for all tags
     *
     * @return int Number of tags updated
     */
    public function recalculateAllUsageCounts(): int
    {
        return $this->repository->recalculateAllUsageCounts();
    }
}
