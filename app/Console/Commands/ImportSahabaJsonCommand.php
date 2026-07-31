<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Sahaba\Services\SahabaJsonImportService;
use Illuminate\Console\Command;
use RuntimeException;
use Throwable;

final class ImportSahabaJsonCommand extends Command
{
    protected $signature = 'sahaba:import-json
                            {path : Directory containing one Sahabi JSON file per record}
                            {--dry-run : Validate every JSON file without writing}
                            {--existing-only : Enrich only identities already present; permits scholar records}
                            {--overwrite-relations : Replace conflicting father/mother links}
                            {--stop-on-error : Stop on the first invalid file or database error}
                            {--report= : JSON report path for counts, conflicts, and errors}';

    protected $description = 'Import Sahaba JSON files and build their canonical family relationships';

    public function __construct(
        private readonly SahabaJsonImportService $importService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $path = (string) $this->argument('path');
        $directory = realpath($path);

        if ($directory === false || ! is_dir($directory)) {
            $this->error("Directory not found: {$path}");

            return self::FAILURE;
        }

        $this->info('Sahaba JSON Import');
        $this->line("Directory: {$directory}");
        if ($this->option('dry-run')) {
            $this->warn('Dry run: validation only; no database writes.');
        }
        $this->newLine();

        $lastPhase = null;
        $bar = null;

        try {
            $stats = $this->importService->importDirectory(
                directory: $directory,
                dryRun: (bool) $this->option('dry-run'),
                existingOnly: (bool) $this->option('existing-only'),
                overwriteRelations: (bool) $this->option('overwrite-relations'),
                stopOnError: (bool) $this->option('stop-on-error'),
                progress: function (string $phase, int $current, int $total) use (&$lastPhase, &$bar): void {
                    if ($phase !== $lastPhase) {
                        if ($bar !== null) {
                            $bar->finish();
                            $this->newLine(2);
                        }
                        $lastPhase = $phase;
                        $this->line(ucfirst($phase).':');
                        $bar = $this->output->createProgressBar($total);
                        $bar->start();
                    }
                    $bar?->setProgress($current);
                },
            );

            if ($bar !== null) {
                $bar->finish();
                $this->newLine(2);
            }

            $this->table(['Metric', 'Count'], [
                ['JSON files', $stats['files']],
                ['Validated', $stats['validated']],
                ['Created', $stats['created']],
                ['Updated', $stats['updated']],
                ['Skipped (not present)', $stats['skipped']],
                ['Relation placeholders', $stats['placeholders_created']],
                ['Translations upserted', $stats['translations_upserted']],
                ['Parent links', $stats['parents_linked']],
                ['Child-derived links', $stats['children_linked']],
                ['Relationships', $stats['relationships_upserted']],
                ['Relation conflicts', $stats['relation_conflicts']],
                ['Failed', $stats['failed']],
            ]);

            if ($stats['errors'] !== []) {
                $this->newLine();
                $this->error('Import errors:');
                foreach (array_slice($stats['errors'], 0, 20) as $error) {
                    $this->line("  {$error['file']}: {$error['message']}");
                }
            }

            $this->writeReport($directory, $stats);

            return $stats['failed'] === 0 ? self::SUCCESS : self::FAILURE;
        } catch (Throwable $exception) {
            if ($bar !== null) {
                $bar->finish();
                $this->newLine(2);
            }
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    /** @param array<string, mixed> $stats */
    private function writeReport(string $directory, array $stats): void
    {
        $reportPath = (string) $this->option('report');
        if ($reportPath === '') {
            return;
        }

        $report = [
            'generated_at' => now()->toIso8601String(),
            'directory' => $directory,
            'dry_run' => (bool) $this->option('dry-run'),
            ...$stats,
        ];
        $written = file_put_contents(
            $reportPath,
            json_encode($report, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n",
        );
        if ($written === false) {
            throw new RuntimeException("Unable to write report: {$reportPath}");
        }

        $this->line("Report: {$reportPath}");
    }
}
