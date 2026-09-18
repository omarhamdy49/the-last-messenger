<?php

declare(strict_types=1);

namespace App\Console\Commands\Quran;

use App\Console\Commands\Quran\Concerns\ReportsImport;
use App\Domain\Quran\Enums\Script;
use App\Domain\Quran\Import\QuranFoundation\Importers\VersesImporter;
use App\Domain\Quran\Models\Chapter;
use App\Domain\Quran\Services\QuranCacheKeys;
use Illuminate\Console\Command;

final class QfImportVersesCommand extends Command
{
    use ReportsImport;

    protected $signature = 'quran:qf:import-verses
        {--from=1 : First chapter}
        {--to=114 : Last chapter}
        {--scripts=all : Comma-separated scripts (uthmani,imlaei,…) or "all"}
        {--dry-run : Fetch and validate without writing}';

    protected $description = 'Import verse metadata and the text of every script from Quran Foundation (editor-locked verses are kept)';

    public function handle(VersesImporter $importer): int
    {
        $this->raiseMemoryLimit();

        [$from, $to] = [(int) $this->option('from'), (int) $this->option('to')];
        if ($from < 1 || $to > Chapter::COUNT || $from > $to) {
            $this->error('--from/--to must satisfy 1 ≤ from ≤ to ≤ 114.');

            return self::FAILURE;
        }

        $scripts = null;
        if ((string) $this->option('scripts') !== 'all') {
            $scripts = [];
            foreach (explode(',', (string) $this->option('scripts')) as $name) {
                $script = Script::fromExternal($name);
                if ($script === null) {
                    $this->error("Unknown script: {$name}");

                    return self::FAILURE;
                }
                $scripts[] = $script;
            }
        }

        $code = $this->runStep(fn () => $importer->run($from, $to, $scripts, (bool) $this->option('dry-run')));

        if (! $this->option('dry-run')) {
            QuranCacheKeys::flushContent();
        }

        return $code;
    }
}
