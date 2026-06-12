<?php

declare(strict_types=1);

namespace App\Domain\Content\Repositories;

use App\Domain\Content\Enums\MenuLocation;
use App\Domain\Content\Models\MenuItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Eloquent implementation of the menu items repository.
 */
class MenuItemRepository implements MenuItemRepositoryInterface
{
    /**
     * Columns the menu tree builder needs.
     *
     * @var array<int, string>
     */
    private const ITEM_COLUMNS = ['id', 'location', 'parent_id', 'route_name', 'url', 'icon', 'is_active', 'sort_order'];

    private const TRANSLATION_COLUMNS = 'translations:id,menu_item_id,locale,label';

    public function getActiveByLocation(MenuLocation|string $location): Collection
    {
        return MenuItem::query()
            ->select(self::ITEM_COLUMNS)
            ->with([self::TRANSLATION_COLUMNS])
            ->byLocation($location)
            ->active()
            ->ordered()
            ->get();
    }

    public function getAllByLocation(MenuLocation|string $location): Collection
    {
        return MenuItem::query()
            ->select(self::ITEM_COLUMNS)
            ->with([self::TRANSLATION_COLUMNS])
            ->byLocation($location)
            ->ordered()
            ->get();
    }

    public function findWithTranslations(int $id): ?MenuItem
    {
        return MenuItem::query()
            ->select(self::ITEM_COLUMNS)
            ->with([self::TRANSLATION_COLUMNS])
            ->find($id);
    }

    public function create(array $attributes, array $translations): MenuItem
    {
        return DB::transaction(function () use ($attributes, $translations): MenuItem {
            if (! array_key_exists('sort_order', $attributes) || $attributes['sort_order'] === null) {
                $attributes['sort_order'] = $this->nextSortOrder(
                    (string) $attributes['location'],
                    $attributes['parent_id'] ?? null,
                );
            }

            /** @var MenuItem $item */
            $item = MenuItem::query()->create($attributes);
            $item->syncTranslations($translations);

            return $item;
        });
    }

    public function update(int $id, array $attributes, array $translations): ?MenuItem
    {
        return DB::transaction(function () use ($id, $attributes, $translations): ?MenuItem {
            if (array_key_exists('sort_order', $attributes) && $attributes['sort_order'] === null) {
                // Keep the current position when the form sends no explicit order.
                unset($attributes['sort_order']);
            }

            /** @var MenuItem|null $item */
            $item = MenuItem::query()->find($id);

            if ($item === null) {
                return null;
            }

            $item->update($attributes);
            $item->syncTranslations($translations);

            return $item;
        });
    }

    public function delete(int $id): bool
    {
        // Children fall back to root via the parent_id nullOnDelete FK.
        return MenuItem::query()->whereKey($id)->delete() > 0;
    }

    public function moveWithinSiblings(int $id, string $direction): bool
    {
        return DB::transaction(function () use ($id, $direction): bool {
            /** @var MenuItem|null $item */
            $item = MenuItem::query()
                ->select(['id', 'location', 'parent_id', 'sort_order'])
                ->lockForUpdate()
                ->find($id);

            if ($item === null) {
                return false;
            }

            $siblings = MenuItem::query()
                ->select(['id', 'sort_order'])
                ->where('location', $item->location->value)
                ->where('parent_id', $item->parent_id)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $index = $siblings->search(fn (MenuItem $sibling): bool => $sibling->id === $item->id);
            $target = $direction === 'up' ? $index - 1 : $index + 1;

            if ($index === false || $target < 0 || $target >= $siblings->count()) {
                return false;
            }

            // Swap positions, then renumber so duplicate sort_order values self-heal.
            $ordered = $siblings->values()->all();
            [$ordered[$index], $ordered[$target]] = [$ordered[$target], $ordered[$index]];

            foreach ($ordered as $position => $sibling) {
                if ($sibling->sort_order !== $position) {
                    MenuItem::query()->whereKey($sibling->id)->update(['sort_order' => $position]);
                }
            }

            return true;
        });
    }

    /**
     * Next sort_order slot at the end of one location/parent branch.
     */
    private function nextSortOrder(string $location, ?int $parentId): int
    {
        $max = MenuItem::query()
            ->where('location', $location)
            ->where('parent_id', $parentId)
            ->max('sort_order');

        return $max === null ? 0 : ((int) $max) + 1;
    }
}
