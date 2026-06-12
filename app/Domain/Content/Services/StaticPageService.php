<?php

declare(strict_types=1);

namespace App\Domain\Content\Services;

use App\Domain\Content\Models\StaticPage;
use App\Domain\Content\Repositories\StaticPageRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Static Page Service
 *
 * Resolves publicly visible pages by slug per locale, forever-cached
 * (missing pages are never cached, so a later publish shows immediately).
 * Title/body/SEO fall back to Arabic via the localized-content trait.
 */
final class StaticPageService
{
    public function __construct(
        private readonly StaticPageRepositoryInterface $repository,
    ) {}

    /**
     * Get a published page resolved for one locale, or null when the slug
     * is unknown, inactive, or not yet published.
     *
     * @return array{id: int, slug: string, title: string|null, body: string|null, seo_title: string|null, seo_description: string|null, published_at: \Illuminate\Support\Carbon|null}|null
     */
    public function getBySlug(string $slug, ?string $locale = null): ?array
    {
        $locale = $locale ?? app()->getLocale();

        // rememberForever skips storing null, so misses stay uncached.
        return Cache::rememberForever(
            ContentCacheKeys::staticPage($slug, $locale),
            function () use ($slug, $locale): ?array {
                $page = $this->repository->findPublishedBySlug($slug);

                if ($page === null) {
                    return null;
                }

                return [
                    'id' => $page->id,
                    'slug' => $page->slug,
                    'title' => $page->getTranslation('title', $locale),
                    'body' => $page->getTranslation('body', $locale),
                    'seo_title' => $page->getTranslation('seo_title', $locale),
                    'seo_description' => $page->getTranslation('seo_description', $locale),
                    'published_at' => $page->published_at,
                ];
            }
        );
    }

    /**
     * All pages with translations for admin management (uncached).
     *
     * @return Collection<int, StaticPage>
     */
    public function pagesForAdmin(): Collection
    {
        return $this->repository->allWithTranslations();
    }

    /**
     * Invalidate one page's cached locale variants after a write.
     */
    public function flush(string $slug): void
    {
        foreach (ContentCacheKeys::staticPageKeys($slug) as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Find one page with translations for the admin edit form (uncached).
     */
    public function pageForAdmin(int $id): ?StaticPage
    {
        return $this->repository->findWithTranslations($id);
    }

    /**
     * Create a page and flush its cached locale variants.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string, array<string, string|null>>  $translations
     */
    public function create(array $attributes, array $translations): StaticPage
    {
        $page = $this->repository->create($attributes, $translations);

        $this->flush($page->slug);

        return $page;
    }

    /**
     * Update a page and flush both the previous and current slug caches
     * (slug renames must not leave stale entries behind).
     * Returns null when the page does not exist.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string, array<string, string|null>>  $translations
     */
    public function update(int $id, array $attributes, array $translations): ?StaticPage
    {
        $previous = $this->repository->findWithTranslations($id);

        if ($previous === null) {
            return null;
        }

        $previousSlug = $previous->slug;
        $page = $this->repository->update($id, $attributes, $translations);

        if ($page !== null) {
            $this->flush($previousSlug);
            $this->flush($page->slug);
        }

        return $page;
    }

    /**
     * Delete a page and flush its cached locale variants.
     */
    public function delete(int $id): bool
    {
        $page = $this->repository->findWithTranslations($id);

        if ($page === null) {
            return false;
        }

        $deleted = $this->repository->delete($id);

        if ($deleted) {
            $this->flush($page->slug);
        }

        return $deleted;
    }
}
