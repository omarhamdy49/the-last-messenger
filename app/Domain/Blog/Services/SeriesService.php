<?php

declare(strict_types=1);

namespace App\Domain\Blog\Services;

use App\Domain\Blog\DTOs\CreateSeriesData;
use App\Domain\Blog\DTOs\UpdateSeriesData;
use App\Domain\Blog\Models\Series;
use App\Domain\Blog\Repositories\SeriesRepositoryInterface;
use App\Domain\Shared\Services\BaseService;
use App\Domain\Shared\Services\PublicImageService;
use App\Domain\Shared\Services\SlugGenerator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Series Service
 * Handles business logic for series management
 */
class SeriesService extends BaseService
{
    /**
     * Column sets used for eager-loading in admin views.
     * Only load columns that the Blade views actually access, keeping
     * SELECT payloads narrow and preventing accidental over-fetching.
     */
    private const ARTICLES_COLUMNS = 'id,thumb,writer_id,read_time,views,is_featured,is_pinned,category_id,created_at';

    private const TRANSLATIONS_COLUMNS = 'id,series_id,locale,title,description,is_ready';

    /**
     * Constructor
     */
    public function __construct(
        SeriesRepositoryInterface $repository,
        private readonly PublicImageService $images,
        private readonly SlugGenerator $slugs = new SlugGenerator,
    ) {
        $this->repository = $repository;
    }

    /**
     * Get paginated series with search and filters
     *
     * @param  int  $perPage  Number of items per page
     * @param  string  $search  Search query
     * @param  array<string, mixed>  $filters  Additional filters
     * @param  array<string>  $with  Relations to eager load
     */
    public function paginateWithSearch(
        int $perPage = 50,
        string $search = '',
        array $filters = [],
        array $with = []
    ): LengthAwarePaginator {
        return $this->repository->paginateWithSearch($perPage, $search, $filters, $with);
    }

    /**
     * Get paginated series for the admin index page.
     *
     * Passes column-constrained eager-load strings so that no `translations`
     * or `articles` SELECT fetches every column.
     */
    public function paginateAdmin(int $perPage = 50, string $search = ''): LengthAwarePaginator
    {
        return $this->repository->paginateWithSearch(
            perPage: $perPage,
            search: $search,
            filters: [],
            with: [
                'articles:'.self::ARTICLES_COLUMNS,
                'translations:'.self::TRANSLATIONS_COLUMNS,
            ]
        );
    }

    /**
     * Find a series by ID with the column-constrained relations needed for
     * admin show/edit views.
     *
     *
     * @throws ModelNotFoundException
     */
    public function findForAdmin(int $id): Series
    {
        return $this->repository->findOrFail($id, ['*'], [
            'articles:'.self::ARTICLES_COLUMNS,
            'translations:'.self::TRANSLATIONS_COLUMNS,
        ]);
    }

    /**
     * Create a new series inside a transaction.
     *
     * Handles thumbnail storage, slug generation, translation sync, and
     * article pivot sync. All steps run atomically; if any fail the
     * thumbnail file is rolled back too.
     */
    public function storeSeries(CreateSeriesData $data): Series
    {
        // Store the file before opening the transaction so that filesystem
        // errors are surfaced before any DB writes occur.
        $thumbnailPath = $this->images->store(
            $data->thumbnail ?? $data->thumbnailId,
            'series/thumbnails',
            'series'
        );

        try {
            $series = DB::transaction(function () use ($data, $thumbnailPath): Series {
                // Resolve slug. Not disambiguated: `series.slug` has never had a
                // `-N` suffix applied and adding one would change existing output.
                $slug = ! empty($data->slug)
                    ? $data->slug
                    : $this->slugs->fromLocalized($data->title, 'series');

                // Create the non-translatable row
                /** @var Series $series */
                $series = Series::create([
                    'slug' => $slug,
                    'thumbnail' => $thumbnailPath,
                    'total_articles' => 0,
                ]);

                // Sync translations
                $series->syncTranslations([
                    'title' => $data->title,
                    'description' => $data->description,
                ]);

                // Sync article pivot with positions
                if (! empty($data->articleIds)) {
                    $syncData = [];
                    foreach ($data->articleIds as $position => $articleId) {
                        $syncData[$articleId] = ['position' => $position];
                    }
                    $series->articles()->sync($syncData);
                    $series->updateArticlesCount();
                }

                return $series;
            });
        } catch (\Throwable $e) {
            // Roll back uploaded file if the transaction failed
            if ($thumbnailPath !== null && Storage::disk('public')->exists($thumbnailPath)) {
                $this->images->delete($thumbnailPath);
            }

            throw $e;
        }

        $this->repository->clearCache();

        return $series->load([
            'translations:'.self::TRANSLATIONS_COLUMNS,
        ]);
    }

