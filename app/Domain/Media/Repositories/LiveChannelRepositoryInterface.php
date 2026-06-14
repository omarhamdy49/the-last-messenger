<?php

declare(strict_types=1);

namespace App\Domain\Media\Repositories;

use App\Domain\Media\Models\LiveChannel;
use App\Domain\Shared\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface LiveChannelRepositoryInterface extends RepositoryInterface
{
    /**
     * Paginated admin list, optionally filtered by status/search term.
     */
    public function paginateChannels(
        int $perPage = 15,
        ?string $status = null,
        ?string $search = null,
        string $sortBy = 'display_order',
        string $sortDir = 'asc'
    ): LengthAwarePaginator;

    /**
     * Published channels for the public row (live first, then display order).
     */
    public function getPublishedForRow(int $limit = 12): Collection;

    /**
     * A single published channel by slug (null when missing/unpublished).
     */
    public function findPublishedBySlug(string $slug): ?LiveChannel;

    /**
     * Other published channels excluding the given id (related list).
     */
    public function getRelated(int $excludeId, int $limit = 6): Collection;

    /**
     * Whether a slug already exists (admin uniqueness on auto-generate).
     */
    public function slugExists(string $slug): bool;

    public function clearCacheForModel(int $id): void;
}
