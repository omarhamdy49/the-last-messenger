<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Handlers;

use App\Domain\Quran\Application\DTOs\VerseDTO;
use App\Domain\Quran\Application\Queries\ListVersesByPageQuery;
use App\Domain\Quran\Repositories\VerseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Handler for listing verses by Quran page
 */
final readonly class ListVersesByPageHandler
{
    public function __construct(
        private VerseRepositoryInterface $verseRepository,
    ) {}

    /**
     * @return Collection<int, VerseDTO>
     */
    public function handle(ListVersesByPageQuery $query): Collection
    {
        $verses = $this->verseRepository->getByPage($query->page);

        if ($query->withTranslations) {
            $verses->load('translations');
        }

        return $verses->map(
            fn ($verse) => VerseDTO::fromModel($verse, $query->locale)
        );
    }
}
