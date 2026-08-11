<?php

declare(strict_types=1);

namespace App\Console\Commands\Quran;

use App\Domain\Quran\Repositories\AiInquiryQuotaRepositoryInterface;
use App\Domain\Quran\Repositories\AiInquiryRepositoryInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Bounds the storage footprint of the "Ask Quran" AI feature so a growing volume
 * of saved questions never impacts disk or query performance over time:
 *
 *   - hard-deletes soft-deleted (moderated-away) inquiries past their retention;
 *   - optionally hard-deletes live history older than a retention window
 *     (disabled by default — set QURAN_AI_HISTORY_RETENTION_DAYS to enable);
 *   - prunes stale monthly quota rows (only the current month enforces quota).
 *
 * Deletions are batched in the repository to avoid long table locks at scale.
 * Use --dry-run to preview counts. Scheduled daily in routes/console.php.
 */
final class PruneAiInquiriesCommand extends Command
{
    protected $signature = 'quran:ai-prune
        {--trashed-days= : Hard-delete soft-deleted inquiries trashed older than N days (default: config)}
        {--history-days= : Hard-delete live history older than N days; 0 keeps forever (default: config)}
        {--usage-months= : Delete monthly quota rows older than N months (default: config)}
        {--dry-run : Report what would be deleted without deleting anything}';

    protected $description = 'Prune old Ask Quran AI inquiries and quota rows to bound disk/performance.';

    public function handle(
        AiInquiryRepositoryInterface $inquiries,
        AiInquiryQuotaRepositoryInterface $quota,
    ): int {
        $dryRun = (bool) $this->option('dry-run');

        $trashedDays = max(0, (int) ($this->option('trashed-days') ?? config('services.quran_ai.trashed_retention_days', 30)));
        $historyDays = max(0, (int) ($this->option('history-days') ?? config('services.quran_ai.history_retention_days', 0)));
        $usageMonths = max(1, (int) ($this->option('usage-months') ?? config('services.quran_ai.usage_retention_months', 12)));

        $now = Carbon::now();

        // 1. Soft-deleted inquiries past retention — always safe to reclaim.
        $trashed = $inquiries->purgeTrashedBefore($now->copy()->subDays($trashedDays), $dryRun);

        // 2. Live history retention — opt-in; 0 keeps every user's history forever.
        $history = $historyDays > 0
            ? $inquiries->purgeCreatedBefore($now->copy()->subDays($historyDays), $dryRun)
            : 0;

        // 3. Stale monthly quota rows — the current month is never touched.
        $cutoffPeriod = $now->copy()->subMonthsNoOverflow($usageMonths)->format('Y-m');
        $usage = $quota->purgePeriodsBefore($cutoffPeriod, $dryRun);

        $verb = $dryRun ? 'Would delete' : 'Deleted';
        $this->info($verb.':');
        $this->line("  • trashed inquiries (trashed > {$trashedDays}d): {$trashed}");
        $this->line('  • history inquiries '.($historyDays > 0 ? "(created > {$historyDays}d): {$history}" : '(retention disabled): 0'));
        $this->line("  • quota rows (period < {$cutoffPeriod}): {$usage}");

        return self::SUCCESS;
    }
}
