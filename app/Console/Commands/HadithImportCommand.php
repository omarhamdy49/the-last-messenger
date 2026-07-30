<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Concerns\GuardsDatabaseTarget;
use App\Domain\Hadith\Enums\ImportStatus;
use App\Domain\Hadith\Import\HadithImporter;
use App\Domain\Hadith\Import\SourceFileReader;
use App\Domain\Hadith\Models\HadithImportRun;
use Illuminate\Console\Command;
use Throwable;

/**
 * Imports extracted sunnah.com book files into the schema.
 *
 * The files come from the browser-side extractor (scripts/extract-sunnah-book.js)
 * — sunnah.com 403s non-browser clients, so this command never fetches anything.
 */
class HadithImportCommand extends Command
{
    use GuardsDatabaseTarget;

    protected $signature = 'hadith:import
                            {--collection= : collection code, e.g. bukhari (required)}
                            {--book= : import only this book number}
                            {--dry-run : parse and report without writing}
                            {--reset : let the source overwrite editor-owned fields}';

    protected $description = 'Import extracted sunnah.com book files for one collection';

    /**
     * Import writes across five tables, so it is fenced to the databases this
     * project owns. A stale --env pointing at another schema is a real hazard:
     * this repo already lost its dev database to a mis-targeted command.
     */
    private const ALLOWED_DATABASES = ['the_last_messenger', 'the_last_messenger_test'];

    public function handle(SourceFileReader $reader, HadithImporter $importer): int
    {
        try {
            $this->assertSafeDatabase(self::ALLOWED_DATABASES);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $collection = (string) $this->option('collection');

        if ($collection === '') {
            $this->error('--collection is required.');

            return self::FAILURE;
        }

        $directory = storage_path('app/hadith_source/'.$collection);
        $files = $reader->booksFor($directory);

        if ($book = $this->option('book')) {
            $files = array_values(array_filter(
                $files,
                static fn (string $f): bool => basename($f) === 'book_'.$book.'.json',
            ));
        }

        if ($files === []) {
            $this->error("No extracted book files under {$directory}.");

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        $this->info(sprintf(
            '%s %d book file(s) for "%s"%s',
            $dryRun ? 'Validating' : 'Importing',
            count($files),
            $collection,
            $dryRun ? ' (dry run — nothing will be written)' : '',
        ));

        return $dryRun
            ? $this->validateOnly($reader, $files)
            : $this->runImport($importer, $collection, $files);
    }

    /**
     * @param  list<string>  $files
     */
    private function validateOnly(SourceFileReader $reader, array $files): int
    {
        $records = 0;

        foreach ($files as $file) {
            try {
                $payload = $reader->read($file);
                $records += count($payload['records']);
            } catch (Throwable $e) {
                $this->error(basename($file).': '.$e->getMessage());

                return self::FAILURE;
            }
        }

        $this->info("All files parse. {$records} record(s) would be imported.");

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $files
     */
    private function runImport(HadithImporter $importer, string $collection, array $files): int
    {
        $run = HadithImportRun::query()->create([
            'collection_code' => $collection,
            'source_path' => storage_path('app/hadith_source/'.$collection),
            'status' => ImportStatus::RUNNING,
            'started_at' => now(),
        ]);

        $totals = [];
        $bar = $this->output->createProgressBar(count($files));
        $bar->start();

        try {
            foreach ($files as $file) {
                foreach ($importer->importFile($file, $collection, (bool) $this->option('reset')) as $key => $value) {
                    $totals[$key] = $value;
                }
                $bar->advance();
            }
        } catch (Throwable $e) {
            $bar->finish();
            $this->newLine(2);

            // Recorded, not swallowed: a failed run that leaves no trace is how
            // a partial corpus goes unnoticed.
            $run->update([
                'status' => ImportStatus::FAILED,
                'error_message' => $e->getMessage(),
                'counts' => $totals,
                'completed_at' => now(),
            ]);

            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $bar->finish();
        $this->newLine(2);

        $unmapped = $importer->unmappedGrades();

        $run->update([
            'status' => ImportStatus::COMPLETED,
            'counts' => $totals,
            'unmapped_grades' => $unmapped,
            'completed_at' => now(),
        ]);

        $this->table(['metric', 'count'], array_map(
            static fn (string $k, int $v): array => [$k, $v],
            array_keys($totals),
            array_values($totals),
        ));

        if ($unmapped !== []) {
            $this->warn(sprintf(
                '%d grade string(s) could not be interpreted and were stored as "unknown" with their raw text intact.',
                count($unmapped),
            ));
        }

        $this->info("Import run #{$run->id} completed.");

        return self::SUCCESS;
    }
}
