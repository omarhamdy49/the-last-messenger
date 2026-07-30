<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Hadith\Services\JsonImportService;
use Illuminate\Console\Command;
use Throwable;

class RebuildHadithCollectionCommand extends Command
{
    protected $signature = 'hadith:rebuild-collection
        {collection : Lowercase production collection directory}
        {--source= : Source root (default: sibling prophet-data-generator/hadith/production)}
        {--portal= : Portal root (default: sibling portal-data)}';

    protected $description = 'Normalize, bulk import, and assert one Hadith collection';

    public function __construct(
        private readonly JsonImportService $importService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $report = $this->importService->rebuildCollection(
                (string) $this->argument('collection'),
                $this->sourceRoot(),
                $this->portalRoot(),
            );
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Rebuilt %s from %s:%s/%s',
            $report['collection'],
            $report['database']['host'],
            $report['database']['port'],
            $report['database']['name'],
        ));
        $this->line("Files read: {$report['files_read']}");
        $this->line("Portal-data files written: {$report['portal']['files_written']}");
        $this->line("Portal-data directory: {$report['portal']['directory']}");

        $this->table(
            ['table', 'rows'],
            array_map(
                static fn (string $table, int $count): array => [$table, $count],
                array_keys($report['rows_written']),
                array_values($report['rows_written']),
            ),
        );
        $this->table(
            ['mapping', 'populated input', 'written output'],
            array_map(
                static fn (string $key, int $count): array => [
                    $key,
                    $count,
                    $report['mapping']['outputs'][$key],
                ],
                array_keys($report['mapping']['inputs']),
                array_values($report['mapping']['inputs']),
            ),
        );

        $this->line('Unmapped grading.status values: '.count($report['mapping']['unmapped_grading_statuses']));
        $this->line('Application source values captured: '.$report['mapping']['captured_values']['applications']);
        $this->line('Fiqh source values captured: '.$report['mapping']['captured_values']['fiqh_rulings']);
        $this->table(
            ['keyword source', 'new unique rows'],
            array_map(
                static fn (string $key, int $count): array => [$key, $count],
                array_keys($report['mapping']['keyword_sources']),
                array_values($report['mapping']['keyword_sources']),
            ),
        );
        $this->table(
            ['removed-schema field', 'populated values skipped'],
            array_map(
                static fn (string $key, int $count): array => [$key, $count],
                array_keys($report['mapping']['skipped_schema_fields']),
                array_values($report['mapping']['skipped_schema_fields']),
            ),
        );
        $this->table(
            ['legacy key', 'present', 'handled', 'skipped'],
            array_map(
                static fn (string $key, array $counts): array => [
                    $key,
                    $counts['present'],
                    $counts['handled'],
                    $counts['skipped'],
                ],
                array_keys($report['mapping']['legacy_variants']),
                array_values($report['mapping']['legacy_variants']),
            ),
        );

        return self::SUCCESS;
    }

    private function sourceRoot(): string
    {
        return (string) ($this->option('source')
            ?: dirname(base_path()).'/prophet-data-generator/hadith/production');
    }

    private function portalRoot(): string
    {
        return (string) ($this->option('portal')
            ?: dirname(base_path()).'/portal-data');
    }
}
