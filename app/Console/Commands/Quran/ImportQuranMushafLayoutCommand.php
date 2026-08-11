<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Quran\Services\MushafLayoutImportService;
use Illuminate\Console\Command;
use InvalidArgumentException;
use Throwable;

/**
 * Imports the printed-mushaf word layout from the quran.com API into quran_words.
 * Idempotent (upsert on word location); safe to re-run and to resume partial runs.
 */
class ImportQuranMushafLayoutCommand extends Command
{
    protected $signature = 'quran:import-mushaf-layout
                            {--pages=1-604 : Pages to import — a range "1-604", a CSV "1,2,3", or a single page}';

    protected $description = 'Import mushaf word-layout (line/page/order) from the quran.com API into quran_words — idempotent, keyed by word location';

    public function __construct(
        private readonly MushafLayoutImportService $importService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $pages = $this->parsePages((string) $this->option('pages'));
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Importing mushaf layout for '.count($pages).' page(s) from quran.com ...');
        $bar = $this->output->createProgressBar(count($pages));
        $bar->start();

        try {
            $result = $this->importService->importPages(
                $pages,
                static fn () => $bar->advance(),
            );
        } catch (Throwable $e) {
            $bar->finish();
            $this->newLine();
            $this->error('Import failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $bar->finish();
        $this->newLine();
        $this->info("Done — {$result['words']} words written across {$result['pages']} page(s).");

        return self::SUCCESS;
    }

    /**
     * Parse "1-604", "1,2,3", or "7" into a unique, sorted, validated page list.
     *
     * @return list<int>
     *
     * @throws InvalidArgumentException
     */
    private function parsePages(string $input): array
    {
        $input = trim($input);

        if (preg_match('/^(\d+)-(\d+)$/', $input, $m) === 1) {
            $from = (int) $m[1];
            $to = (int) $m[2];
            if ($from > $to) {
                throw new InvalidArgumentException("Invalid range: {$input}.");
            }
            $pages = range($from, $to);
        } else {
            $pages = [];
            foreach (explode(',', $input) as $part) {
                $part = trim($part);
                if ($part === '' || ! ctype_digit($part)) {
                    throw new InvalidArgumentException("Invalid page value: '{$part}'.");
                }
                $pages[] = (int) $part;
            }
        }

        $pages = array_values(array_unique($pages));
        sort($pages);

        if ($pages === []) {
            throw new InvalidArgumentException('No pages to import.');
        }

        foreach ($pages as $page) {
            if ($page < 1 || $page > MushafLayoutImportService::TOTAL_PAGES) {
                throw new InvalidArgumentException("Page {$page} is out of range (1-".MushafLayoutImportService::TOTAL_PAGES.').');
            }
        }

        return $pages;
    }
}
