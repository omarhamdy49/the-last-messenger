<?php

declare(strict_types=1);

namespace App\Console\Commands\IslamicData;

use Illuminate\Console\Command;

/**
 * Hadith is deliberately absent here. It is no longer imported from the
 * prophet-data-generator production files; it comes from sunnah.com pages
 * extracted to storage/app/hadith_source and loaded by `hadith:import`,
 * which is per-collection and cannot be a blind all-or-nothing stage.
 */
class ImportAllCommand extends Command
{
    protected $signature = 'islamic-data:import-all
                            {--fresh : Truncate Quran tables before importing}
                            {--dry-run : Simulate the import without writing}';

    protected $description = 'Import the full Quran production data, then print combined status';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info('=== Quran import ===');
        $quranExit = $this->call('islamic-data:import-quran', array_filter([
            '--fresh' => (bool) $this->option('fresh'),
            '--dry-run' => $dryRun,
        ]));

        if ($quranExit !== self::SUCCESS) {
            return self::FAILURE;
        }

        $this->newLine();

        return $dryRun ? self::SUCCESS : $this->call('islamic-data:verify');
    }
}
