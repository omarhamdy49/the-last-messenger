<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Concerns\DisplaysHadithSyncOutput;
use App\Domain\Hadith\Models\HadithSyncLog;
use App\Domain\Hadith\Services\JsonImportService;
use App\Domain\Hadith\ValueObjects\SyncStatistics;
use App\Services\Hadith\JsonFileScanner;
use App\Services\Json\JsonDataTransformer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Command to sync Hadith data from JSON files
 *
 * Supports importing hadith data from local JSON files with various formats.
 * Can process single files or entire directories (with recursive scanning).
 *
 * @example php artisan hadith:sync-json /path/to/file.json
 * @example php artisan hadith:sync-json /path/to/directory --recursive
 * @example php artisan hadith:sync-json /path/to/file.json --dry-run
 * @example php artisan hadith:sync-json /path/to/file.json --collection=nawawi40
 */
class SyncHadithFromJsonCommand extends Command
{
    use DisplaysHadithSyncOutput;

    protected $signature = 'hadith:sync-json
                            {path : Path to JSON file or directory containing JSON files}
                            {--dry-run : Simulate import without saving to database}
                            {--collection= : Override collection name detection}
                            {--recursive : Scan directory recursively for JSON files}';

    protected $description = 'استيراد بيانات الأحاديث من ملفات JSON - Import Hadith data from JSON files';

    private const EXIT_SUCCESS = 0;

    private const EXIT_ERROR = 1;

    public function __construct(
        private readonly JsonImportService $importService,
        private readonly JsonDataTransformer $transformer,
        private readonly JsonFileScanner $fileScanner,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->startTimer();
        $this->displayBanner(
            'استيراد الأحاديث من JSON - Hadith JSON Import',
            'Local File System Integration'
        );

        $path = $this->argument('path');
        $options = $this->getImportOptions();

        if ($options['dryRun']) {
            $this->displayDryRunWarning();
        }

        if (! $this->fileScanner->pathExists($path)) {
            $this->displayPathNotFound($path);

            return self::EXIT_ERROR;
        }

        return $this->processPath($path, $options);
    }

    /**
     * Get import options from command arguments
     */
    private function getImportOptions(): array
    {
        return [
            'dryRun' => (bool) $this->option('dry-run'),
            'collection' => $this->option('collection'),
            'recursive' => (bool) $this->option('recursive'),
        ];
    }

    /**
     * Process the given path (file or directory)
     */
    private function processPath(string $path, array $options): int
    {
        if ($this->fileScanner->isFile($path)) {
            return $this->importSingleFile($path, $options);
        }

        if ($this->fileScanner->isDirectory($path)) {
            return $this->importDirectory($path, $options);
        }

        $this->displayInvalidPath();

        return self::EXIT_ERROR;
    }

    /**
     * Import a single JSON file
     */
    private function importSingleFile(string $filePath, array $options): int
    {
        $filename = $this->fileScanner->getFilename($filePath);
        $this->displayProcessingFile($filename);

        // Validate file
        $validation = $this->fileScanner->validateFile($filePath);
        if (! $validation['valid']) {
            $this->displayImportFailed($validation['error']);

            return self::EXIT_ERROR;
        }

        try {
            $syncLog = $this->createSyncLog($filePath, $options);
            $progressBar = $this->createSimpleProgressBar();

            $result = $this->importService->importFromFile(
                $filePath,
                $syncLog,
                $options['dryRun']
            );

            $this->finishProgressBar($progressBar);

            $stats = SyncStatistics::fromArray($result)->withFileProcessed();
            $this->displayImportSummary($stats, $filename);

            if ($syncLog) {
                $this->displaySyncLogId($syncLog->id);
            }

            return self::EXIT_SUCCESS;

        } catch (\Throwable $e) {
            $this->handleImportError($e, $filePath);

            return self::EXIT_ERROR;
        }
    }

