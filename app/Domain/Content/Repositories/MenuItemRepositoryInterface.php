<?php

declare(strict_types=1);

namespace App\Domain\Content\Repositories;

use App\Domain\Content\Enums\MenuLocation;
use App\Domain\Content\Models\MenuItem;
use Illuminate\Database\Eloquent\Collection;

interface MenuItemRepositoryInterface
{
    /**
     * Get the active items of one menu location, ordered for display,
     * with all their label translations eager loaded.
     *
     * @return Collection<int, MenuItem>
     */
    public function getActiveByLocation(MenuLocation|string $location): Collection;

    /**
     * Get every item of one location (admin listing, includes inactive).
     *
     * @return Collection<int, MenuItem>
     */
    public function getAllByLocation(MenuLocation|string $location): Collection;

    /**
     * Find one item with all its label translations (admin forms).
     */
    public function findWithTranslations(int $id): ?MenuItem;

    /**
     * Create an item and its label translations atomically.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string, array<string, string|null>>  $translations  Field → locale → value
     */
    public function create(array $attributes, array $translations): MenuItem;

    /**
     * Update an item and its label translations atomically.
     * Returns null when the item does not exist.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string, array<string, string|null>>  $translations  Field → locale → value
     */
    public function update(int $id, array $attributes, array $translations): ?MenuItem;

    /**
     * Delete an item (children are re-parented to root by the FK).
     * Returns false when the item does not exist.
     */
    public function delete(int $id): bool;

    /**
     * Move an item one position up or down among its siblings
     * (same location + parent). Returns false when the item is missing
     * or already at the edge.
     */
    public function moveWithinSiblings(int $id, string $direction): bool;
}
