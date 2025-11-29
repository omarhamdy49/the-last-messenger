<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Handlers;

use App\Domain\Hadith\Application\DTOs\HadithDTO;
use App\Domain\Hadith\Application\Queries\ListHadithsByThemeQuery;
use App\Domain\Hadith\Repositories\HadithRepositoryInterface;
use App\Domain\Hadith\Repositories\ThemeRepositoryInterface;
use App\Domain\Shared\Application\DTOs\PaginatedResult;
use App\Domain\Shared\Application\Queries\Query;
use App\Domain\Shared\Application\Queries\QueryHandler;
use App\Domain\Shared\Exceptions\EntityNotFoundException;

/**
 * Handler for ListHadithsByThemeQuery
 *
 * @implements QueryHandler<ListHadithsByThemeQuery, PaginatedResult>
 */
final class ListHadithsByThemeHandler implements QueryHandler
{
    public function __construct(
        private readonly HadithRepositoryInterface $hadithRepository,
        private readonly ThemeRepositoryInterface $themeRepository,
    ) {}

    /**
     * Handle the query
     *
     * @param ListHadithsByThemeQuery $query
     * @return PaginatedResult
     * @throws EntityNotFoundException
     */
    public function handle(Query $query): PaginatedResult
    {
        /** @var ListHadithsByThemeQuery $query */

        // Validate theme exists
        $theme = $this->themeRepository->find($query->themeId);
        if ($theme === null) {
            throw EntityNotFoundException::forEntity('HadithTheme', $query->themeId);
        }

        // Get theme IDs to search (include sub-themes if requested)
        $themeIds = [$query->themeId];
        if ($query->includeSubThemes) {
            $childIds = $this->themeRepository->getChildrenIds($query->themeId);
            $themeIds = array_merge($themeIds, $childIds);
        }

        $filters = [
            'theme_ids' => $themeIds,
        ];

        if ($query->gradingStatus !== null) {
            $filters['grading_status'] = $query->gradingStatus;
        }

        $paginator = $this->hadithRepository->paginateWithFilters(
            perPage: $query->perPage,
            filters: $filters
        );

        $locale = $query->locale ?? 'ar';
        $items = $paginator->getCollection()->map(
            fn($hadith) => HadithDTO::fromModel($hadith, $locale)
        );

        return PaginatedResult::fromPaginator($paginator, $items->toArray());
    }
}
