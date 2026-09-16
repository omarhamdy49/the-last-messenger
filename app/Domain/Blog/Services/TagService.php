<?php

declare(strict_types=1);

namespace App\Domain\Blog\Services;

use App\Domain\Blog\DTOs\CreateTagData;
use App\Domain\Blog\DTOs\UpdateTagData;
use App\Domain\Blog\Exceptions\CannotMergeTagException;
use App\Domain\Blog\Models\Tag;
use App\Domain\Blog\Repositories\TagRepositoryInterface;
use App\Domain\Shared\Services\SlugGenerator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
        protected TagRepositoryInterface $repository,
        protected SlugGenerator $slugs = new SlugGenerator,
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

        // Generate slug if not provided.
        // No uniqueness check here — `tags.slug` has never been disambiguated
        // and adding a `-N` suffix would change the slug of every duplicate
        // name currently produced.
        $nonTranslatableData['slug'] = $data->slug
            ?? $this->slugs->fromLocalized($data->name, 'tag');

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

        // Generate slug if not provided (see create() — deliberately not unique)
        $nonTranslatableData['slug'] = $data->slug
            ?? $this->slugs->fromLocalized($data->name, 'tag');

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
     * Move all content links from source tag to destination tag, then delete source.
     * All write operations are wrapped in a single database transaction so that
     * a mid-merge failure leaves the data in a consistent state.
     *
     * @return int Number of source content links
     *
     * @throws CannotMergeTagException
     */
    public function mergeTags(int $sourceId, int $destinationId): int
    {
        if ($sourceId === $destinationId) {
            throw CannotMergeTagException::selfMerge();
        }

        $this->findOrFail($sourceId);
        $this->findOrFail($destinationId);

        return DB::transaction(function () use ($sourceId, $destinationId): int {
            $links = DB::table('taggables')->where('tag_id', $sourceId)->get();
            $count = $links->count();
            $now = now();

            // Preserve every content type, including links already present on
            // the destination. The composite unique key prevents duplicates.
            foreach ($links->chunk(500) as $chunk) {
                DB::table('taggables')->upsert(
                    $chunk->map(fn ($link) => [
                        'tag_id' => $destinationId,
                        'taggable_type' => $link->taggable_type,
                        'taggable_id' => $link->taggable_id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all(),
                    ['tag_id', 'taggable_id', 'taggable_type'],
                    ['updated_at'],
                );
            }

            Tag::whereKey($destinationId)->update([
                'usage_count' => DB::table('taggables')->where('tag_id', $destinationId)->count(),
            ]);

            // Delete source tag (the repository detaches its pivot rows first)
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
