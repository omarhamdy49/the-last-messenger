<?php

namespace App\Console\Commands;

use App\Domain\Quran\Services\QuranSyncService;
use Illuminate\Console\Command;

class SyncQuranCommand extends Command
{
    private QuranSyncService $quranSyncSrv;
    protected $signature = 'quran:sync';

    public function __construct(QuranSyncService $quranSyncSrv)
    {
        parent::__construct();
        $this->quranSyncSrv = $quranSyncSrv;
    }

    public function handle(): void
    {
        $this->quranSyncSrv->syncSurah();
    }
}