    /**
     * Import all JSON files in a directory
     */
    private function importDirectory(string $dirPath, array $options): int
    {
        $this->displayProcessingDirectory($dirPath, $options['recursive']);

        $jsonFiles = $this->fileScanner->scan($dirPath, $options['recursive']);

        if (empty($jsonFiles)) {
            $this->displayNoFilesFound();

            return self::EXIT_SUCCESS;
        }

        $this->displayFilesFound(count($jsonFiles));

        if (! $this->confirmBatchImport(count($jsonFiles))) {
            $this->info('تم الإلغاء - Cancelled');

            return self::EXIT_SUCCESS;
        }

        return $this->processBatch($jsonFiles, $options);
    }

    /**
     * Process batch of JSON files
     */
    private function processBatch(array $jsonFiles, array $options): int
    {
        $progressBar = $this->createCountProgressBar(count($jsonFiles));
        $stats = SyncStatistics::empty();

        foreach ($jsonFiles as $filePath) {
            $filename = $this->fileScanner->getFilename($filePath);
            $progressBar->setMessage("معالجة: {$filename} - Processing: {$filename}");

            $fileStats = $this->importFileQuietly($filePath, $options);
            $stats = $stats->merge($fileStats);

            $progressBar->advance();
        }

        $this->finishProgressBar($progressBar);
        $this->displayOverallSummary($stats);

        return $stats->hasErrors() ? self::EXIT_ERROR : self::EXIT_SUCCESS;
    }

    /**
     * Import a file without console output (for batch processing)
     */
    private function importFileQuietly(string $filePath, array $options): SyncStatistics
    {
        try {
            $collectionName = $options['collection']
                ?? $this->transformer->detectCollectionFromFilename($filePath)
                ?? 'unknown_'.uniqid();

            $syncLog = $this->createSyncLogForFile($filePath, $collectionName, $options);

            $result = $this->importService->importFromFile(
                $filePath,
                $syncLog,
                $options['dryRun']
            );

            return SyncStatistics::fromArray($result)->withFileProcessed();

        } catch (\Throwable $e) {
            $this->logImportError($e, $filePath);

            return SyncStatistics::empty()->withFileFailed();
        }
    }

    /**
     * Create sync log for single file import
     */
    private function createSyncLog(string $filePath, array $options): ?HadithSyncLog
    {
        if ($options['dryRun']) {
            return null;
        }

        $collectionName = $options['collection']
            ?? $this->fileScanner->getFilenameWithoutExtension($filePath);

        return HadithSyncLog::create([
            'collection_name' => $collectionName,
            'type' => 'json_import',
            'status' => HadithSyncLog::STATUS_IN_PROGRESS,
            'started_at' => now(),
            'metadata' => [
                'file' => $filePath,
                'dry_run' => $options['dryRun'],
                'collection_override' => $options['collection'],
                'command' => 'hadith:sync-json',
            ],
        ]);
    }

    /**
     * Create sync log for batch file import
     */
    private function createSyncLogForFile(
        string $filePath,
        string $collectionName,
        array $options
    ): ?HadithSyncLog {
        if ($options['dryRun']) {
            return null;
        }

        return HadithSyncLog::create([
            'collection_name' => $collectionName,
            'type' => 'json_import',
            'status' => HadithSyncLog::STATUS_IN_PROGRESS,
            'started_at' => now(),
            'metadata' => [
                'file' => $filePath,
                'dry_run' => $options['dryRun'],
                'command' => 'hadith:sync-json',
            ],
        ]);
    }

    /**
     * Handle import error with logging
     */
    private function handleImportError(\Throwable $e, string $filePath): void
    {
        $this->displayImportFailed($e->getMessage());
        $this->logImportError($e, $filePath);
    }

    /**
     * Log import error
     */
    private function logImportError(\Throwable $e, string $filePath): void
    {
        Log::error('JSON import failed', [
            'file' => $filePath,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }

    /**
     * Confirm batch import with user
     */
    private function confirmBatchImport(int $fileCount): bool
    {
        return $this->confirm(
            "هل تريد المتابعة في استيراد جميع الملفات ({$fileCount})؟ Proceed with importing all files?",
            true
        );
    }
}
