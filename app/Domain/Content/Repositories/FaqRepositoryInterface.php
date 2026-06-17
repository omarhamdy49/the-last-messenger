<?php

declare(strict_types=1);

namespace App\Domain\Content\Repositories;

use App\Domain\Content\Models\Faq;
use Illuminate\Database\Eloquent\Collection;

interface FaqRepositoryInterface
{
    /**
     * Active FAQs in display order with all translations.
     *
     * @return Collection<int, Faq>
     */
    public function getActive(): Collection;

    /**
     * Every FAQ with translations, ordered for display (admin listing,
     * includes inactive).
     *
     * @return Collection<int, Faq>
     */
    public function allWithTranslations(): Collection;

    /**
     * Find one FAQ with all its translations (admin forms).
     */
    public function findWithTranslations(int $id): ?Faq;

    /**
     * Create a FAQ and its translations atomically.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string, array<string, string|null>>  $translations  Field → locale → value
     */
    public function create(array $attributes, array $translations): Faq;

    /**
     * Update a FAQ and its translations atomically.
     * Returns null when the FAQ does not exist.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string, array<string, string|null>>  $translations  Field → locale → value
     */
    public function update(int $id, array $attributes, array $translations): ?Faq;

    /**
     * Delete a FAQ. Returns false when the FAQ does not exist.
     */
    public function delete(int $id): bool;

    /**
     * Distinct, non-empty FAQ categories (for category pickers).
     *
     * @return list<string>
     */
    public function distinctCategories(): array;
}
