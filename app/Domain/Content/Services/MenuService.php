<?php

declare(strict_types=1);

namespace App\Domain\Content\Services;

use App\Domain\Content\Enums\MenuLocation;
use App\Domain\Content\Models\MenuItem;
use App\Domain\Content\Repositories\MenuItemRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Menu Service
 *
 * Builds the navigation tree for one menu location and locale. Trees are
 * forever-cached per (location, locale) — menus are stable data — and
 * flushed on every write. Labels fall back to Arabic when the requested
 * locale has no translation.
 */
final class MenuService
{
    public function __construct(
        private readonly MenuItemRepositoryInterface $repository,
    ) {}

    /**
     * Get the active menu tree for a location, ready for Blade rendering.
     *
     * @return array<int, array{id: int, label: string|null, route_name: string|null, url: string|null, icon: string|null, children: array<int, mixed>}>
     */
    public function tree(MenuLocation|string $location, ?string $locale = null): array
    {
        $locationValue = $location instanceof MenuLocation ? $location->value : $location;
        $locale = $locale ?? app()->getLocale();

        return Cache::rememberForever(
            ContentCacheKeys::menuTree($locationValue, $locale),
            function () use ($locationValue, $locale): array {
                $items = $this->repository->getActiveByLocation($locationValue);

                return $this->buildBranch($items, null, $locale);
            }
        );
    }

    /**
     * Get every item of a location for admin management (uncached).
     *
     * @return Collection<int, MenuItem>
     */
    public function itemsForAdmin(MenuLocation|string $location): Collection
    {
        return $this->repository->getAllByLocation($location);
    }

    /**
     * Invalidate every cached menu tree (all locations × locales).
     */
    public function flush(): void
    {
        foreach (ContentCacheKeys::allMenuTreeKeys() as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Recursively assemble one branch of the tree from the flat,
     * already-ordered item collection (no extra queries per level).
     *
     * @param  Collection<int, MenuItem>  $items
     * @return array<int, array<string, mixed>>
     */
    private function buildBranch(Collection $items, ?int $parentId, string $locale): array
    {
        return $items
            ->where('parent_id', $parentId)
            ->map(fn (MenuItem $item): array => [
                'id' => $item->id,
                'label' => $item->getTranslation('label', $locale),
                'route_name' => $item->route_name,
                'url' => $item->url,
                'icon' => $item->icon,
                'children' => $this->buildBranch($items, $item->id, $locale),
            ])
            ->values()
            ->all();
    }

    /**
     * Find one item with translations for the admin edit form (uncached).
     */
    public function itemForAdmin(int $id): ?MenuItem
    {
        return $this->repository->findWithTranslations($id);
    }

    /**
     * Create an item and flush every cached tree.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string, array<string, string|null>>  $translations
     */
    public function create(array $attributes, array $translations): MenuItem
    {
        $item = $this->repository->create($attributes, $translations);

        $this->flush();

        return $item;
    }

    /**
     * Update an item and flush every cached tree.
     * Returns null when the item does not exist.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string, array<string, string|null>>  $translations
     */
    public function update(int $id, array $attributes, array $translations): ?MenuItem
    {
        $item = $this->repository->update($id, $attributes, $translations);

        if ($item !== null) {
            $this->flush();
        }

        return $item;
    }

    /**
     * Delete an item and flush every cached tree.
     */
    public function delete(int $id): bool
    {
        $deleted = $this->repository->delete($id);

        if ($deleted) {
            $this->flush();
        }

        return $deleted;
    }

    /**
     * Move an item one position up/down among its siblings and flush.
     */
    public function move(int $id, string $direction): bool
    {
        $moved = $this->repository->moveWithinSiblings($id, $direction);

        if ($moved) {
            $this->flush();
        }

        return $moved;
    }
}
