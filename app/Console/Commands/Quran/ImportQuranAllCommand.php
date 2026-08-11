<?php

declare(strict_types=1);

namespace App\Console\Commands\Quran;

use Illuminate\Console\Command;

/**
 * Single entry point for importing everything Quran from external APIs:
 * surahs/verses, Arabic text reconciliation, hizb backfill, all translation
 * and tafsir editions, reciters (+ Wikidata enrichment), recitation audio,
 * and the mushaf word layout.
 *
 * Each step is an existing hidden command and remains individually callable
 * (e.g. `php artisan quran:import-recitations`) for granular re-runs; all
 * steps are idempotent, so this command is safe to re-run at any time.
 */
class ImportQuranAllCommand extends Command
{
    protected $signature = 'quran:import
        {--skip=* : Step names to skip (see quran:import --list)}
        {--list : Show the steps without running anything}';

    protected $description = 'Import all Quran data from external APIs: verses, translations, tafsirs, reciters, recitations, mushaf layout';

    /** Step name => underlying command. Order matters: verses must exist first. */
    private const STEPS = [
        'verses' => 'quran:sync',
        'arabic-text' => 'quran:sync-arabic',
        'hizb' => 'quran:backfill-hizb',
        'editions' => 'quran:import-editions',
        'reciters' => 'quran:import-reciters',
        'reciter-profiles' => 'quran:enrich-reciters',
        'recitations' => 'quran:import-recitations',
        'mushaf-layout' => 'quran:import-mushaf-layout',
    ];

    public function handle(): int
    {
        if ($this->option('list')) {
            foreach (self::STEPS as $name => $command) {
                $this->line(sprintf('  %-18s %s', $name, $command));
            }

            return self::SUCCESS;
        }

        $skip = (array) $this->option('skip');
        if ($unknown = array_diff($skip, array_keys(self::STEPS))) {
            $this->error('Unknown step(s): '.implode(', ', $unknown).'. See quran:import --list');

            return self::FAILURE;
        }

        $total = count(self::STEPS) - count($skip);
        $index = 0;
        foreach (self::STEPS as $name => $command) {
            if (in_array($name, $skip, true)) {
                continue;
            }

            $index++;
            $this->info("[{$index}/{$total}] {$name} ({$command})");
            if ($this->call($command) !== self::SUCCESS) {
                $this->error("Step '{$name}' failed — stopping. Fix and re-run (already-imported steps are idempotent, or use --skip).");

                return self::FAILURE;
            }
        }

        $this->info('Quran import complete.');

        return self::SUCCESS;
    }
}
