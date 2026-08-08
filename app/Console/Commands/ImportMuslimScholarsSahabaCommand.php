<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\People\Import\MuslimScholarsSahabaImportService;
use App\Domain\People\Import\MuslimScholarsSahabaPreview;
use Illuminate\Console\Command;
use Throwable;

final class ImportMuslimScholarsSahabaCommand extends Command
{
    protected $signature = 'people:import-muslimscholars-sahaba
                            {--path=storage/app/sahaba-import-preview/muslimscholars-sahaba-mapped-preview.csv : Reviewed mapped CSV}
                            {--expected=7806 : Required total source record count}
                            {--dry-run : Validate and preview without writing}
                            {--resolve-reviews : Resolve the six reviewed identities and fill missing source genders}
                            {--force : Import without interactive confirmation}';

    protected $description = 'Import reviewed MuslimScholars.info Sahaba as unpublished people with provenance';

    public function handle(MuslimScholarsSahabaImportService $service): int
    {
        try {
            $path = $this->absolutePath((string) $this->option('path'));
            $preview = $service->preview($path);
            $this->displayPreview($preview);

            $expected = filter_var($this->option('expected'), FILTER_VALIDATE_INT);
            if ($expected === false || $expected < 1 || $preview->total() !== $expected) {
                $this->error("Expected {$this->option('expected')} source rows; validated {$preview->total()}.");

                return self::FAILURE;
            }

            if ($this->option('dry-run')) {
                $this->warn('Dry run complete — nothing written.');

                return self::SUCCESS;
            }

            $resolveReviews = (bool) $this->option('resolve-reviews');
            $reviewAction = $resolveReviews
                ? 'resolve every reviewed row'
                : 'hold '.count($preview->reviewRows).' review rows';
            if (! $this->option('force') && ! $this->confirm(
                "Insert {$preview->toCreate()} unpublished Sahaba and {$reviewAction}? Dataset ".
                substr($preview->datasetHash, 0, 12)
            )) {
                $this->info('Cancelled.');

                return self::SUCCESS;
            }

            $bar = $this->output->createProgressBar(max(1, $preview->toCreate()));
            $bar->start();
            $result = $service->import(
                $preview,
                static function (int $completed) use ($bar): void {
                    $bar->setProgress($completed);
                },
            );
            if ($preview->toCreate() === 0) {
                $bar->setProgress(1);
            }
            $bar->finish();
            $this->newLine(2);

            $this->table(['Created', 'Already imported', 'Review held', 'Published'], [[
                $result->created,
                $result->alreadyImported,
                $resolveReviews ? 0 : $result->reviewSkipped,
                0,
            ]]);

            if ($resolveReviews) {
                $resolution = $service->resolveReviewsAndFillGender($preview);
                $this->table([
                    'Merged existing', 'Created distinct', 'Already resolved',
                    'Gender filled', 'Profiles filled', 'Metadata synced',
                ], [[
                    $resolution->mergedIntoExisting,
                    $resolution->createdAsDistinct,
                    $resolution->alreadyResolved,
                    $resolution->genderFilled,
                    $resolution->profilesFilled,
                    $resolution->metadataSynced,
                ]]);
            }

            return self::SUCCESS;
        } catch (Throwable $exception) {
            report($exception);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    private function displayPreview(MuslimScholarsSahabaPreview $preview): void
    {
        $this->table(['Validated', 'Arabic names', 'Importable', 'Already imported', 'To create', 'Review'], [[
            $preview->total(),
            $preview->arabicNames,
            $preview->importable(),
            $preview->alreadyImported,
            $preview->toCreate(),
            count($preview->reviewRows),
        ]]);
        $this->line('Dataset SHA-256: '.$preview->datasetHash);
        if ($preview->sourceSnapshotHash !== null) {
            $this->line('Source snapshot SHA-256: '.$preview->sourceSnapshotHash);
        }

        if ($preview->reviewRows !== []) {
            $this->warn('Rows held for manual identity review:');
            $this->table(['Source ID', 'Arabic', 'English', 'Existing ID', 'Reason'], array_map(
                static fn (array $row): array => [
                    $row['source_id'], $row['name_ar'], $row['name_en'],
                    $row['existing_muslim_id'], $row['existing_match_reason'],
                ],
                $preview->reviewRows,
            ));
        }
    }

    private function absolutePath(string $path): string
    {
        return str_starts_with($path, DIRECTORY_SEPARATOR) ? $path : base_path($path);
    }
}
