<?php

declare(strict_types=1);

namespace App\Domain\Shared\Importers;

use App\Domain\Blog\Models\Article;
use App\Domain\Blog\Models\Category;
use App\Domain\Blog\Models\Tag;
use App\Domain\Blog\Enums\ArticleStatus;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Article Importer
 *
 * Imports blog articles from CSV/Excel files
 */
class ArticleImporter extends BaseImporter
{
    /**
     * {@inheritdoc}
     */
    protected function getValidationRules(): array
    {
        return [
            'title_ar' => 'required|string|max:255',
            'title_en' => 'required|string|max:255',
            'title_fr' => 'nullable|string|max:255',
            'title_tr' => 'nullable|string|max:255',
            'title_ur' => 'nullable|string|max:255',
            'content_ar' => 'required|string',
            'content_en' => 'required|string',
            'content_fr' => 'nullable|string',
            'content_tr' => 'nullable|string',
            'content_ur' => 'nullable|string',
            'excerpt_ar' => 'nullable|string',
            'excerpt_en' => 'nullable|string',
            'excerpt_fr' => 'nullable|string',
            'excerpt_tr' => 'nullable|string',
            'excerpt_ur' => 'nullable|string',
            'category_name' => 'nullable|string|max:255',
            'tags' => 'nullable|string',
            'author_email' => 'required|email',
            'status' => 'nullable|in:draft,published,archived',
            'published_at' => 'nullable|date',
        ];
    }

    /**
     * {@inheritdoc}
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
            'category_name' => 'category_name',
            'tags' => 'tags',
            'author_email' => 'author_email',
            'status' => 'status',
            'published_at' => 'published_at',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function isDuplicate(array $row): bool
    {
        $slug = Str::slug($row['title_en'] ?? $row['title_ar']);

        return Article::where('slug', $slug)->exists();
    }

    /**
     * {@inheritdoc}
     */
    protected function importRow(array $row): Article
    {
        // Find or create author
        $author = User::where('email', $row['author_email'])->first();
        if (! $author) {
            $this->addError('author_email', "User with email {$row['author_email']} not found", 'error');
            throw new \Exception("Author not found: {$row['author_email']}");
        }

        // Find or create category
        $categoryId = null;
        if (! empty($row['category_name'])) {
            $category = Category::where('name->en', $row['category_name'])
                ->orWhere('name->ar', $row['category_name'])
                ->first();

            if (! $category) {
                $category = Category::create([
                    'name' => [
                        'ar' => $row['category_name'],
                        'en' => $row['category_name'],
                        'fr' => $row['category_name'],
                        'tr' => $row['category_name'],
                        'ur' => $row['category_name'],
                    ],
                    'slug' => Str::slug($row['category_name']),
                ]);
            }

            $categoryId = $category->id;
        }

        // Build multi-language fields
        $title = $this->buildMultiLanguageField($row, 'title');
        $content = $this->buildMultiLanguageField($row, 'content');
        $excerpt = $this->buildMultiLanguageField($row, 'excerpt');

        // Create article
        $article = Article::create([
            'user_id' => $author->id,
            'category_id' => $categoryId,
            'title' => $title,
            'content' => $content,
            'excerpt' => $excerpt,
            'slug' => Str::slug($title['en'] ?: $title['ar']),
            'status' => ArticleStatus::tryFrom($row['status'] ?? 'draft') ?? ArticleStatus::DRAFT,
            'published_at' => $this->transformDate($row['published_at']),
            'reading_time_minutes' => $this->calculateReadingTime($content['en'] ?: $content['ar']),
        ]);

        // Attach tags
        if (! empty($row['tags'])) {
            $tagNames = array_map('trim', explode(',', $row['tags']));
            $tagIds = [];

            foreach ($tagNames as $tagName) {
                $tag = Tag::firstOrCreate(
                    ['slug' => Str::slug($tagName)],
                    [
                        'name' => [
                            'ar' => $tagName,
                            'en' => $tagName,
                            'fr' => $tagName,
                            'tr' => $tagName,
                            'ur' => $tagName,
                        ],
                    ]
                );

                $tagIds[] = $tag->id;
            }

            $article->tags()->attach($tagIds);
        }

        return $article;
    }

    /**
     * Calculate reading time in minutes
     */
    private function calculateReadingTime(string $content): int
    {
        $wordCount = str_word_count(strip_tags($content));
        $minutes = ceil($wordCount / 200); // 200 words per minute

        return max(1, $minutes);
    }
}
