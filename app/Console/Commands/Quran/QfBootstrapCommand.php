<?php

declare(strict_types=1);

namespace App\Console\Commands\Quran;

use Illuminate\Console\Command;

/**
 * Runs the corpus import in dependency order and stops at the first failing step.
 * Every step is idempotent, so a failed bootstrap can simply be re-run.
 */
final class QfBootstrapCommand extends Command
{
    protected $signature = 'quran:qf:bootstrap
        {--only=* : Run only these steps}
        {--skip=* : Skip these steps}
        {--dry-run : Fetch and validate without writing}';

    protected $description = 'Import the whole Quran corpus from Quran Foundation: structure, verses, words, catalogue';

    /** Step => [command, extra arguments]. Order matters. */
    public const STEPS = [
        'structure' => ['quran:qf:import-structure', []],
        'verses' => ['quran:qf:import-verses', []],
        'words' => ['quran:qf:import-words', []],
        'catalogue' => ['quran:qf:import-catalogue', ['--with-info' => true]],
    ];

    public function handle(): int
    {
        $only = (array) $this->option('only');
        $skip = (array) $this->option('skip');
        $unknown = array_diff([...$only, ...$skip], array_keys(self::STEPS));
        if ($unknown !== []) {
            $this->error('Unknown step(s): '.implode(', ', $unknown).'. Steps: '.implode(', ', array_keys(self::STEPS)));

            return self::FAILURE;
        }

        foreach (self::STEPS as $name => [$command, $arguments]) {
            if (($only !== [] && ! in_array($name, $only, true)) || in_array($name, $skip, true)) {
                continue;
            }

            $this->info("▶ {$name} ({$command})");
            if ($this->option('dry-run')) {
                $arguments['--dry-run'] = true;
            }

            if ($this->call($command, $arguments) !== self::SUCCESS) {
                $this->error("Stopped: step '{$name}' failed. Fix the reported problem and re-run; completed steps are idempotent.");

                return self::FAILURE;
            }
        }

        $this->info('Bootstrap complete. Enable resources in the admin, then run quran:qf:sync --bootstrap.');

        return self::SUCCESS;
    }
}
