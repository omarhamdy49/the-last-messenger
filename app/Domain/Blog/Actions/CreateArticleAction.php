<?php

declare(strict_types=1);

namespace App\Domain\Blog\Actions;

use App\Domain\Blog\DTOs\CreateArticleData;
use App\Domain\Blog\Services\ReadTimeCalculatorService;
use App\Domain\Blog\Services\SlugGeneratorService;
use App\Domain\Blog\Services\TocGeneratorService;
use App\Domain\Shared\Services\VersionService;
use App\Models\Article;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Create Article Action
 *
 * Encapsulates all business logic required to persist a new Article,
 * including thumbnail storage, locale creation, relationship syncing,
 * and initial version snapshotting. Runs inside a single DB transaction.
 *
 * On failure, any uploaded thumbnail is cleaned up before the exception
 * propagates so the caller can handle it at the HTTP boundary.
 */
class CreateArticleAction
{
    public function __construct(
        private readonly SlugGeneratorService $slugGenerator,
        private readonly ReadTimeCalculatorService $readTimeCalculator,
        private readonly TocGeneratorService $tocGenerator,
        private readonly VersionService $versionService,
    ) {}

    /**
     * Execute the article creation workflow.
     *
     * @throws \Throwable Propagates any database or storage exception after cleanup.
     */
    public function execute(CreateArticleData $data, array $locales): Article
    {
        $thumbPath = null;

        try {
            $thumbPath = $this->storeThumb($data);

            return DB::transaction(function () use ($data, $locales, $thumbPath): Article {
                $article = $this->createArticleRecord($data, $thumbPath);

                $this->createLocales($article, $data, $locales);
                $this->syncCategories($article, $data);
                $this->syncTags($article, $data);
                $this->syncMediaItems($article, $data);
                $this->syncSeries($article, $data);

                $this->versionService->createVersion(
                    $article,
                    $article->toArray(),
                    auth()->id(),
                    'Initial version created'
                );

                return $article;
            });
        } catch (\Throwable $e) {
            $this->cleanupThumb($thumbPath);
            throw $e;
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function storeThumb(CreateArticleData $data): ?string
    {
        if ($data->thumb === null) {
            return null;
        }

        return $data->thumb->store('articles/thumbs', 'public');
    }

    private function cleanupThumb(?string $thumbPath): void
    {
        if ($thumbPath !== null) {
            Storage::disk('public')->delete($thumbPath);
        }
    }

    private function createArticleRecord(CreateArticleData $data, ?string $thumbPath): Article
    {
        $primaryContent = $data->contents['ar'] ?? reset($data->contents) ?: '';

        return Article::create([
            'thumb'        => $thumbPath,
            'writer_id'    => $data->writerId,
            'read_time'    => $data->readTime ?? $this->readTimeCalculator->calculate($primaryContent),
            'views'        => 0,
            'is_featured'  => $data->isFeatured,
            'is_pinned'    => $data->isPinned,
            'category_id'  => $data->categoryId,
            'hijri_date'   => $data->hijriDate,
            'status'       => $data->status,
            'scheduled_at' => $data->scheduledAt,
            'statistics'   => $data->statistics,
            'references'   => $data->references,
        ]);
    }

    private function createLocales(Article $article, CreateArticleData $data, array $locales): void
    {
        foreach ($locales as $locale) {
            if (! isset($data->titles[$locale])) {
                continue;
            }

            $slug    = $data->slugs[$locale] ?? $this->slugGenerator->generate($data->titles[$locale], $locale);
            $content = $data->contents[$locale] ?? '';

            $article->locales()->create([
                'locale'       => $locale,
                'title'        => $data->titles[$locale],
                'slug'         => $slug,
                'description'  => $data->descriptions[$locale] ?? null,
                'excerpt'      => $data->excerpts[$locale] ?? null,
                'content'      => [
                    'body'    => $content,
                    'toc'     => $data->tocs[$locale] ?? $this->tocGenerator->generate($content),
                    'summary' => $data->summaries[$locale] ?? null,
                ],
                'seo_metadata' => [
                    'title'       => $data->seoTitles[$locale] ?? null,
                    'description' => $data->seoDescriptions[$locale] ?? null,
                ],
                'is_ready'     => $data->isReady[$locale] ?? false,
            ]);
        }
    }

    private function syncCategories(Article $article, CreateArticleData $data): void
    {
        if ($data->categoryId !== null) {
            $article->categories()->attach($data->categoryId);
        }

        if (! empty($data->additionalCategories)) {
            $article->categories()->syncWithoutDetaching($data->additionalCategories);
        }
    }

    private function syncTags(Article $article, CreateArticleData $data): void
    {
        if (! empty($data->tags)) {
            $article->tags()->attach($data->tags);
        }
    }

    private function syncMediaItems(Article $article, CreateArticleData $data): void
    {
        if (! empty($data->mediaItems)) {
            $article->mediaItems()->sync($data->mediaItems);
        }
    }

    private function syncSeries(Article $article, CreateArticleData $data): void
    {
        if (! empty($data->series)) {
            $syncData = [];
            foreach ($data->series as $position => $seriesId) {
                $syncData[$seriesId] = ['position' => $position];
            }
            $article->series()->sync($syncData);
        }
    }
}
