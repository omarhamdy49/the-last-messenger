<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Hadith\Services\HadithDeduplicationService;
use Illuminate\Console\Command;

class HadithDeduplicateCommand extends Command
{
    protected $signature = 'hadith:deduplicate {--recalculate-hashes} {--find-only} {--merge}';

    protected $description = 'Deduplicate hadiths based on text similarity';

    public function __construct(
        private readonly HadithDeduplicationService $deduplicationService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if ($this->option('recalculate-hashes')) {
            return $this->recalculateHashes();
        }

        if ($this->option('find-only')) {
            return $this->findDuplicates();
        }

        if ($this->option('merge')) {
            return $this->mergeDuplicates();
        }

        $this->info('Hadith Deduplication Tool');
        $this->info('=======================');
        $this->newLine();

        $choice = $this->choice('What would you like to do?', [
            'recalculate-hashes' => 'Recalculate text hashes for all hadiths',
            'find-only' => 'Find duplicates (report only)',
            'merge' => 'Merge duplicate hadiths',
            'exit' => 'Exit',
        ], 'exit');

        if ($choice === 'exit') {
            $this->info('Goodbye!');
            return 0;
        }

        return $this->call('hadith:deduplicate', ['--' . $choice]);
    }

    private function recalculateHashes(): int
    {
        $this->info('Recalculating text hashes for all hadiths...');
        $this->newLine();

        $bar = $this->output->createProgressBar(100);
        $bar->start();

        try {
            $stats = $this->deduplicationService->recalculateAllHashes();

            $bar->finish();
            $this->newLine(2);

            $this->info('Hash Recalculation Complete');
            $this->table(['Metric', 'Count'], [
                ['Total Hadiths', $stats['total']],
                ['Updated', $stats['updated']],
                ['Failed', $stats['failed']],
            ]);

            if (!empty($stats['errors'])) {
                $this->newLine();
                $this->error('Errors encountered:');
                foreach (array_slice($stats['errors'], 0, 10) as $error) {
                    $this->line("  • {$error}");
                }
                if (count($stats['errors']) > 10) {
                    $this->line("  ... and " . (count($stats['errors']) - 10) . " more");
                }
            }

            return 0;
        } catch (\Exception $e) {
            $bar->finish();
            $this->newLine(2);
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }

    private function findDuplicates(): int
    {
        $this->info('Scanning for duplicate hadiths...');
        $this->newLine();

        try {
            $duplicates = $this->deduplicationService->findDuplicates();

            if (empty($duplicates)) {
                $this->info('✓ No duplicates found!');
                return 0;
            }

            $this->warn("Found {$duplicates[0]['count']} groups of duplicates:");
            $this->newLine();

            foreach (array_slice($duplicates, 0, 10) as $group) {
                $this->line("Hash: {$group['text_hash']}");
                $this->line("Count: {$group['count']}");

                foreach ($group['hadiths'] as $hadith) {
                    $this->line("  - Hadith #{$hadith['id']}: {$hadith['book_title']} (#{$hadith['hadith_number']})");
                    $this->line("    \"{$hadith['text_preview']}\"");
                }
                $this->newLine();
            }

            if (count($duplicates) > 10) {
                $this->line('... and ' . (count($duplicates) - 10) . ' more duplicate groups');
            }

            $this->info("Total duplicate groups: " . count($duplicates));

            return 0;
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }

    private function mergeDuplicates(): int
    {
        $this->warn('WARNING: This operation will merge duplicate hadiths.');
        $this->info('Backups are recommended before proceeding.');
        $this->newLine();

        if (!$this->confirm('Do you want to continue?')) {
            $this->info('Operation cancelled.');
            return 0;
        }

        $this->info('Finding duplicates...');

        try {
            $duplicates = $this->deduplicationService->findDuplicates();

            if (empty($duplicates)) {
                $this->info('✓ No duplicates found!');
                return 0;
            }

            $this->info("Found " . count($duplicates) . " groups of duplicates.");
            $this->newLine();

            $bar = $this->output->createProgressBar(count($duplicates));
            $bar->start();

            $totalMerged = 0;
            $totalFailed = 0;

            foreach ($duplicates as $group) {
                // Keep the hadith with the lowest ID (oldest)
                $primaryId = min(array_map(fn ($h) => $h['id'], $group['hadiths']));

                $stats = $this->deduplicationService->mergeDuplicates(
                    $group['text_hash'],
                    $primaryId
                );

                $totalMerged += $stats['merged'];
                $totalFailed += $stats['failed'];

                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            $this->info('Merge Complete');
            $this->table(['Metric', 'Count'], [
                ['Merged', $totalMerged],
                ['Failed', $totalFailed],
            ]);

            return 0;
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }
}
