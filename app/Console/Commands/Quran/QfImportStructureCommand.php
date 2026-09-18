<?php

declare(strict_types=1);

namespace App\Console\Commands\Quran;

use App\Console\Commands\Quran\Concerns\ReportsImport;
use App\Domain\Quran\Import\QuranFoundation\Importers\StructureImporter;
use App\Domain\Quran\Services\QuranCacheKeys;
use Illuminate\Console\Command;

final class QfImportStructureCommand extends Command
{
    use ReportsImport;

    protected $signature = 'quran:qf:import-structure {--dry-run : Fetch and validate without writing}';

    protected $description = 'Import chapters (+ localized names), juz/hizb/rubʿ/manzil/rukūʿ, default-mushaf pages and languages from Quran Foundation';

    public function handle(StructureImporter $importer): int
    {
        $this->raiseMemoryLimit();

        $code = $this->runStep(fn () => $importer->run((bool) $this->option('dry-run')));

        if (! $this->option('dry-run')) {
            QuranCacheKeys::flushContent();
        }

        return $code;
    }
}