    /**
     * Update an existing series inside a transaction.
     *
     * Handles thumbnail replacement, slug regeneration when title changes,
     * translation sync, and article pivot sync. Returns the refreshed model.
     *
     *
     * @throws ModelNotFoundException
     */
    public function updateSeries(int $id, UpdateSeriesData $data): Series
    {
        // Load before the transaction so a missing record throws immediately.
        /** @var Series $series */
        $series = Series::with('translations')->findOrFail($id);
        $oldThumbnail = $series->thumbnail;

        $newThumbnailPath = $this->images->store(
            $data->thumbnail ?? $data->thumbnailId,
            'series/thumbnails',
            'series'
        );

        try {
            DB::transaction(function () use ($series, $data, $newThumbnailPath): void {
                $nonTranslatable = [];

                // Replace thumbnail path if a new file was uploaded
                if ($newThumbnailPath !== null) {
                    $nonTranslatable['thumbnail'] = $newThumbnailPath;
                }

                // Regenerate slug when the active-locale title changes.
                // Deliberately NOT SlugGenerator::fromLocalized(): the update
                // path prefers the *request* locale over English and falls back
                // to whatever title was supplied first, which is a different
                // preference order from every other slug call site.
                if (! empty($data->title)) {
                    $locale = app()->getLocale();
                    $currentTitle = $series->getTranslation('title', $locale);
                    $newTitle = $data->title[$locale]
                        ?? $data->title['en']
                        ?? reset($data->title);

                    if ($currentTitle !== $newTitle) {
                        $nonTranslatable['slug'] = Str::slug($newTitle);
                    }
                }

                if (! empty($nonTranslatable)) {
                    $series->update($nonTranslatable);
                }

                // Sync translations
                if (! empty($data->title) || ! empty($data->description)) {
                    $series->syncTranslations([
                        'title' => $data->title,
                        'description' => $data->description,
                    ]);
                }

                // Sync article pivot (always — an empty list means detach all)
                $syncData = [];
                foreach (($data->articleIds ?? []) as $position => $articleId) {
                    $syncData[$articleId] = ['position' => $position];
                }
                $series->articles()->sync($syncData);
                $series->updateArticlesCount();
            });
        } catch (\Throwable $e) {
            // Roll back newly uploaded file on failure
            if ($newThumbnailPath !== null && Storage::disk('public')->exists($newThumbnailPath)) {
                $this->images->delete($newThumbnailPath);
            }

            throw $e;
        }

        // Delete old thumbnail only after the transaction committed successfully
        if ($newThumbnailPath !== null && $oldThumbnail !== null
            && Storage::disk('public')->exists($oldThumbnail)
        ) {
            $this->images->delete($oldThumbnail);
        }

        $this->repository->clearCache();

        return $series->fresh([
            'translations:'.self::TRANSLATIONS_COLUMNS,
        ]);
    }

    /**
     * Delete a series, detaching its articles and removing its thumbnail.
     *
     * Wraps the pivot detach + model delete in a transaction so partial
     * failures don't leave orphaned pivot rows.
     *
     *
     * @throws ModelNotFoundException
     */
    public function destroySeries(int $id): bool
    {
        /** @var Series $series */
        $series = Series::findOrFail($id);
        $thumbnail = $series->thumbnail;

        $deleted = DB::transaction(function () use ($series): bool {
            $series->articles()->detach();

            return (bool) $series->delete();
        });

        if ($deleted && $thumbnail !== null && Storage::disk('public')->exists($thumbnail)) {
            $this->images->delete($thumbnail);
        }

        $this->repository->clearCache();

        return $deleted;
    }

