<?php

declare(strict_types=1);

namespace App\Domain\Blog\Actions;

use App\Domain\Blog\DTOs\UpdateArticleData;
use App\Domain\Blog\Services\ArticleThumbService;
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
        private readonly ArticleThumbService $thumbService,
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
            $this->syncGallery($article, $data);
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

        // Seeded/duplicated articles can share one thumb file; only delete it
        // when no other article still points at it.
        if ($article->thumb && ! Article::where('id', '!=', $article->id)->where('thumb', $article->thumb)->exists()) {
            $this->thumbService->delete($article->thumb);
        }

        return $this->thumbService->store($data->thumb);
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

        // Video queue: the submitted order is the play order, so the array
        // index becomes the pivot's `position`.
        if ($data->episodes !== null) {
            $article->episodes()->sync(
                collect($data->episodes)
                    ->values()
                    ->mapWithKeys(fn (int $id, int $i) => [$id => ['position' => $i]])
                    ->all()
            );
        }
    }

    /**
     * Gallery is replace-on-submit: the wizard always posts the full, ordered
     * list. A null galleryFiles means the tab was never touched, so the existing
     * gallery is left alone. Rows the editor removed have their stored copies
     * deleted so the public disk does not accumulate orphans.
     */
    private function syncGallery(Article $article, UpdateArticleData $data): void
    {
        if ($data->galleryFiles === null) {
            return;
        }

        $keptMediaIds = array_column($data->galleryFiles, 'media_id');
        $existing = $article->gallery()->get();
        $deletedMediaIds = [];

        foreach ($existing as $item) {
            if ($item->media_id !== null && in_array($item->media_id, $keptMediaIds, true)) {
                continue;
            }

            if ($item->image) {
                Storage::disk('public')->delete($item->image);
            }

            $deletedMediaIds[] = $item->media_id;
            $item->delete();
        }

        $existingByMediaId = $existing
            ->reject(fn ($item) => in_array($item->media_id, $deletedMediaIds, true))
            ->keyBy('media_id');

        foreach ($data->galleryFiles as $order => $entry) {
            $current = $existingByMediaId->get($entry['media_id']);

            if ($current !== null) {
                $current->update(['order' => $order]);

                continue;
            }

            $path = $entry['file']->store('articles/gallery', 'public');

            if ($path === false) {
                continue;
            }

            $article->gallery()->create([
                'media_id' => $entry['media_id'],
                'image' => $path,
                'order' => $order,
            ]);
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
