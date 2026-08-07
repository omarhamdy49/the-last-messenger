<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Hadith\Import\Sunnah\FortyCollectionWriteResult;
use App\Domain\Hadith\Import\Sunnah\FortyImportPreview;
use App\Domain\Hadith\Import\Sunnah\MappedFortyCollection;
use App\Domain\Hadith\Import\Sunnah\SunnahCollectionCatalog;
use App\Domain\Hadith\Import\Sunnah\SunnahCollectionDefinition;
use App\Domain\Hadith\Import\Sunnah\SunnahCollectionImportService;
use App\Domain\Hadith\Import\Sunnah\SunnahFortyImportService;
use App\Domain\Hadith\Import\Sunnah\SunnahStreamingImportResult;
use Illuminate\Console\Command;
use Throwable;

final class ImportSunnahHadithCommand extends Command
{
    protected $signature = 'islamic-data:import-hadith
                            {--collection=forty : Sunnah API source collection or all}
                            {--dry-run : Fetch, validate, and preview without writing}
                            {--force : Import without an interactive confirmation}';

    protected $description = 'Preview or import Hadith collections from a validated Sunnah API snapshot';

    public function handle(
        SunnahFortyImportService $fortyService,
        SunnahCollectionImportService $collectionService,
        SunnahCollectionCatalog $catalog,
    ): int {
        $requested = mb_strtolower(trim((string) $this->option('collection')), 'UTF-8');
        try {
            if ($requested === 'forty') {
                return $this->handleForty($fortyService);
            }
            if (isset($catalog->unavailable()[$requested])) {
                $this->error("{$requested} cannot be imported completely: {$catalog->unavailable()[$requested]}");

                return self::FAILURE;
            }
            if ($requested === 'all') {
                return $this->handleAll($collectionService, $fortyService, $catalog);
            }

            $definition = $catalog->find($requested);
            if (! $definition instanceof SunnahCollectionDefinition) {
                $this->error("Unknown or unsupported Sunnah API collection: {$requested}");

                return self::FAILURE;
            }

            return $this->handleCollection($collectionService, $definition);
        } catch (Throwable $exception) {
            report($exception);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    private function handleAll(
        SunnahCollectionImportService $service,
        SunnahFortyImportService $fortyService,
        SunnahCollectionCatalog $catalog,
    ): int {
        $this->warn('Five configured collections are excluded because the Sunnah API inventory is incomplete:');
        $this->table(['Collection', 'Reason'], array_map(
            static fn (string $reason, string $slug): array => [$slug, $reason],
            $catalog->unavailable(),
            array_keys($catalog->unavailable()),
        ));

        if (! $this->option('dry-run') && ! $this->option('force') && ! $this->confirm(
            'Fetch, validate, and import every API-complete collection?'
        )) {
            $this->info('Cancelled.');

            return self::SUCCESS;
        }

        $results = [];
        foreach ($catalog->importable() as $definition) {
            $this->newLine();
            $this->info("Streaming and validating Sunnah API collection: {$definition->source}");
            $results[] = $service->stream(
                $definition,
                (bool) $this->option('dry-run'),
                $this->progressReporter(),
            );
            $this->displayStreamingResults([end($results)]);
            if (! $this->option('dry-run')) {
                $this->info("Imported and published {$definition->slug}.");
            }
        }

        $this->info('Fetching and validating Sunnah API collection: forty');
        $forty = $fortyService->preview();
        $this->displayPreview($forty);
        if ($this->option('dry-run')) {
            $this->warn('Dry run complete — nothing written.');

            return self::SUCCESS;
        }

        $fortyResult = $fortyService->import($forty);
        $this->newLine();
        $this->info('All API-complete imports finished. Imported collections and publishable rows are published.');
        $this->displayStreamingResults($results);
        $this->table(['Forty collection', 'Created', 'Updated', 'Stored'], array_map(
            static fn (FortyCollectionWriteResult $row): array => [
                $row->slug, $row->created, $row->updated, $row->total,
            ],
            $fortyResult->collections,
        ));

        return self::SUCCESS;
    }

    private function handleCollection(
        SunnahCollectionImportService $service,
        SunnahCollectionDefinition $definition,
    ): int {
        if (! $this->option('dry-run') && ! $this->option('force') && ! $this->confirm(
            "Stream, validate, and import {$definition->source}? The collection remains hidden until parity succeeds."
        )) {
            $this->info('Cancelled.');

            return self::SUCCESS;
        }

        $this->info("Streaming and validating Sunnah API collection: {$definition->source}");
        $result = $service->stream(
            $definition,
            (bool) $this->option('dry-run'),
            $this->progressReporter(),
        );
        $this->displayStreamingResults([$result]);
        if ($result->dryRun) {
            $this->warn('Dry run — nothing written.');
        } else {
            $this->info('Import completed; the collection and all publishable rows are published.');
        }

        return self::SUCCESS;
    }

    private function handleForty(SunnahFortyImportService $service): int
    {
        $this->info('Fetching and validating Sunnah API collection: forty');
        $preview = $service->preview();
        $this->displayPreview($preview);
        if ($this->option('dry-run')) {
            $this->warn('Dry run — nothing written.');

            return self::SUCCESS;
        }
        if (! $this->option('force') && ! $this->confirm(
            'Import this exact snapshot? Confirmation hash: '.substr($preview->snapshotHash, 0, 12)
        )) {
            $this->info('Cancelled.');

            return self::SUCCESS;
        }

        $result = $service->import($preview);
        $this->newLine();
        $this->info('Import completed; collections and imported rows are published.');
        $this->table(['Collection', 'Created', 'Updated', 'Stored'], array_map(
            static fn (FortyCollectionWriteResult $row): array => [
                $row->slug, $row->created, $row->updated, $row->total,
            ],
            $result->collections,
        ));

        return self::SUCCESS;
    }

    /** @return \Closure(string, string, int, int, int): void */
    private function progressReporter(): \Closure
    {
        return fn (string $collection, string $book, int $current, int $total, int $hadiths) => $this->line(
            "  [{$current}/{$total}] {$collection} book {$book}: {$hadiths} Hadiths"
        );
    }

    /** @param list<SunnahStreamingImportResult> $results */
    private function displayStreamingResults(array $results): void
    {
        $this->table(
            ['Collection', 'Books', 'Chapters', 'Created', 'Updated', 'Stored', 'Published', 'Missing Arabic', 'No grade', 'Unmapped grades', 'SHA-256'],
            array_map(static fn (SunnahStreamingImportResult $result): array => [
                $result->slug,
                $result->books,
                $result->chapters,
                $result->createdHadiths,
                $result->updatedHadiths,
                $result->hadiths,
                $result->publishedHadiths,
                $result->missingArabic,
                $result->ungradedHadiths,
                $result->unmappedGrades,
                $result->snapshotHash,
            ], $results),
        );
    }

    private function displayPreview(FortyImportPreview $preview): void
    {
        $this->line('Snapshot SHA-256: '.$preview->snapshotHash);
        $this->line('Source rows: '.$preview->totalHadiths());
        $this->table(
            ['Source book', 'Target collection', 'Expected', 'Fetched', 'Existing', 'Missing Arabic', 'No source grade', 'Unmapped grades'],
            array_map(
                fn (MappedFortyCollection $collection): array => [
                    $collection->sourceBookNumber,
                    $collection->slug,
                    $collection->expectedHadiths,
                    count($collection->hadiths),
                    $preview->existing->countFor($collection->slug),
                    $collection->missingArabic,
                    $collection->ungradedHadiths,
                    $collection->unmappedGrades,
                ],
                $preview->collections,
            ),
        );
    }
}
