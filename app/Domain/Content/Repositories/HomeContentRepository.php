<?php

declare(strict_types=1);

namespace App\Domain\Content\Repositories;

use App\Domain\Content\Enums\HomeModuleCardPlacement;
use App\Domain\Content\Models\HomeModuleCard;
use App\Domain\Content\Models\QuickFilterChip;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Eloquent implementation of the home page content repository
 * (module cards + quick-filter chips).
 */
class HomeContentRepository implements HomeContentRepositoryInterface
{
    /**
     * @var array<int, string>
     */
    private const CARD_COLUMNS = ['id', 'key', 'placement', 'icon', 'image', 'target_url', 'accent_color', 'is_featured', 'is_active', 'sort_order'];

    private const CARD_TRANSLATIONS = 'translations:id,home_module_card_id,locale,title,description';

    /**
     * @var array<int, string>
     */
    private const CHIP_COLUMNS = ['id', 'key', 'target_query', 'target_url', 'is_active', 'sort_order'];

    private const CHIP_TRANSLATIONS = 'translations:id,quick_filter_chip_id,locale,label';

    public function getActiveCards(HomeModuleCardPlacement $placement): Collection
    {
        return HomeModuleCard::query()
            ->select(self::CARD_COLUMNS)
            ->with([self::CARD_TRANSLATIONS])
            ->where('placement', $placement->value)
            ->active()
            ->ordered()
            ->get();
    }

    public function getActiveChips(): Collection
    {
        return QuickFilterChip::query()
            ->select(self::CHIP_COLUMNS)
            ->with([self::CHIP_TRANSLATIONS])
            ->active()
            ->ordered()
            ->get();
    }

    public function allCardsWithTranslations(?HomeModuleCardPlacement $placement = null): Collection
    {
        return HomeModuleCard::query()
            ->select(self::CARD_COLUMNS)
            ->with([self::CARD_TRANSLATIONS])
            ->when($placement !== null, fn ($query) => $query->where('placement', $placement->value))
            ->ordered()
            ->orderBy('id')
            ->get();
    }

    public function findCard(int $id): ?HomeModuleCard
    {
        return HomeModuleCard::query()
            ->select(self::CARD_COLUMNS)
            ->with([self::CARD_TRANSLATIONS])
            ->find($id);
    }

    public function createCard(array $attributes, array $translations): HomeModuleCard
    {
        return DB::transaction(function () use ($attributes, $translations): HomeModuleCard {
            if (! array_key_exists('sort_order', $attributes) || $attributes['sort_order'] === null) {
                $max = HomeModuleCard::query()
                    ->where('placement', $attributes['placement'])
                    ->max('sort_order');
                $attributes['sort_order'] = $max === null ? 0 : ((int) $max) + 1;
            }

            /** @var HomeModuleCard $card */
            $card = HomeModuleCard::query()->create($attributes);
            $card->syncTranslations($translations);

            return $card;
        });
    }

    public function updateCard(int $id, array $attributes, array $translations): ?HomeModuleCard
    {
        return DB::transaction(function () use ($id, $attributes, $translations): ?HomeModuleCard {
            if (array_key_exists('sort_order', $attributes) && $attributes['sort_order'] === null) {
                // Keep the current position when the form sends no explicit order.
                unset($attributes['sort_order']);
            }

            /** @var HomeModuleCard|null $card */
            $card = HomeModuleCard::query()->find($id);

            if ($card === null) {
                return null;
            }

            $card->update($attributes);
            $card->syncTranslations($translations);

            return $card;
        });
    }

    public function deleteCard(int $id): bool
    {
        return HomeModuleCard::query()->whereKey($id)->delete() > 0;
    }

    public function allChipsWithTranslations(): Collection
    {
        return QuickFilterChip::query()
            ->select(self::CHIP_COLUMNS)
            ->with([self::CHIP_TRANSLATIONS])
            ->ordered()
            ->orderBy('id')
            ->get();
    }

    public function findChip(int $id): ?QuickFilterChip
    {
        return QuickFilterChip::query()
            ->select(self::CHIP_COLUMNS)
            ->with([self::CHIP_TRANSLATIONS])
            ->find($id);
    }

    public function createChip(array $attributes, array $translations): QuickFilterChip
    {
        return DB::transaction(function () use ($attributes, $translations): QuickFilterChip {
            if (! array_key_exists('sort_order', $attributes) || $attributes['sort_order'] === null) {
                $max = QuickFilterChip::query()->max('sort_order');
                $attributes['sort_order'] = $max === null ? 0 : ((int) $max) + 1;
            }

            /** @var QuickFilterChip $chip */
            $chip = QuickFilterChip::query()->create($attributes);
            $chip->syncTranslations($translations);

            return $chip;
        });
    }

    public function updateChip(int $id, array $attributes, array $translations): ?QuickFilterChip
    {
        return DB::transaction(function () use ($id, $attributes, $translations): ?QuickFilterChip {
            if (array_key_exists('sort_order', $attributes) && $attributes['sort_order'] === null) {
                // Keep the current position when the form sends no explicit order.
                unset($attributes['sort_order']);
            }

            /** @var QuickFilterChip|null $chip */
            $chip = QuickFilterChip::query()->find($id);

            if ($chip === null) {
                return null;
            }

            $chip->update($attributes);
            $chip->syncTranslations($translations);

            return $chip;
        });
    }

    public function deleteChip(int $id): bool
    {
        return QuickFilterChip::query()->whereKey($id)->delete() > 0;
    }
}
