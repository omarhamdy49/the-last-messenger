<?php

declare(strict_types=1);

namespace App\Domain\Blog\Actions;

use App\Domain\Blog\DTOs\UpdateArticleData;
use App\Domain\Blog\Services\SlugGeneratorService;
use App\Domain\Blog\Services\TocGeneratorService;
use App\Domain\Shared\Services\VersionService;
use App\Models\Article;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Update Article Action
 *
 * Encapsulates all business logic required to update an existing Article,
 * including optional thumbnail replacement, locale upserts, relationship
 * re-syncing, and version snapshotting. Runs inside a single DB transaction.
 */
class UpdateArticleAction
{
    public function __construct(
        private readonly SlugGeneratorService $slugGenerator,
        private readonly TocGeneratorService $tocGenerator,
        private readonly VersionService $versionService,
    ) {}

    /**
     * Execute the article update workflow.
     *
     * @throws \Throwable Propagates any database or storage exception.
     */
    public function execute(Article $article, UpdateArticleData $data, array $locales): Article
    {
        return DB::transaction(function () use ($article, $data, $locales): Article {
            $thumbPath = $this->handleThumbReplacement($article, $data);

            $this->updateArticleRecord($article, $data, $thumbPath);
            $this->upsertLocales($article, $data, $locales);
            $this->syncCategories($article, $data);
            $this->syncTags($article, $data);
            $this->syncMediaItems($article, $data);
            $this->syncSeries($article, $data);

            $this->versionService->createVersion(
                $article,
                $article->toArray(),
                auth()->id(),
                'Article updated'
            );

            return $article;
        });
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Upload a new thumbnail if provided, delete the old one, and return the path.
     * Returns null when no new file was provided (caller keeps the existing path).
     */
    private function handleThumbReplacement(Article $article, UpdateArticleData $data): ?string
    {
        if ($data->thumb === null) {
            return null;
        }

        if ($article->thumb) {
            Storage::disk('public')->delete($article->thumb);
        }

        return $data->thumb->store('articles/thumbs', 'public');
    }

    private function updateArticleRecord(Article $article, UpdateArticleData $data, ?string $newThumbPath): void
    {
        $article->update([
            'thumb' => $newThumbPath ?? $article->thumb,
            'writer_id' => $data->writerId,
            'read_time' => $data->readTime,
            'is_featured' => $data->isFeatured,
            'is_pinned' => $data->isPinned,
            'category_id' => $data->categoryId,
            'hijri_date' => $data->hijriDate,
            'status' => $data->status,
            'scheduled_at' => $data->scheduledAt,
            // Stamp published_at on first publish; preserve the original date afterwards.
            'published_at' => $data->status === 'published'
                ? ($article->published_at ?? now())
                : ($data->status === 'scheduled' ? null : $article->published_at),
            'statistics' => $data->statistics,
            'references' => $data->references,
        ]);
    }

    private function upsertLocales(Article $article, UpdateArticleData $data, array $locales): void
    {
        foreach ($locales as $locale) {
            if (! isset($data->titles[$locale])) {
                continue;
            }

            $slug = $data->slugs[$locale]
                ?? $this->slugGenerator->generate($data->titles[$locale], $locale, $article->id);
            $content = $data->contents[$locale] ?? '';

            $article->locales()->updateOrCreate(
                ['locale' => $locale],
                [
                    'title' => $data->titles[$locale],
                    'slug' => $slug,
                    'description' => $data->descriptions[$locale] ?? null,
                    'excerpt' => $data->excerpts[$locale] ?? null,
                    'content' => [
                        'body' => $content,
                        'toc' => $data->tocs[$locale] ?? $this->tocGenerator->generate($content),
                        'summary' => $data->summaries[$locale] ?? null,
                    ],
                    'seo_metadata' => [
                        'title' => $data->seoTitles[$locale] ?? null,
                        'description' => $data->seoDescriptions[$locale] ?? null,
                    ],
                    'is_ready' => $data->isReady[$locale] ?? false,
                ]
            );
        }
    }

    private function syncCategories(Article $article, UpdateArticleData $data): void
    {
        $categoryIds = array_filter(array_merge(
            [$data->categoryId],
            $data->additionalCategories ?? []
        ));

        $article->categories()->sync($categoryIds);
    }

    private function syncTags(Article $article, UpdateArticleData $data): void
    {
        $article->tags()->sync($data->tags ?? []);
    }

    private function syncMediaItems(Article $article, UpdateArticleData $data): void
    {
        if ($data->mediaItems !== null) {
            $article->mediaItems()->sync($data->mediaItems);
        }
    }

    private function syncSeries(Article $article, UpdateArticleData $data): void
    {
        if ($data->series !== null) {
            $syncData = [];
            foreach ($data->series as $position => $seriesId) {
                $syncData[$seriesId] = ['position' => $position];
            }
            $article->series()->sync($syncData);
        }
    }
}
