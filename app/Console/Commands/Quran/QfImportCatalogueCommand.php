<?php

declare(strict_types=1);

namespace App\Console\Commands\Quran;

use App\Console\Commands\Quran\Concerns\ReportsImport;
use App\Domain\Quran\Enums\ResourceKind;
use App\Domain\Quran\Import\QuranFoundation\Importers\CatalogueImporter;
use Illuminate\Console\Command;

final class QfImportCatalogueCommand extends Command
{
    use ReportsImport;

    protected $signature = 'quran:qf:import-catalogue
        {--kinds=all : Comma-separated kinds (translation,tafsir,chapter_info,verse_media,recitation,chapter_reciter) or "all"}
        {--with-info : Also fetch each resource\'s info text}
        {--no-discovery : Skip discovering sync-only resources and mushafs}
        {--dry-run : Fetch and validate without writing}';

    protected $description = 'Import the Quran Foundation resource catalogue (nothing is enabled; editors enable resources in the admin)';

    public function handle(CatalogueImporter $importer): int
    {
        $this->raiseMemoryLimit();

        $kinds = null;
        if ((string) $this->option('kinds') !== 'all') {
            $kinds = [];
            foreach (explode(',', (string) $this->option('kinds')) as $value) {
                $kind = ResourceKind::tryFrom(trim($value));
                if ($kind === null || $kind->qfCataloguePath() === null) {
                    $this->error("Unknown catalogue kind: {$value}");

                    return self::FAILURE;
                }
                $kinds[] = $kind;
            }
        }

        return $this->runStep(fn () => $importer->run(
            $kinds,
            (bool) $this->option('with-info'),
            ! $this->option('no-discovery'),
            (bool) $this->option('dry-run'),
        ));
    }
}