    /**
     * Bulk-delete series, cleaning up thumbnails and pivot rows for each one.
     *
     * Each series is deleted in its own try/catch so one failure does not
     * abort the entire batch (mirrors the original controller behaviour).
     *
     * @param  array<int>  $ids
     * @return int Number of successfully deleted series
     */
    public function bulkDestroy(array $ids): int
    {
        $deleted = 0;

        foreach ($ids as $id) {
            try {
                /** @var Series|null $series */
                $series = Series::find($id);
                if ($series === null) {
                    continue;
                }

                $thumbnail = $series->thumbnail;

                DB::transaction(function () use ($series): void {
                    $series->articles()->detach();
                    $series->delete();
                });

                if ($thumbnail !== null && Storage::disk('public')->exists($thumbnail)) {
                    $this->images->delete($thumbnail);
                }

                $deleted++;
            } catch (\Throwable) {
                // Log at call-site; keep processing remaining IDs
                continue;
            }
        }

        $this->repository->clearCache();

        return $deleted;
    }

    /**
     * Sync the series' article set: the given list becomes the full membership
     * (assign + unassign) with positions taken from the payload.
     *
     * @param  array<int, array{id: int, position: int}>  $articles
     *
     * @throws ModelNotFoundException
     */
    public function reorderArticles(int $seriesId, array $articles): void
    {
        /** @var Series $series */
        $series = Series::findOrFail($seriesId);

        DB::transaction(function () use ($series, $articles): void {
            $syncData = [];
            foreach ($articles as $item) {
                $syncData[$item['id']] = ['position' => $item['position']];
            }
            $series->articles()->sync($syncData);
            $series->updateArticlesCount();
        });

        $this->repository->clearCache();
    }

    /**
     * Get aggregate stats for the series index dashboard card.
     *
     * @return array{total: int, total_articles_in_series: int, max_articles: int}
     */
    public function getStats(): array
    {
        return $this->repository->getStats();
    }

    /**
     * Create new series
     *
     *
     * @deprecated  Use storeSeries() which handles thumbnails, translations, and pivot sync.
     */
    public function createSeries(CreateSeriesData $data): Series
    {
        return $this->storeSeries($data);
    }

    /**
     * Update series by ID
     *
     *
     * @throws ModelNotFoundException
     *
     * @deprecated  Use updateSeries() which handles thumbnails, translations, and pivot sync.
     */
    public function updateSeriesById(int $id, UpdateSeriesData $data): Series
    {
        return $this->updateSeries($id, $data);
    }

    /**
     * Delete series by ID
     *
     *
     * @throws ModelNotFoundException
     */
    public function deleteSeries(int $id): bool
    {
        return $this->destroySeries($id);
    }

    /**
     * Bulk delete series
     *
     * @param  array<int>  $ids  Series IDs to delete
     * @return int Number of deleted records
     */
    public function bulkDelete(array $ids): int
    {
        return $this->bulkDestroy($ids);
    }

    /**
     * Find series by ID with articles
     *
     *
     * @throws ModelNotFoundException
     */
    public function findById(int $id): Series
    {
        return $this->repository->findOrFail($id);
    }

    /**
     * Get total series count
     */
    public function getTotalCount(): int
    {
        return $this->repository->count();
    }

    /**
     * Count series by status
     */
    public function countByStatus(string $status): int
    {
        return $this->repository->countByStatus($status);
    }

    /**
     * Get series with all articles in order
     *
     *
     * @throws ModelNotFoundException
     */
    public function getWithArticles(int $id): Series
    {
        return $this->repository->getWithArticles($id);
    }

    /**
     * Recalculate the cached total_articles value from the article_series pivot.
     *
     *
     * @throws ModelNotFoundException
     */
    public function updateArticleCount(int $id): void
    {
        /** @var Series $series */
        $series = Series::findOrFail($id);
        $series->updateArticlesCount();

        $this->repository->clearCache();
    }

    /**
     * Search series across all languages
     */
    public function search(string $query, int $perPage = 50): LengthAwarePaginator
    {
        return $this->repository->search($query, $perPage);
    }
}
