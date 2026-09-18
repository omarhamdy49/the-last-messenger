<?php

declare(strict_types=1);

namespace App\Console\Commands\Quran;

use App\Console\Commands\Quran\Concerns\ReportsImport;
use App\Domain\Quran\Import\QuranFoundation\Importers\ChapterAudioTimestampsImporter;
use Illuminate\Console\Command;

final class QfImportTimestampsCommand extends Command
{
    use ReportsImport;

    protected $signature = 'quran:qf:import-timestamps
        {--reciter= : Local chapter-reciter resource id (default: every active or sync-enabled QF chapter reciter)}
        {--from=1 : First chapter}
        {--to=114 : Last chapter}';

    protected $description = 'Import chapter audio files with per-verse timestamps and word segments from Quran Foundation';

    public function handle(ChapterAudioTimestampsImporter $importer): int
    {
        $this->raiseMemoryLimit();

        $reciter = $this->option('reciter');

        return $this->runStep(fn () => $importer->run(
            is_numeric($reciter) ? (int) $reciter : null,
            (int) $this->option('from'),
            (int) $this->option('to'),
        ));
    }
}
