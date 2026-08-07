<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Import\Sunnah;

use App\Domain\Hadith\Repositories\HadithImportRepositoryInterface;
use Closure;
use JsonException;
use RuntimeException;

final class SunnahCollectionImportService
{
    public function __construct(
        private readonly SunnahApiClient $client,
        private readonly SunnahCollectionMapper $mapper,
        private readonly HadithImportRepositoryInterface $repository,
    ) {}

    public function preview(
        SunnahCollectionDefinition $definition,
        ?Closure $progress = null,
    ): SunnahCollectionPreview {
        $collection = $this->mapper->map(
            $definition,
            $this->client->fetchCollection($definition, $progress),
        );

        return new SunnahCollectionPreview(
            snapshotHash: $this->hash($collection),
            collection: $collection,
            existing: $this->repository->collectionState($definition->slug),
        );
    }

    public function import(SunnahCollectionPreview $preview): SunnahCollectionWriteResult
    {
        return $this->repository->importCollection($preview->collection);
    }

    public function stream(
        SunnahCollectionDefinition $definition,
        bool $dryRun,
        ?Closure $progress = null,
    ): SunnahStreamingImportResult {
        $collection = $this->client->collectionData($definition->source);
        $this->mapper->assertCollection($definition, $collection);
        $bookIndex = $this->client->bookIndex($definition->source);
        $bookNumbers = [];
        $declaredHadiths = 0;
        foreach ($bookIndex as $book) {
            $number = trim((string) ($book['bookNumber'] ?? ''));
            if ($number === '' || isset($bookNumbers[$number])) {
                throw new RuntimeException("{$definition->source} has a missing or duplicate API book identity.");
            }
            $bookNumbers[$number] = true;
            $declaredHadiths += (int) ($book['numberOfHadith'] ?? 0);
        }
        if ($declaredHadiths !== $definition->expectedHadiths) {
            throw new RuntimeException(
                "{$definition->source} book-index mismatch: expected {$definition->expectedHadiths}, books declare {$declaredHadiths}."
            );
        }

        $collectionId = $dryRun ? null : $this->repository->prepareStreamingCollection(
            $definition,
            $collection,
            array_keys($bookNumbers),
        );
        $hash = hash_init('sha256');
        $seenReferences = [];
        $hadithSortOrder = 0;
        $chapters = 0;
        $missingArabic = 0;
        $ungradedHadiths = 0;
        $unmappedGrades = 0;
        $created = 0;
        $updated = 0;

        foreach ($bookIndex as $index => $bookPayload) {
            $sourceBook = $this->client->fetchBook($definition->source, $definition->hasChapters, $bookPayload);
            $mapped = $this->mapper->mapBook(
                $definition,
                $sourceBook,
                $index + 1,
                $hadithSortOrder,
                $seenReferences,
            );
            try {
                hash_update($hash, json_encode($mapped->book, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
            } catch (JsonException $exception) {
                throw new RuntimeException('Could not hash a streamed Sunnah API book.', 0, $exception);
            }

            $chapters += count($mapped->book->chapters);
            $missingArabic += $mapped->missingArabic;
            $ungradedHadiths += $mapped->ungradedHadiths;
            $unmappedGrades += $mapped->unmappedGrades;
            if (! $dryRun) {
                $write = $this->repository->importStreamingBook(
                    (int) $collectionId,
                    $definition->slug,
                    $mapped->book,
                );
                $created += $write['created'];
                $updated += $write['updated'];
            }
            $progress?->__invoke(
                $definition->source,
                $mapped->book->number,
                $index + 1,
                count($bookIndex),
                count($mapped->book->hadiths),
            );
            unset($sourceBook, $mapped);
        }

        if ($hadithSortOrder !== $definition->expectedHadiths) {
            throw new RuntimeException(
                "{$definition->source} streamed-count mismatch: expected {$definition->expectedHadiths}, fetched {$hadithSortOrder}."
            );
        }

        $stored = $dryRun ? null : $this->repository->finishStreamingCollection(
            (int) $collectionId,
            $definition->slug,
            count($bookIndex),
            $definition->expectedHadiths,
        );

        return new SunnahStreamingImportResult(
            slug: $definition->slug,
            snapshotHash: hash_final($hash),
            books: count($bookIndex),
            chapters: $chapters,
            hadiths: $hadithSortOrder,
            publishedHadiths: $stored?->publishedHadiths ?? ($hadithSortOrder - $missingArabic),
            createdHadiths: $created,
            updatedHadiths: $updated,
            missingArabic: $missingArabic,
            ungradedHadiths: $ungradedHadiths,
            unmappedGrades: $unmappedGrades,
            dryRun: $dryRun,
        );
    }

    private function hash(MappedSunnahCollection $collection): string
    {
        try {
            return hash('sha256', json_encode($collection, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        } catch (JsonException $exception) {
            throw new RuntimeException('Could not hash the Sunnah API collection snapshot.', 0, $exception);
        }
    }
}
