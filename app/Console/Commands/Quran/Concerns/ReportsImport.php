<?php

declare(strict_types=1);

namespace App\Console\Commands\Quran\Concerns;

use App\Domain\Quran\DTOs\ImportReport;
use App\Domain\Quran\Import\QuranFoundation\Exceptions\QfApiException;
use App\Domain\Quran\Import\QuranFoundation\Exceptions\QfPayloadException;
use Illuminate\Console\Command;

/**
 * Prints an ImportReport as a reconciliation table with every gate, note and
 * itemised failure, and turns it into an exit code.
 *
 * @mixin Command
 */
trait ReportsImport
{
    /**
     * Raise the memory limit for a whole-corpus import.
     *
     * The word import alone builds ~77k rows per language, and PHP's CLI default of
     * 128M dies mid-upsert on a laptop — leaving a partial import that only looks
     * fine because every importer is resumable. An import command is a batch job, not
     * a request, so it sets its own floor and never lowers an already-higher limit.
     */
    protected function raiseMemoryLimit(string $limit = '1G'): void
    {
        $current = trim((string) ini_get('memory_limit'));

        if ($current === '-1') {
            return; // already unlimited
        }

        $bytes = static function (string $value): int {
            $unit = strtolower(substr($value, -1));
            $number = (int) $value;

            return match ($unit) {
                'g' => $number * 1024 ** 3,
                'm' => $number * 1024 ** 2,
                'k' => $number * 1024,
                default => $number,
            };
        };

        if ($bytes($current) < $bytes($limit)) {
            ini_set('memory_limit', $limit);
        }
    }

    protected function renderReport(ImportReport $report): int
    {
        $this->newLine();
        $this->line("<options=bold>{$report->step}</>");
        $this->table(['', 'count'], $report->countsTable());

        foreach ($report->gates as $gate) {
            $this->line(($gate['passed'] ? '  <fg=green>✓</> ' : '  <fg=red>✗</> ').$gate['message']);
        }
        foreach ($report->notes as $note) {
            $this->line("  <fg=gray>•</> {$note}");
        }

        if ($report->failed !== []) {
            $this->warn('  Failed ('.count($report->failed).'):');
            foreach (array_slice($report->failed, 0, 50) as $failure) {
                $this->line("    - {$failure['key']}: {$failure['reason']}");
            }
            if (count($report->failed) > 50) {
                $this->line('    … and '.(count($report->failed) - 50).' more');
            }
        }

        return $report->passed() ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Run an import step, turning QF errors into a clear, secret-free message.
     *
     * @param  callable(): ImportReport  $step
     */
    protected function runStep(callable $step): int
    {
        try {
            return $this->renderReport($step());
        } catch (QfApiException $e) {
            $this->error($e->getMessage());
            if ($e->type === 'insufficient_scope' || $e->type === 'forbidden') {
                $this->line('  The Quran Foundation client is not authorised for this endpoint (check QURAN_API_KEY scopes).');
            }

            return self::FAILURE;
        } catch (QfPayloadException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
