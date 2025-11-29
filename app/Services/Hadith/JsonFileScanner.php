<?php

declare(strict_types=1);

namespace App\Services\Hadith;

use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Service for scanning and validating JSON files for hadith import
 *
 * Handles directory traversal, file validation, and path resolution
 * for the hadith JSON import process.
 */
final class JsonFileScanner
{
    private const JSON_EXTENSION = 'json';
    private const MAX_FILE_SIZE_MB = 100;

    /**
     * Scan a path (file or directory) for JSON files
     *
     * @param string $path File path or directory path
     * @param bool $recursive Scan subdirectories if path is a directory
     * @return array<string> Array of absolute paths to JSON files
     *
     * @throws InvalidArgumentException If path does not exist
     */
    public function scan(string $path, bool $recursive = false): array
    {
        $this->validatePathExists($path);

        if (is_file($path)) {
            return $this->isJsonFile($path) ? [$path] : [];
        }

        return $this->scanDirectory($path, $recursive);
    }

    /**
     * Check if a path exists
     */
    public function pathExists(string $path): bool
    {
        return file_exists($path);
    }

    /**
     * Check if a path is a file
     */
    public function isFile(string $path): bool
    {
        return is_file($path);
    }

    /**
     * Check if a path is a directory
     */
    public function isDirectory(string $path): bool
    {
        return is_dir($path);
    }

    /**
     * Check if a file is a valid JSON file
     */
    public function isJsonFile(string $path): bool
    {
        if (!is_file($path)) {
            return false;
        }

        return strtolower(pathinfo($path, PATHINFO_EXTENSION)) === self::JSON_EXTENSION;
    }

    /**
     * Validate a JSON file for import
     *
     * Checks:
     * - File exists and is readable
     * - File has .json extension
     * - File size is within limits
     * - File contains valid JSON
     *
     * @param string $path Path to JSON file
     * @return array{valid: bool, error: string|null}
     */
    public function validateFile(string $path): array
    {
        if (!file_exists($path)) {
            return ['valid' => false, 'error' => 'File does not exist'];
        }

        if (!is_readable($path)) {
            return ['valid' => false, 'error' => 'File is not readable'];
        }

        if (!$this->isJsonFile($path)) {
            return ['valid' => false, 'error' => 'File is not a JSON file'];
        }

        $fileSizeMb = filesize($path) / (1024 * 1024);
        if ($fileSizeMb > self::MAX_FILE_SIZE_MB) {
            return [
                'valid' => false,
                'error' => sprintf('File exceeds maximum size of %dMB', self::MAX_FILE_SIZE_MB),
            ];
        }

        // Validate JSON syntax
        $content = file_get_contents($path);
        json_decode($content);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'valid' => false,
                'error' => 'Invalid JSON syntax: ' . json_last_error_msg(),
            ];
        }

        return ['valid' => true, 'error' => null];
    }

    /**
     * Get the filename from a path
     */
    public function getFilename(string $path): string
    {
        return basename($path);
    }

    /**
     * Get filename without extension
     */
    public function getFilenameWithoutExtension(string $path): string
    {
        return pathinfo($path, PATHINFO_FILENAME);
    }

    /**
     * Scan directory for JSON files
     *
     * @param string $dirPath Directory path
     * @param bool $recursive Include subdirectories
     * @return array<string> Array of file paths
     */
    private function scanDirectory(string $dirPath, bool $recursive): array
    {
        $jsonFiles = [];

        if ($recursive) {
            $jsonFiles = $this->scanRecursively($dirPath);
        } else {
            $jsonFiles = $this->scanFlat($dirPath);
        }

        // Sort by filename for consistent ordering
        sort($jsonFiles);

        return $jsonFiles;
    }

    /**
     * Recursively scan directory
     */
    private function scanRecursively(string $dirPath): array
    {
        $jsonFiles = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $dirPath,
                RecursiveDirectoryIterator::SKIP_DOTS
            ),
            RecursiveIteratorIterator::SELF_FIRST
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile() && $this->hasJsonExtension($file)) {
                $jsonFiles[] = $file->getPathname();
            }
        }

        return $jsonFiles;
    }

    /**
     * Scan directory without recursion
     */
    private function scanFlat(string $dirPath): array
    {
        $jsonFiles = [];
        $files = scandir($dirPath);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $filePath = $dirPath . DIRECTORY_SEPARATOR . $file;

            if (is_file($filePath) && $this->isJsonFile($filePath)) {
                $jsonFiles[] = $filePath;
            }
        }

        return $jsonFiles;
    }

    /**
     * Check if SplFileInfo has JSON extension
     */
    private function hasJsonExtension(SplFileInfo $file): bool
    {
        return strtolower($file->getExtension()) === self::JSON_EXTENSION;
    }

    /**
     * Validate that path exists
     *
     * @throws InvalidArgumentException
     */
    private function validatePathExists(string $path): void
    {
        if (!file_exists($path)) {
            throw new InvalidArgumentException("Path does not exist: {$path}");
        }
    }
}
