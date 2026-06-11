<?php

declare(strict_types=1);

namespace App\Domain\Media\Services;

use App\Domain\Media\DTOs\CreateMediaProgramData;
use App\Domain\Media\DTOs\UpdateMediaProgramData;
use App\Domain\Media\Models\MediaProgram;
use App\Domain\Media\Repositories\MediaProgramRepositoryInterface;
use App\Domain\Shared\Services\BaseService;
use App\Exceptions\Domain\ResourceNotFoundException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class MediaProgramService extends BaseService
{
    public function __construct(
        MediaProgramRepositoryInterface $repository
    ) {
        $this->repository = $repository;
    }

    public function paginatePrograms(
        int $perPage = 15,
        ?string $category = null,
        string $sortBy = 'display_order',
        string $sortDir = 'asc',
        ?string $search = null,
        bool $publishedOnly = false,
        ?string $status = null
    ): LengthAwarePaginator {
        return $this->repository->paginatePrograms($perPage, $category, $sortBy, $sortDir, $search, $publishedOnly, $status);
    }

    public function getPopular(int $limit = 12): Collection
    {
        return $this->repository->getPopular($limit);
    }

    public function getRecent(int $limit = 12): Collection
    {
        return $this->repository->getRecent($limit);
    }

    public function getFeatured(int $limit = 6): Collection
    {
        return $this->repository->getFeatured($limit);
    }

    public function getFeaturedForHero(int $limit = 5): Collection
    {
        return $this->repository->getFeaturedForHero($limit);
    }

    public function getTrendingFlagged(int $limit = 12): Collection
    {
        return $this->repository->getTrendingFlagged($limit);
    }

    public function getByCategory(string $category, int $limit = 12): Collection
    {
        return $this->repository->getByCategory($category, $limit);
    }

    public function search(string $term, int $perPage = 15, bool $publishedOnly = false): LengthAwarePaginator
    {
        return $this->repository->search($term, $perPage, $publishedOnly);
    }

    /**
     * @throws ResourceNotFoundException
     */
    public function getBySlug(string $slug): MediaProgram
    {
        $program = $this->repository->findBySlug($slug);

        if (! $program) {
            throw new ResourceNotFoundException("Media program not found: {$slug}");
        }

        $program->incrementViews();

        return $program;
    }

    public function createProgram(CreateMediaProgramData $data): MediaProgram
    {
        /** @var MediaProgram $program */
        $program = $this->repository->create([
            'slug' => $data->slug,
            'category' => $data->category,
            'maturity_rating' => $data->maturityRating,
            'year' => $data->year,
            'total_seasons' => $data->totalSeasons,
            'total_episodes' => $data->totalEpisodes,
            'rating' => $data->rating,
            'is_featured' => $data->isFeatured,
            'is_trending' => $data->isTrending,
            'is_published' => $data->isPublished,
            'publish_at' => $data->publishAt,
            'display_order' => $data->displayOrder,
            'views_count' => 0,
        ]);

        return $program;
    }

    /**
     * @throws ResourceNotFoundException
     */
    public function updateProgram(int $id, UpdateMediaProgramData $data): MediaProgram
    {
        $updated = $this->repository->update($id, [
            'slug' => $data->slug,
            'category' => $data->category,
            'maturity_rating' => $data->maturityRating,
            'year' => $data->year,
            'total_seasons' => $data->totalSeasons,
            'total_episodes' => $data->totalEpisodes,
            'rating' => $data->rating,
            'is_featured' => $data->isFeatured,
            'is_trending' => $data->isTrending,
            'is_published' => $data->isPublished,
            'publish_at' => $data->publishAt,
            'display_order' => $data->displayOrder,
        ]);

        if (! $updated) {
            throw new ResourceNotFoundException("Media program not found: {$id}");
        }

        $this->repository->clearCacheForModel($id);

        return $this->repository->find($id);
    }

    /**
     * @throws ResourceNotFoundException
     */
    public function deleteProgram(int $id): bool
    {
        $deleted = $this->repository->delete($id);

        if (! $deleted) {
            throw new ResourceNotFoundException("Media program not found: {$id}");
        }

        $this->repository->clearCacheForModel($id);

        return true;
    }

    public function getIndexStats(): array
    {
        return $this->repository->getIndexStats();
    }

    /**
     * Publish every program whose scheduled publish_at has passed.
     *
     * @return int Number of programs published
     */
    public function publishDuePrograms(): int
    {
        return $this->repository->publishDue();
    }

    /**
     * Category keys featured on the public programs list, in display order.
     *
     * @return list<string>
     */
    public function getFeaturedCategoryKeys(): array
    {
        return $this->repository->getFeaturedCategoryKeys();
    }

    /** All category rows with program counts, for the admin screen. */
    public function getAllCategoriesWithProgramCounts(): Collection
    {
        return $this->repository->getAllCategoriesWithProgramCounts();
    }

    /** Toggle a category's visibility on the public programs list. */
    public function setCategoryFeatured(string $key, bool $isFeatured): void
    {
        $this->repository->setCategoryFeatured($key, $isFeatured);
    }
}
