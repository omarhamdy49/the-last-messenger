<?php

declare(strict_types=1);

namespace App\Console\Commands\Quran;

use App\Console\Commands\Quran\Concerns\ReportsImport;
use App\Domain\Quran\Import\QuranFoundation\Importers\ChapterInfosImporter;
use Illuminate\Console\Command;

final class QfImportChapterInfosCommand extends Command
{
    use ReportsImport;

    protected $signature = 'quran:qf:import-chapter-infos
        {--resource= : Local resource id (default: every sync-enabled chapter-info resource)}
        {--from=1 : First chapter}
        {--to=114 : Last chapter}';

    protected $description = 'Import chapter introductions from Quran Foundation for enabled chapter-info sources';

    public function handle(ChapterInfosImporter $importer): int
    {
        $this->raiseMemoryLimit();

        $resource = $this->option('resource');

        return $this->runStep(fn () => $importer->run(
            is_numeric($resource) ? (int) $resource : null,
            (int) $this->option('from'),
            (int) $this->option('to'),
        ));
    }
}
