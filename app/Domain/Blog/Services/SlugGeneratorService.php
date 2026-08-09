<?php

declare(strict_types=1);

namespace App\Domain\Blog\Services;

use App\Domain\Shared\Services\SlugGenerator;
use App\Models\ArticleLocale;

/**
 * Slug Generator Service
 *
 * Generates unique, locale-aware slugs for article locales.
 * Extracted from ArticleController to keep business logic in the domain layer.
 *
 * The base-slug + `-N` disambiguation loop lives in the shared SlugGenerator;
 * what stays here is the article-locale uniqueness scope, which is unique to
 * this table: slugs collide only within the same locale, and an update
 * excludes every locale row belonging to the article being saved.
 */
class SlugGeneratorService
{
    /**
     * Defaulted rather than required so `new SlugGeneratorService` keeps
     * working for callers that construct it outside the container.
     */
    public function __construct(
        private readonly SlugGenerator $slugs = new SlugGenerator,
    ) {}

    /**
     * Generate a unique slug for the given title and locale.
     *
     * Appends an incrementing counter suffix when the base slug is already
     * taken by another article locale.
     *
     * @param  string  $title  The human-readable title to slugify.
     * @param  string  $locale  The locale the slug belongs to (e.g. 'ar', 'en').
     * @param  int|null  $excludeArticleId  When updating, exclude this article's own locales
     *                                      from the uniqueness check.
     */
    public function generate(string $title, string $locale, ?int $excludeArticleId = null): string
    {
        return $this->slugs->unique(
            $title,
            static function (string $slug) use ($locale, $excludeArticleId): bool {
                $query = ArticleLocale::where('slug', $slug)
                    ->where('locale', $locale);

                if ($excludeArticleId !== null) {
                    $query->where('article_id', '!=', $excludeArticleId);
                }

                return $query->exists();
            }
        );
    }
}
