<?php

declare(strict_types=1);

namespace App\Domain\Blog\Services;

use App\Models\ArticleLocale;
use Illuminate\Support\Str;

/**
 * Slug Generator Service
 *
 * Generates unique, locale-aware slugs for article locales.
 * Extracted from ArticleController to keep business logic in the domain layer.
 */
class SlugGeneratorService
{
    /**
     * Generate a unique slug for the given title and locale.
     *
     * Appends an incrementing counter suffix when the base slug is already
     * taken by another article locale.
     *
     * @param string      $title            The human-readable title to slugify.
     * @param string      $locale           The locale the slug belongs to (e.g. 'ar', 'en').
     * @param int|null    $excludeArticleId When updating, exclude this article's own locales
     *                                      from the uniqueness check.
     */
    public function generate(string $title, string $locale, ?int $excludeArticleId = null): string
    {
        $slug         = Str::slug($title);
        $originalSlug = $slug;
        $counter      = 1;

        while (true) {
            $query = ArticleLocale::where('slug', $slug)
                ->where('locale', $locale);

            if ($excludeArticleId !== null) {
                $query->where('article_id', '!=', $excludeArticleId);
            }

            if (! $query->exists()) {
                break;
            }

            $slug = $originalSlug . '-' . $counter++;
        }

        return $slug;
    }
}
