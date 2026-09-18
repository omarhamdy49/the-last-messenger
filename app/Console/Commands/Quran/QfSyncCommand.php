<?php

declare(strict_types=1);

namespace App\Console\Commands\Quran;

use App\Console\Commands\Quran\Concerns\ReportsImport;
use App\Domain\Quran\Import\QuranFoundation\Sync\ContentSyncService;
use Illuminate\Console\Command;

final class QfSyncCommand extends Command
{
    use ReportsImport;

    protected $signature = 'quran:qf:sync
        {--bootstrap : Ignore the stored token and fetch full snapshots}
        {--resources= : Explicit QF resources filter (default: every enabled resource)}
        {--per-page=100 : Changes per page (1–100)}
        {--show-filter : Print the canonical filter and exit}';

    protected $description = 'Apply Quran Foundation Content Sync for every enabled translation, tafsir, word-by-word resource, recitation and active mushaf';

    public function handle(ContentSyncService $sync): int
    {
        $this->raiseMemoryLimit();

        if ($this->option('show-filter')) {
            $this->line($sync->canonicalFilter() ?: '(nothing enabled)');

            return self::SUCCESS;
        }

        $filter = $this->option('resources');

        return $this->runStep(function () use ($sync, $filter) {
            ['run' => $run, 'report' => $report] = $sync->run(
                (bool) $this->option('bootstrap'),
                is_string($filter) && $filter !== '' ? $filter : null,
                (int) $this->option('per-page'),
            );

            $this->line("Run #{$run->id}: {$run->mutations_applied} mutation(s), {$run->snapshots_fetched} snapshot(s)");

            return $report;
        });
    }
}
