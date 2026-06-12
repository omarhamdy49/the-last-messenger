<?php

declare(strict_types=1);

namespace App\Domain\Content\Repositories;

use App\Domain\Content\Models\HomeModuleCard;
use App\Domain\Content\Models\QuickFilterChip;
use Illuminate\Database\Eloquent\Collection;

interface HomeContentRepositoryInterface
{
    /**
     * Active home module cards in display order with translations.
     *
     * @return Collection<int, HomeModuleCard>
     */
    public function getActiveCards(): Collection;

    /**
     * Active quick-filter chips in display order with translations.
     *
     * @return Collection<int, QuickFilterChip>
     */
    public function getActiveChips(): Collection;

    /**
     * Every module card with translations, ordered (admin listing).
     *
     * @return Collection<int, HomeModuleCard>
     */
    public function allCardsWithTranslations(): Collection;

    /**
     * Find one module card with translations (admin forms).
     */
    public function findCard(int $id): ?HomeModuleCard;

    /**
     * Create a module card and its translations atomically.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string, array<string, string|null>>  $translations  Field → locale → value
     */
    public function createCard(array $attributes, array $translations): HomeModuleCard;

    /**
     * Update a module card and its translations atomically.
     * Returns null when the card does not exist.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string, array<string, string|null>>  $translations  Field → locale → value
     */
    public function updateCard(int $id, array $attributes, array $translations): ?HomeModuleCard;

    /**
     * Delete a module card. Returns false when missing.
     */
    public function deleteCard(int $id): bool;

    /**
     * Every quick-filter chip with translations, ordered (admin listing).
     *
     * @return Collection<int, QuickFilterChip>
     */
    public function allChipsWithTranslations(): Collection;

    /**
     * Find one quick-filter chip with translations (admin forms).
     */
    public function findChip(int $id): ?QuickFilterChip;

    /**
     * Create a quick-filter chip and its translations atomically.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string, array<string, string|null>>  $translations  Field → locale → value
     */
    public function createChip(array $attributes, array $translations): QuickFilterChip;

    /**
     * Update a quick-filter chip and its translations atomically.
     * Returns null when the chip does not exist.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string, array<string, string|null>>  $translations  Field → locale → value
     */
    public function updateChip(int $id, array $attributes, array $translations): ?QuickFilterChip;

    /**
     * Delete a quick-filter chip. Returns false when missing.
     */
    public function deleteChip(int $id): bool;
}
