<?php

declare(strict_types=1);

namespace App\Console\Commands\IslamicData;

use Illuminate\Console\Command;

class ImportAllCommand extends Command
{
    protected $signature = 'islamic-data:import-all
                            {--fresh : Truncate Quran tables before importing (hadith import is always additive)}
                            {--chunk=500 : Hadith files per batch}
                            {--dry-run : Simulate both imports without writing}';

    protected $description = 'Import the full Quran and all hadith production data, then print combined status';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info('=== 1/2 Quran import ===');
        $quranExit = $this->call('islamic-data:import-quran', array_filter([
            '--fresh' => (bool) $this->option('fresh'),
            '--dry-run' => $dryRun,
        ]));

        if ($quranExit !== self::SUCCESS) {
            $this->error('Quran import failed — aborting before hadith import.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('=== 2/2 Hadith import ===');
        $hadithExit = $this->call('islamic-data:import-hadith', array_filter([
            '--chunk' => $this->option('chunk'),
            '--dry-run' => $dryRun,
        ]));

        if ($hadithExit !== self::SUCCESS) {
            return self::FAILURE;
        }

        $this->newLine();

        return $dryRun ? self::SUCCESS : $this->call('islamic-data:verify');
    }
}
