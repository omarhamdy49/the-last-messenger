<?php

declare(strict_types=1);

namespace App\Console\Commands\Quran;

use App\Domain\Quran\Services\QuranCacheKeys;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Backfills quran_verses.hizb_number from the (correctly populated)
 * rub_el_hizb_number column. There are 240 rub-el-hizb quarters and 60 ahzab,
 * so hizb = ceil(rub / 4): hizb 1 covers rubs 1-4, hizb 2 rubs 5-8, etc.
 *
 * Quran imports leave hizb_number = 1 for every verse, so run this once after
 * importing Quran data. Idempotent — safe to re-run.
 */
class BackfillQuranHizbCommand extends Command
{
    protected $hidden = true;

    protected $signature = 'quran:backfill-hizb';

    protected $description = 'Populate quran_verses.hizb_number from rub_el_hizb_number (hizb = ceil(rub/4))';

    public function handle(): int
    {
        $totalRubs = 240;
        $rubsPerHizb = 4;
        $hizbCount = $totalRubs / $rubsPerHizb;

        $updated = 0;

        DB::transaction(function () use ($hizbCount, $rubsPerHizb, &$updated) {
            for ($hizb = 1; $hizb <= $hizbCount; $hizb++) {
                $from = ($hizb - 1) * $rubsPerHizb + 1;
                $to = $hizb * $rubsPerHizb;

                $updated += DB::table('quran_verses')
                    ->whereBetween('rub_el_hizb_number', [$from, $to])
                    ->update(['hizb_number' => $hizb]);
            }
        });

        // Public reader pages cache verse payloads; clear so the fix is visible.
        QuranCacheKeys::flushContent();

        $this->info("Backfilled hizb_number for {$updated} verses across {$hizbCount} ahzab.");

        return self::SUCCESS;
    }
}
