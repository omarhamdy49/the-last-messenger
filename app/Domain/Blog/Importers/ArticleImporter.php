<?php

declare(strict_types=1);

namespace App\Domain\Blog\Importers;

use App\Domain\Shared\Importers\BaseImporter;
use App\Models\Article;
use App\Models\ArticleLocale;
use Illuminate\Support\Str;

/**
 * Article Importer
 *
 * Imports articles (Seerah/Blog posts) from CSV/Excel files.
 * Each row creates one Article record with ArticleLocale entries
 * for every language column present in the CSV.
 */
class ArticleImporter extends BaseImporter
{
    /**
     * {@inheritdoc}
     */
    protected function getValidationRules(): array
    {
        return [
            'title_ar' => 'required|string|max:500',
            'title_en' => 'required|string|max:500',
            'title_fr' => 'nullable|string|max:500',
            'title_tr' => 'nullable|string|max:500',
            'title_ur' => 'nullable|string|max:500',
            'content_ar' => 'required|string',
            'content_en' => 'required|string',
            'content_fr' => 'nullable|string',
            'content_tr' => 'nullable|string',
            'content_ur' => 'nullable|string',
            'excerpt_ar' => 'nullable|string|max:1000',
            'excerpt_en' => 'nullable|string|max:1000',
            'excerpt_fr' => 'nullable|string|max:1000',
            'excerpt_tr' => 'nullable|string|max:1000',
            'excerpt_ur' => 'nullable|string|max:1000',
            'author_email' => 'nullable|email|max:255',
            'status' => 'nullable|in:draft,published,scheduled,archived',
            'published_at' => 'nullable|date',
        ];
    }

    /**
     * {@inheritdoc}
     *
     * Maps CSV column names to the same names used in getValidationRules().
     * When a file has headers, BaseImporter uses them directly; this mapping
     * is the fallback for header-less files (positional import).
     */
    protected function getColumnMapping(): array
    {
        return [
            'title_ar' => 'title_ar',
            'title_en' => 'title_en',
            'title_fr' => 'title_fr',
            'title_tr' => 'title_tr',
            'title_ur' => 'title_ur',
            'content_ar' => 'content_ar',
            'content_en' => 'content_en',
            'content_fr' => 'content_fr',
            'content_tr' => 'content_tr',
            'content_ur' => 'content_ur',
            'excerpt_ar' => 'excerpt_ar',
            'excerpt_en' => 'excerpt_en',
            'excerpt_fr' => 'excerpt_fr',
            'excerpt_tr' => 'excerpt_tr',
            'excerpt_ur' => 'excerpt_ur',
            'author_email' => 'author_email',
            'status' => 'status',
            'published_at' => 'published_at',
        ];
    }

    /**
     * {@inheritdoc}
     *
     * Checks for an existing ArticleLocale with the same Arabic title slug
     * to avoid creating duplicate articles.
     */
    protected function isDuplicate(array $row): bool
    {
        $slug = Str::slug($row['title_ar'] ?? '');

        if ($slug === '') {
            return false;
        }

        return ArticleLocale::where('locale', 'ar')
            ->where('slug', $slug)
            ->exists();
    }

    /**
     * {@inheritdoc}
     *
     * Creates the Article header record and one ArticleLocale row per language
     * that has at least a title present in the import row.
     */
    protected function importRow(array $row): Article
    {
        $status = $row['status'] ?? 'draft';

        if ($this->options['update_existing'] ?? false) {
            $existingLocale = ArticleLocale::where('locale', 'ar')
                ->where('slug', Str::slug($row['title_ar'] ?? ''))
                ->first();

            if ($existingLocale) {
                $article = $existingLocale->article;
                $this->updateArticleLocales($article, $row);

                return $article;
            }
        }

        $article = Article::create([
            'status' => in_array($status, ['draft', 'published', 'scheduled', 'archived'], true)
                ? $status
                : 'draft',
            'scheduled_at' => $status === 'scheduled'
                ? $this->transformDate($row['published_at'] ?? null)
                : null,
        ]);

        $this->createArticleLocales($article, $row);

        return $article;
    }

    /**
     * Create ArticleLocale records for each language present in the row.
     */
    private function createArticleLocales(Article $article, array $row): void
    {
        foreach ($this->buildLocalePayloads($row) as $locale => $payload) {
            ArticleLocale::create(array_merge(['article_id' => $article->id, 'locale' => $locale], $payload));
        }
    }

    /**
     * Update existing ArticleLocale records for each language present in the row.
     */
    private function updateArticleLocales(Article $article, array $row): void
    {
        foreach ($this->buildLocalePayloads($row) as $locale => $payload) {
            ArticleLocale::updateOrCreate(
                ['article_id' => $article->id, 'locale' => $locale],
                $payload
            );
        }
    }

    /**
     * Build an array of locale => data pairs from the CSV row.
     * Only includes locales for which at least a title is provided.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, array<string, mixed>>
     */
    private function buildLocalePayloads(array $row): array
    {
        $locales = ['ar', 'en', 'fr', 'tr', 'ur'];
        $payloads = [];

        foreach ($locales as $locale) {
            $title = trim((string) ($row["title_{$locale}"] ?? ''));

            if ($title === '') {
                continue;
            }

            $content = trim((string) ($row["content_{$locale}"] ?? ''));
            $excerpt = trim((string) ($row["excerpt_{$locale}"] ?? ''));

            $payloads[$locale] = [
                'title' => $title,
                'slug' => Str::slug($title),
                'excerpt' => $excerpt !== '' ? $excerpt : null,
                'content' => $content !== '' ? ['body' => $content, 'toc' => null, 'summary' => null] : null,
                'is_ready' => $content !== '',
            ];
        }

        return $payloads;
    }
}
