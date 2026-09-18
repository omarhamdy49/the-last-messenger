<?php

declare(strict_types=1);

namespace App\Console\Commands\Quran;

use App\Console\Commands\Quran\Concerns\ReportsImport;
use App\Domain\Quran\Import\QuranFoundation\Importers\WordsImporter;
use App\Domain\Quran\Models\Chapter;
use App\Domain\Quran\Services\QuranCacheKeys;
use Illuminate\Console\Command;

final class QfImportWordsCommand extends Command
{
    use ReportsImport;

    protected $signature = 'quran:qf:import-words
        {--from=1 : First chapter}
        {--to=114 : Last chapter}
        {--language=en,ar,fr,tr,ur : Word-by-word gloss languages (the first also writes the words)}
        {--dry-run : Fetch and validate without writing}';

    protected $description = 'Import words (glyphs, scripts, positions, per-word audio) and word-by-word glosses from Quran Foundation';

    public function handle(WordsImporter $importer): int
    {
        $this->raiseMemoryLimit();

        [$from, $to] = [(int) $this->option('from'), (int) $this->option('to')];
        if ($from < 1 || $to > Chapter::COUNT || $from > $to) {
            $this->error('--from/--to must satisfy 1 ≤ from ≤ to ≤ 114.');

            return self::FAILURE;
        }

        $languages = array_values(array_filter(array_map('trim', explode(',', (string) $this->option('language')))));

        $code = $this->runStep(fn () => $importer->run($from, $to, $languages, (bool) $this->option('dry-run')));

        if (! $this->option('dry-run')) {
            QuranCacheKeys::flushContent();
        }

        return $code;
    }
}
