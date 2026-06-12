<?php

declare(strict_types=1);

namespace App\Domain\Content\Services;

use App\Domain\Content\Models\HomeModuleCard;
use App\Domain\Content\Models\QuickFilterChip;
use App\Domain\Content\Repositories\HomeContentRepositoryInterface;
use Illuminate\Support\Facades\Cache;

/**
 * Home Content Service
 *
 * Serves the home page module cards and hero quick-filter chips,
 * forever-cached per locale (stable data), flushed on writes.
 * Translations fall back to Arabic via the localized-content trait.
 */
final class HomeContentService
{
    public function __construct(
        private readonly HomeContentRepositoryInterface $repository,
    ) {}

    /**
     * Active module cards in display order, resolved for one locale.
     *
     * @return array<int, array{key: string, icon: string|null, image: string|null, target_url: string|null, accent_color: string|null, is_featured: bool, title: string|null, description: string|null}>
     */
    public function activeCards(?string $locale = null): array
    {
        $locale = $locale ?? app()->getLocale();

        return Cache::rememberForever(ContentCacheKeys::homeCards($locale), function () use ($locale): array {
            return $this->repository->getActiveCards()
                ->map(static fn (HomeModuleCard $card): array => [
                    'key' => $card->key,
                    'icon' => $card->icon,
                    'image' => $card->image,
                    'target_url' => $card->target_url,
                    'accent_color' => $card->accent_color,
                    'is_featured' => $card->is_featured,
                    'title' => $card->getTranslation('title', $locale),
                    'description' => $card->getTranslation('description', $locale),
                ])
                ->values()
                ->all();
        });
    }

    /**
     * Active quick-filter chips in display order, resolved for one locale.
     *
     * @return array<int, array{key: string, label: string|null, target_query: string|null, target_url: string|null}>
     */
    public function activeChips(?string $locale = null): array
    {
        $locale = $locale ?? app()->getLocale();

        return Cache::rememberForever(ContentCacheKeys::homeChips($locale), function () use ($locale): array {
            return $this->repository->getActiveChips()
                ->map(static fn (QuickFilterChip $chip): array => [
                    'key' => $chip->key,
                    'label' => $chip->getTranslation('label', $locale),
                    'target_query' => $chip->target_query,
                    'target_url' => $chip->target_url,
                ])
                ->values()
                ->all();
        });
    }

    /**
     * Invalidate cached cards and chips for every locale.
     */
    public function flush(): void
    {
        foreach ([...ContentCacheKeys::allHomeCardKeys(), ...ContentCacheKeys::allHomeChipKeys()] as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Every module card with translations for admin management (uncached).
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, HomeModuleCard>
     */
    public function cardsForAdmin(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repository->allCardsWithTranslations();
    }

    /**
     * Find one module card for the admin edit form (uncached).
     */
    public function cardForAdmin(int $id): ?HomeModuleCard
    {
        return $this->repository->findCard($id);
    }

    /**
     * Create a module card and flush cached home content.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string, array<string, string|null>>  $translations
     */
    public function createCard(array $attributes, array $translations): HomeModuleCard
    {
        $card = $this->repository->createCard($attributes, $translations);

        $this->flush();

        return $card;
    }

    /**
     * Update a module card and flush cached home content.
     * Returns null when the card does not exist.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string, array<string, string|null>>  $translations
     */
    public function updateCard(int $id, array $attributes, array $translations): ?HomeModuleCard
    {
        $card = $this->repository->updateCard($id, $attributes, $translations);

        if ($card !== null) {
            $this->flush();
        }

        return $card;
    }

    /**
     * Delete a module card and flush cached home content.
     */
    public function deleteCard(int $id): bool
    {
        $deleted = $this->repository->deleteCard($id);

        if ($deleted) {
            $this->flush();
        }

        return $deleted;
    }

    /**
     * Every quick-filter chip with translations for admin management (uncached).
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, QuickFilterChip>
     */
    public function chipsForAdmin(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repository->allChipsWithTranslations();
    }

    /**
     * Find one quick-filter chip for the admin edit form (uncached).
     */
    public function chipForAdmin(int $id): ?QuickFilterChip
    {
        return $this->repository->findChip($id);
    }

    /**
     * Create a quick-filter chip and flush cached home content.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string, array<string, string|null>>  $translations
     */
    public function createChip(array $attributes, array $translations): QuickFilterChip
    {
        $chip = $this->repository->createChip($attributes, $translations);

        $this->flush();

        return $chip;
    }

    /**
     * Update a quick-filter chip and flush cached home content.
     * Returns null when the chip does not exist.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string, array<string, string|null>>  $translations
     */
    public function updateChip(int $id, array $attributes, array $translations): ?QuickFilterChip
    {
        $chip = $this->repository->updateChip($id, $attributes, $translations);

        if ($chip !== null) {
            $this->flush();
        }

        return $chip;
    }

    /**
     * Delete a quick-filter chip and flush cached home content.
     */
    public function deleteChip(int $id): bool
    {
        $deleted = $this->repository->deleteChip($id);

        if ($deleted) {
            $this->flush();
        }

        return $deleted;
    }
}
