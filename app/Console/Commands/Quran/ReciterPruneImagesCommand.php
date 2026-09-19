<?php

declare(strict_types=1);

namespace App\Console\Commands\Quran;

use App\Console\Commands\Quran\Concerns\ReportsImport;
use App\Domain\Quran\Import\Reciters\ReciterProfileImporter;
use Illuminate\Console\Command;

final class ReciterPruneImagesCommand extends Command
{
    use ReportsImport;

    protected $signature = 'quran:reciter:prune-images';

    protected $description = 'حذف صور القرّاء المخزّنة محليًا التي لا يشير إليها أي مصدر';

    public function handle(ReciterProfileImporter $importer): int
    {
        return $this->runStep(fn () => $importer->pruneImages());
    }
}
