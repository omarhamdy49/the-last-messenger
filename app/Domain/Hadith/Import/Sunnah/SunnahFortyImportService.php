<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Import\Sunnah;

use App\Domain\Hadith\Repositories\HadithImportRepositoryInterface;
use JsonException;

final class SunnahFortyImportService
{
    public function __construct(
        private readonly SunnahApiClient $client,
        private readonly SunnahFortyMapper $mapper,
        private readonly HadithImportRepositoryInterface $repository,
    ) {}

    public function preview(): FortyImportPreview
    {
        $collections = $this->mapper->map($this->client->fetchFortyBooks());

        return new FortyImportPreview(
            snapshotHash: $this->hash($collections),
            collections: $collections,
            existing: $this->repository->fortyState(),
        );
    }

    public function import(FortyImportPreview $preview): FortyImportResult
    {
        $results = [];
        foreach ($preview->collections as $collection) {
            $results[] = $this->repository->importFortyCollection($collection);
        }

        return new FortyImportResult($results);
    }

    /** @param list<MappedFortyCollection> $collections */
    private function hash(array $collections): string
    {
        try {
            return hash('sha256', json_encode($collections, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        } catch (JsonException $exception) {
            throw new \RuntimeException('Could not hash the Sunnah API snapshot.', 0, $exception);
        }
    }
}
