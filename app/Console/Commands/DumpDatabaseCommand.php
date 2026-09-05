<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Shared\Services\DatabaseDumpService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Creates a gzipped database archive under storage/app/private/db-dumps.
 *
 * Exposed in the admin settings hub through config/artisan-commands.php, where it
 * is marked `queued`: the production database takes minutes to dump and an inline
 * run would die on PHP's max_execution_time mid-write.
 */
class DumpDatabaseCommand extends Command
{
    protected $signature = 'db:dump';

    protected $description = 'إنشاء نسخة مضغوطة من قاعدة البيانات (تُحفظ للتنزيل من إعدادات النظام)';

    public function handle(DatabaseDumpService $dumps): int
    {
        try {
            $path = $dumps->dump(fn (string $line) => $this->info($line));
        } catch (Throwable $e) {
            // Reported, not swallowed: a backup that silently fails is worse than
            // no backup, because it is trusted.
            report($e);
            $this->error('Dump failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Archive: '.basename($path));
        $this->info('Download it from Settings → النسخ الاحتياطي.');

        return self::SUCCESS;
    }
}
