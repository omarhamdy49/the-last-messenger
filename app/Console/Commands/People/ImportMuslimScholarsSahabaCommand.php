<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\People\Import\MuslimScholarsSahabaImportPlan;
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
                            {--resolve-reviews : Deprecated; reviewed matches are always resolved safely by stable slug}
                            {--force : Import without interactive confirmation}';

    protected $description = 'Import reviewed MuslimScholars.info Sahaba as unpublished people with provenance';

    public function handle(MuslimScholarsSahabaImportService $service): int
    {
        try {
            $path = $this->absolutePath((string) $this->option('path'));
            $preview = $service->preview($path);
            $plan = $service->plan($preview);
            $this->displayPreview($preview, $plan);

            $expected = filter_var($this->option('expected'), FILTER_VALIDATE_INT);
            if ($expected === false || $expected < 1 || $preview->total() !== $expected) {
                $this->error("Expected {$this->option('expected')} source rows; validated {$preview->total()}.");

                return self::FAILURE;
            }

            if ($this->option('dry-run')) {
                $this->warn('Dry run complete — nothing written.');

                return self::SUCCESS;
            }

            if (! $this->option('force') && ! $this->confirm(
                'Process all source rows: create '.count($plan->createRows).
                ', update '.count($plan->mergeRows).
                ', refresh '.count($plan->refreshRows).'? Dataset '.
                substr($preview->datasetHash, 0, 12)
            )) {
                $this->info('Cancelled.');

                return self::SUCCESS;
            }

            $bar = $this->output->createProgressBar(max(1, $plan->total()));
            $bar->start();
            $result = $service->import(
                $preview,
                static function (int $completed) use ($bar): void {
                    $bar->setProgress($completed);
                },
            );
            if ($plan->total() === 0) {
                $bar->setProgress(1);
            }
            $bar->finish();
            $this->newLine(2);

            $this->table(['Created drafts', 'Updated existing', 'Refreshed imported'], [[
                $result->created,
                $result->mergedExisting,
                $result->refreshedImported,
            ]]);

            return self::SUCCESS;
        } catch (Throwable $exception) {
            report($exception);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    private function displayPreview(
        MuslimScholarsSahabaPreview $preview,
        MuslimScholarsSahabaImportPlan $plan,
    ): void {
        $this->table(['Validated', 'Arabic names', 'Create drafts', 'Update existing', 'Refresh imported'], [[
            $preview->total(),
            $preview->arabicNames,
            count($plan->createRows),
            count($plan->mergeRows),
            count($plan->refreshRows),
        ]]);
        $this->line('Dataset SHA-256: '.$preview->datasetHash);
        if ($preview->sourceSnapshotHash !== null) {
            $this->line('Source snapshot SHA-256: '.$preview->sourceSnapshotHash);
        }

        if ($plan->mergeRows !== []) {
            $this->info('Existing people are updated in place; IDs, slugs, relationships, and publication state are preserved.');
        }
        if ($preview->reviewRows !== []) {
            $this->line(
                count($preview->reviewRows).
                ' legacy CSV review flags were re-resolved using stable slugs; stored numeric IDs were ignored.'
            );
        }
    }

    private function absolutePath(string $path): string
    {
        return str_starts_with($path, DIRECTORY_SEPARATOR) ? $path : base_path($path);
    }
}
