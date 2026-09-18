<?php

declare(strict_types=1);

namespace App\Console\Commands\Quran;

use App\Console\Commands\Quran\Concerns\ReportsImport;
use App\Domain\Quran\DTOs\ImportReport;
use App\Domain\Quran\Import\QuranFoundation\Importers\AssetDownloader;
use Illuminate\Console\Command;

final class QfDownloadAssetsCommand extends Command
{
    use ReportsImport;

    protected $signature = 'quran:qf:download-assets
        {--images : Download verse images to the public disk}
        {--fonts= : Comma-separated font sets: v1,v2,hafs,indopak}
        {--force : Re-download files that already exist}';

    protected $description = 'Download verse images and mushaf fonts so the reader serves them locally';

    public function handle(AssetDownloader $downloader): int
    {
        $this->raiseMemoryLimit();

        $fonts = array_values(array_filter(array_map('trim', explode(',', (string) $this->option('fonts')))));

        if (! $this->option('images') && $fonts === []) {
            $this->error('Nothing to do: pass --images and/or --fonts=v1,v2,hafs,indopak.');

            return self::FAILURE;
        }

        $code = self::SUCCESS;
        $force = (bool) $this->option('force');

        if ($this->option('images')) {
            $code = max($code, $this->runStep(fn (): ImportReport => $downloader->images($force)));
        }
        if ($fonts !== []) {
            $code = max($code, $this->runStep(fn (): ImportReport => $downloader->fonts($fonts, $force)));
        }

        return $code;
    }
}
