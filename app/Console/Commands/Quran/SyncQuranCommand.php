<?php

namespace App\Console\Commands;

use App\Domain\Quran\Services\QuranSyncService;
use Illuminate\Console\Command;

class SyncQuranCommand extends Command
{
    protected $signature = 'quran:sync
                            {--from=1 : Start from chapter number (1-114)}
                            {--to=114 : End at chapter number (1-114)}';

    protected $description = 'Sync Quran data from external API';

    public function __construct(private QuranSyncService $quranSyncSrv)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $from = (int) $this->option('from');
        $to = (int) $this->option('to');

        // Validate range
        if ($from < 1 || $from > 114 || $to < 1 || $to > 114 || $from > $to) {
            $this->error('Invalid range. Must be between 1 and 114');

            return Command::FAILURE;
        }

        $this->newLine();
        $this->info('╔══════════════════════════════════════════╗');
        $this->info('║        Quran Data Synchronization        ║');
        $this->info('╚══════════════════════════════════════════╝');
        $this->newLine();

        if ($from > 1 || $to < 114) {
            $this->info("Syncing from chapter {$from} to chapter {$to}");
            $this->newLine();
        }

        try {
            $result = $this->quranSyncSrv->syncSurah(
                onProgress: function (string $message, string $type) {
                    match ($type) {
                        'info' => $this->info("ℹ️  {$message}"),
                        'step' => $this->line("📖 {$message}"),
                        'substep' => $this->line("   ├─ {$message}"),
                        'success' => $this->info($message),
                        'warning' => $this->warn("   ⚠️  {$message}"),
                        'error' => $this->error($message),
                        default => $this->line($message),
                    };
                },
                fromChapter: $from,
                toChapter: $to
            );

            $this->newLine(2);
            $this->info('╔══════════════════════════════════════════╗');
            $this->info('║        Sync completed successfully!      ║');
            $this->info('╚══════════════════════════════════════════╝');
            $this->newLine();

            $this->table(
                ['Item', 'Count'],
                [
                    ['Surahs', $result['surahs']],
                    ['Surah Info', $result['surah_info']],
                    ['Verses', $result['verses']],
                    ['Translations', $result['translations']],
                ]
            );

            if (! empty($result['errors'])) {
                $this->newLine();
                $this->warn('Errors occurred:');
                foreach ($result['errors'] as $error) {
                    $this->error("  ✗ {$error}");
                }

                return Command::FAILURE;
            }

            return Command::SUCCESS;

        } catch (\Throwable $e) {
            $this->newLine();
            $this->error('╔══════════════════════════════════════════╗');
            $this->error('║             Sync failed!                 ║');
            $this->error('╚══════════════════════════════════════════╝');
            $this->error($e->getMessage());

            return Command::FAILURE;
        }
    }
}
