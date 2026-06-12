<?php

declare(strict_types=1);

namespace App\Domain\Content\Repositories;

use App\Domain\Content\Models\StaticPage;
use Illuminate\Database\Eloquent\Collection;

interface StaticPageRepositoryInterface
{
    /**
     * Find a publicly visible (active + published) page by slug,
     * with all its translations.
     */
    public function findPublishedBySlug(string $slug): ?StaticPage;

    /**
     * All pages with translations (admin listing).
     *
     * @return Collection<int, StaticPage>
     */
    public function allWithTranslations(): Collection;

    /**
     * Find one page with all its translations (admin forms).
     */
    public function findWithTranslations(int $id): ?StaticPage;

    /**
     * Create a page and its translations atomically.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string, array<string, string|null>>  $translations  Field → locale → value
     */
    public function create(array $attributes, array $translations): StaticPage;

    /**
     * Update a page and its translations atomically.
     * Returns null when the page does not exist.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string, array<string, string|null>>  $translations  Field → locale → value
     */
    public function update(int $id, array $attributes, array $translations): ?StaticPage;

    /**
     * Delete a page. Returns false when the page does not exist.
     */
    public function delete(int $id): bool;
}
