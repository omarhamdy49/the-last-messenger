<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Importers;

use App\Services\Json\JsonDataTransformer;
use InvalidArgumentException;

/**
 * HadithJsonParser
 *
 * Responsible for:
 *   - Validating that a file path points to a readable, well-formed JSON file.
 *   - Reading and JSON-decoding the file content.
 *   - Delegating format detection and data transformation to JsonDataTransformer.
 *   - Exposing the detected format string after a successful parse.
 *
 * Returns the normalised envelope array produced by JsonDataTransformer::transform().
 * The caller never needs to touch file I/O or raw JSON.
 */
class HadithJsonParser
{
    /** Maximum allowed file size: 100 MB */
    private const MAX_FILE_SIZE_BYTES = 100 * 1024 * 1024;

    private string $detectedFormat = '';

    public function __construct(
        private readonly JsonDataTransformer $transformer,
    ) {}

    // =========================================================================
    // PUBLIC API
    // =========================================================================

    /**
     * Validate, read, decode, and transform a JSON file.
     *
     * @param  string  $filePath  Absolute path to the JSON file.
     * @return array Normalised transformation envelope from JsonDataTransformer::transform().
     *
     * @throws InvalidArgumentException When the file is invalid or the JSON is malformed.
     */
    public function parse(string $filePath): array
    {
        $jsonData = $this->decode($filePath);

        // Let the transformer auto-detect the structure and produce the envelope.
        $transformed = $this->transformer->transform($jsonData);
        $this->attachSourceFile($transformed, $filePath);

        // Capture the detected format for callers that need it.
        $this->detectedFormat = $transformed['type'] ?? 'unknown';

        return $transformed;
    }

    public function decode(string $filePath): array
    {
        $this->guardFile($filePath);
        $jsonData = json_decode((string) file_get_contents($filePath), true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($jsonData)) {
            throw new InvalidArgumentException('Invalid JSON syntax: '.json_last_error_msg());
        }

        return $jsonData;
    }

    /**
     * Return the format string detected by the last call to parse().
     *
     * Returns an empty string if parse() has not been called yet.
     */
    public function detectedFormat(): string
    {
        return $this->detectedFormat;
    }

    /**
     * Validate that a file exists, is readable, has a .json extension,
     * is within the size limit, and contains valid JSON accepted by the transformer.
     *
     * This method is intentionally kept public so callers (e.g. console commands)
     * can pre-validate before committing to a full parse.
     */
    public function validateFile(string $filePath): bool
    {
        if (! file_exists($filePath) || ! is_readable($filePath)) {
            return false;
        }

        if (pathinfo($filePath, PATHINFO_EXTENSION) !== 'json') {
            return false;
        }

        if (filesize($filePath) > self::MAX_FILE_SIZE_BYTES) {
            return false;
        }

        $jsonContent = file_get_contents($filePath);
        $jsonData = json_decode((string) $jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }

        return $this->transformer->isValidData($jsonData);
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /**
     * Throw InvalidArgumentException when the file does not pass validation.
     */
    private function guardFile(string $filePath): void
    {
        if (! $this->validateFile($filePath)) {
            throw new InvalidArgumentException("Invalid JSON file: {$filePath}");
        }
    }

    private function attachSourceFile(array &$transformed, string $filePath): void
    {
        if (($transformed['type'] ?? null) === 'single_hadith') {
            if (! isset($transformed['records']) || ! is_array($transformed['records'])) {
                return;
            }

            foreach ($transformed['records'] as &$record) {
                $record['source_file'] = $filePath;
            }
            unset($record);

            return;
        }

        if (($transformed['type'] ?? null) === 'collection_based') {
            if (! isset($transformed['hadiths']) || ! is_array($transformed['hadiths'])) {
                return;
            }

            foreach ($transformed['hadiths'] as &$record) {
                $record['source_file'] = $filePath;
            }
            unset($record);
        }
    }
}
