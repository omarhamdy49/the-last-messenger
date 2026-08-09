<?php

declare(strict_types=1);

namespace App\Domain\Blog\Services;

use App\Domain\Blog\DTOs\CreateTagData;
use App\Domain\Blog\DTOs\UpdateTagData;
use App\Domain\Blog\Exceptions\CannotMergeTagException;
use App\Domain\Blog\Models\Tag;
use App\Domain\Blog\Repositories\TagRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * TagService
 *
 * Business logic for tag management
 */
class TagService
{
    /**
     * Constructor with repository injection
     */
    public function __construct(
        protected TagRepositoryInterface $repository
    ) {}

    /**
     * Get paginated tags with search and filters
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
     */
    public function create(CreateTagData $data): Model
    {
        // Build the non-translatable column payload
        $nonTranslatableData = [
            'type' => $data->type,
            'color' => $data->color,
        ];

        // Generate slug if not provided
        $nonTranslatableData['slug'] = $data->slug
            ?? Str::slug($data->name['en'] ?? $data->name['ar'] ?? 'tag');

        // Create tag with non-translatable data only
        /** @var Tag */
        $tag = $this->repository->create($nonTranslatableData);

        // Sync translations (name + description are locale-keyed arrays)
        $translatableData = [];

        if (! empty($data->name)) {
            $translatableData['name'] = $data->name;
        }

        if (! empty($data->description)) {
            $translatableData['description'] = $data->description;
        }

        if (! empty($translatableData)) {
            $tag->syncTranslations($translatableData);
        }

        return $tag;
    }

    /**
     * Update tag
     */
    public function update(int $id, UpdateTagData $data): Model
    {
        // Get current tag (with translations for syncTranslations)
        /** @var Tag */
        $tag = $this->repository->findOrFail($id, with: ['translations']);

        // Build the non-translatable column payload
        $nonTranslatableData = ['type' => $data->type, 'color' => $data->color];

        // Generate slug if not provided
        $nonTranslatableData['slug'] = $data->slug
            ?? Str::slug($data->name['en'] ?? $data->name['ar'] ?? 'tag');

        // Update non-translatable fields
        $this->repository->update($id, $nonTranslatableData);

        // Sync translations
        $translatableData = [];
        if (! empty($data->name)) {
            $translatableData['name'] = $data->name;
        }
        if (! empty($data->description)) {
            $translatableData['description'] = $data->description;
        }
        if (! empty($translatableData)) {
            $tag->syncTranslations($translatableData);
        }

        return $this->repository->findOrFail($id);
    }

    /**
     * Delete tag
     */
    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }

    /**
     * Bulk delete multiple tags
     */
    public function bulkDelete(array $ids): int
    {
        return $this->repository->bulkDelete($ids);
    }

    /**
     * Find tag by ID or throw exception
     */
    public function findOrFail(int $id): Model
    {
        return $this->repository->findOrFail($id);
    }

    /**
     * Get total count of tags
     */
    public function count(): int
    {
        return $this->repository->count();
    }

    /**
     * Get total usage count (how many articles use tags)
     */
    public function getTotalUsageCount(): int
    {
        return $this->repository->getTotalUsageCount();
    }

    /**
     * Get most used tag
     */
    public function getMostUsedTag(): ?Model
    {
        return $this->repository->getMostUsedTag();
    }

    /**
     * Get recently added tags count
     */
    public function getRecentlyAddedCount(int $days = 7): int
    {
        return $this->repository->getRecentlyAddedCount($days);
    }

    /**
     * Get usage count for a specific tag
     */
    public function getUsageCount(int $tagId): int
    {
        return $this->repository->getUsageCount($tagId);
    }

    /**
     * Get published articles count for a tag
     */
    public function getPublishedArticlesCount(int $tagId): int
    {
        return $this->repository->getPublishedArticlesCount($tagId);
    }

    /**
     * Get articles for a specific tag (paginated)
     */
    public function getArticlesForTag(int $tagId, int $perPage = 20): LengthAwarePaginator
    {
        return $this->repository->getArticlesForTag($tagId, $perPage);
    }

    /**
     * Find duplicate tag by name
     */
    public function findDuplicate(array $name): ?Model
    {
        return $this->repository->findDuplicate($name);
    }

    /**
     * Merge two tags
     *
     * Move all articles from source tag to destination tag, then delete source.
     * All write operations are wrapped in a single database transaction so that
     * a mid-merge failure leaves the data in a consistent state.
     *
     * @return int Number of articles moved
     *
     * @throws CannotMergeTagException
     */
    public function mergeTags(int $sourceId, int $destinationId): int
    {
        if ($sourceId === $destinationId) {
            throw CannotMergeTagException::selfMerge();
        }

        $sourceTag = $this->findOrFail($sourceId);
        $destinationTag = $this->findOrFail($destinationId);

        return DB::transaction(function () use ($sourceId, $destinationId, $sourceTag): int {
            // Get all articles from source tag
            $articles = $sourceTag->articles;
            $count = $articles->count();

            // Attach articles to destination tag (if not already attached)
            foreach ($articles as $article) {
                if (! $article->tags->contains($destinationId)) {
                    $article->tags()->attach($destinationId);
                }
                // Detach from source tag
                $article->tags()->detach($sourceId);
            }

            // Delete source tag
            $this->delete($sourceId);

            return $count;
        });
    }

    /**
     * Search tags for autocomplete
     */
    public function searchForAutocomplete(string $query, ?string $type = null, int $limit = 10): Collection
    {
        return $this->repository->searchForAutocomplete($query, $type, $limit);
    }

    /**
     * Get unused tags (zero usage_count)
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
