<?php

declare(strict_types=1);

namespace App\Console\Commands\Quran;

use App\Console\Commands\Quran\Concerns\ReportsImport;
use App\Domain\Quran\Import\Reciters\ReciterProfileImporter;
use Illuminate\Console\Command;

final class ReciterImagesCommand extends Command
{
    use ReportsImport;

    protected $signature = 'quran:reciter:images
        {--download : Keep a copy on the public disk instead of linking the remote image}
        {--force : Replace images that are already set, including uploads}
        {--prune : Only delete portrait files on the public disk that no resource references}';

    protected $description = 'Fill reciter portraits and missing Arabic names from the public quran.com reciter list';

    public function handle(ReciterProfileImporter $importer): int
    {
        if ($this->option('prune')) {
            return $this->runStep(fn () => $importer->pruneImages());
        }

        return $this->runStep(fn () => $importer->import((bool) $this->option('download'), (bool) $this->option('force')));
    }
}
