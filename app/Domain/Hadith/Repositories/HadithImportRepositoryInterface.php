<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Repositories;

use App\Domain\Hadith\Import\Sunnah\FortyCollectionWriteResult;
use App\Domain\Hadith\Import\Sunnah\FortyImportState;
use App\Domain\Hadith\Import\Sunnah\MappedFortyCollection;
use App\Domain\Hadith\Import\Sunnah\MappedSunnahBook;
use App\Domain\Hadith\Import\Sunnah\MappedSunnahCollection;
use App\Domain\Hadith\Import\Sunnah\SunnahCollectionData;
use App\Domain\Hadith\Import\Sunnah\SunnahCollectionDefinition;
use App\Domain\Hadith\Import\Sunnah\SunnahCollectionImportState;
use App\Domain\Hadith\Import\Sunnah\SunnahCollectionWriteResult;

interface HadithImportRepositoryInterface
{
    public function fortyState(): FortyImportState;

    public function importFortyCollection(MappedFortyCollection $collection): FortyCollectionWriteResult;

    public function collectionState(string $slug): SunnahCollectionImportState;

    public function importCollection(MappedSunnahCollection $collection): SunnahCollectionWriteResult;

    /** @param list<string> $bookNumbers */
    public function prepareStreamingCollection(
        SunnahCollectionDefinition $definition,
        SunnahCollectionData $collection,
        array $bookNumbers,
    ): int;

    /** @return array{created: int, updated: int} */
    public function importStreamingBook(int $collectionId, string $slug, MappedSunnahBook $book): array;

    public function finishStreamingCollection(
        int $collectionId,
        string $slug,
        int $expectedBooks,
        int $expectedHadiths,
    ): SunnahCollectionWriteResult;
}
