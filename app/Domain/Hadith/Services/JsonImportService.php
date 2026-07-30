<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Services;

use App\Domain\Hadith\Importers\HadithCollectionPortalStore;
use App\Domain\Hadith\Importers\HadithImportMapper;
use App\Domain\Hadith\Importers\HadithImportPersister;
use App\Domain\Hadith\Importers\HadithJsonParser;
use App\Domain\Hadith\Models\HadithSyncLog;
use Exception;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * JsonImportService — Orchestrator
 *
 * Coordinates the three focused import classes:
 *   1. HadithJsonParser   — validates, reads, and transforms the JSON file.
 *   2. HadithImportMapper — converts the transformation envelope into a
 *                           persister-ready payload with safe defaults.
 *   3. HadithImportPersister — performs all database writes.
 *
 * Public method signatures are unchanged from the original implementation
 * so existing callers (SyncHadithFromJsonCommand, ImportHadithsHandler) need
 * no modifications.
 *
 * @see HadithJsonParser
 * @see HadithImportMapper
 * @see HadithImportPersister
 */
class JsonImportService
{
    public function __construct(
        private readonly HadithJsonParser $parser,
        private readonly HadithImportMapper $mapper,
        private readonly HadithImportPersister $persister,
        private readonly HadithCollectionPortalStore $portalStore,
    ) {}

    // =========================================================================
    // PUBLIC API (unchanged signatures)
    // =========================================================================

    /**
     * Import hadith data from a JSON file (auto-detects format).
     *
     * @param  string  $filePath  Absolute path to the JSON file.
     * @param  HadithSyncLog|null  $syncLog  Optional sync log for progress tracking.
     * @param  bool  $dryRun  When true no database writes occur (validation only).
     * @return array Summary statistics keyed by entity type.
     *
     * @throws InvalidArgumentException When the file is invalid.
     * @throws Exception On import failure (re-thrown after logging).
     */
    public function importFromFile(string $filePath, ?HadithSyncLog $syncLog = null, bool $dryRun = false): array
    {
        Log::info('Starting JSON import from file', [
            'file' => $filePath,
            'dry_run' => $dryRun,
        ]);

        try {
            if ($syncLog && ! $dryRun) {
                $syncLog->markStarted();
            }

            // Step 1 — Parse: validate + decode + transform.
            $transformed = $this->parser->parse($filePath);
            $format = $this->parser->detectedFormat();

            // Step 2 — Map: normalise into a persister-ready payload.
            $payload = $this->mapper->map($transformed, $format);

            // Step 3 — Persist.
            $summary = match ($payload['type']) {
                'single_hadith' => $this->persister->persistSingleHadith($payload, $syncLog, $dryRun),
                'collection_based' => $this->persister->persistCollectionBased($payload, $syncLog, $dryRun),
                default => throw new InvalidArgumentException("Unknown import type: {$payload['type']}"),
            };

            if ($syncLog && ! $dryRun) {
                $syncLog->markCompleted();
            }

            Log::info('Completed JSON import from file', [
                'file' => $filePath,
                'type' => $format,
                'summary' => $summary,
            ]);

            return $summary;
        } catch (Exception $e) {
            Log::error('Failed to import JSON file', [
                'file' => $filePath,
                'error' => $e->getMessage(),
            ]);

            if ($syncLog && ! $dryRun) {
                $syncLog->markFailed($e->getMessage(), ['file' => $filePath]);
            }

            throw $e;
        }
    }

    /**
     * Validate a JSON file without importing.
     *
     * Delegates directly to HadithJsonParser so the public API surface of this
     * service remains stable for callers that relied on the original method.
     */
    public function validateJsonFile(string $filePath): bool
    {
        return $this->parser->validateFile($filePath);
    }

    public function rebuildCollection(
        string $collection,
        string $sourceRoot,
        string $portalRoot,
    ): array {
        if (preg_match('/^[a-z0-9_]+$/', $collection) !== 1) {
            throw new InvalidArgumentException('Collection must be a lowercase identifier');
        }

        $sourceDirectory = rtrim($sourceRoot, '/').'/'.$collection;
        $files = $this->sourceFiles($sourceDirectory);
        $sources = $this->decodeSources($files);
        $coordinates = $this->mapper->relatedVerseCoordinates($sources);
        $quranVerseIds = $this->persister->quranVerseIds($coordinates);
        $normalized = $this->mapper->mapRebuildCollection($collection, $sources, $quranVerseIds);
        $portal = $this->portalStore->write($portalRoot, $normalized);
        $package = $this->portalStore->read($portalRoot, $collection);
        $import = $this->persister->persistRebuildCollection($package);

        return [
            'collection' => $collection,
            'source_directory' => $sourceDirectory,
            'files_read' => count($files),
            'portal' => $portal,
            'mapping' => $package['report'],
            'rows_written' => $import['rows'],
            'database' => $import['database'],
        ];
    }

    private function sourceFiles(string $sourceDirectory): array
    {
        $files = glob($sourceDirectory.'/*.json') ?: [];
        sort($files, SORT_STRING);

        return $files !== []
            ? $files
            : throw new InvalidArgumentException("No JSON files found in {$sourceDirectory}");
    }

    private function decodeSources(array $files): array
    {
        return array_map(fn (string $path): array => [
            'path' => $path,
            'json' => $this->parser->decode($path),
        ], $files);
    }
}
