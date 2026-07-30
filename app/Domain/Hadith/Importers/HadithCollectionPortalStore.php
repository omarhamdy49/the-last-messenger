<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Importers;

use RuntimeException;

class HadithCollectionPortalStore
{
    public function write(string $portalRoot, array $package): array
    {
        $directory = $this->collectionDirectory($portalRoot, $package['collection']);
        $this->prepareDirectory($directory);
        $manifest = [
            'format_version' => 1,
            'collection' => $package['collection'],
            'generated_at' => now()->toIso8601String(),
            'conventions' => [
                'ids_preserved' => true,
                'insert_order' => HadithCollectionNormalizer::TABLES,
                'lowercased' => 'identifier, enum, status, locale, method and keyword values only',
                'part_files' => 'each file is {table, part, parts, count, rows:[...]}',
            ],
            'report' => $package['report'],
            'tables' => [],
        ];

        foreach (HadithCollectionNormalizer::TABLES as $table) {
            $filename = "{$table}.json";
            $path = "{$directory}/{$filename}";
            $this->writeEnvelope($path, $table, $package['tables'][$table]);
            $manifest['tables'][$table] = [
                'rows' => count($package['tables'][$table]),
                'file' => $filename,
                'sha256' => hash_file('sha256', $path),
            ];
        }

        $manifestPath = "{$directory}/manifest.json";
        $this->writeJson($manifestPath, $manifest);

        return [
            'directory' => $directory,
            'files_written' => count(HadithCollectionNormalizer::TABLES) + 1,
            'manifest' => $manifestPath,
        ];
    }

    public function read(string $portalRoot, string $collection): array
    {
        $directory = $this->collectionDirectory($portalRoot, $collection);
        $manifestPath = "{$directory}/manifest.json";
        $manifest = $this->readJson($manifestPath);
        $tables = [];

        if (($manifest['collection'] ?? null) !== $collection) {
            throw new RuntimeException("Portal manifest collection does not match {$collection}");
        }

        foreach (HadithCollectionNormalizer::TABLES as $table) {
            $meta = $manifest['tables'][$table] ?? null;
            if (! is_array($meta)) {
                throw new RuntimeException("Portal manifest is missing {$table}");
            }

            $path = "{$directory}/{$meta['file']}";
            if (hash_file('sha256', $path) !== $meta['sha256']) {
                throw new RuntimeException("Portal checksum mismatch for {$table}");
            }

            $envelope = $this->readJson($path);
            if (($envelope['table'] ?? null) !== $table || ! is_array($envelope['rows'] ?? null)) {
                throw new RuntimeException("Invalid portal envelope for {$table}");
            }
            if (count($envelope['rows']) !== $meta['rows']) {
                throw new RuntimeException("Portal row count mismatch for {$table}");
            }

            $tables[$table] = $envelope['rows'];
        }

        return [
            'collection' => $collection,
            'tables' => $tables,
            'report' => $manifest['report'],
            'manifest' => $manifest,
        ];
    }

    private function collectionDirectory(string $portalRoot, string $collection): string
    {
        return rtrim($portalRoot, '/').'/'.$collection;
    }

    private function prepareDirectory(string $directory): void
    {
        if (! is_dir($directory) && ! mkdir($directory, 0o755, true) && ! is_dir($directory)) {
            throw new RuntimeException("Cannot create {$directory}");
        }

        foreach (glob($directory.'/*.json') ?: [] as $stale) {
            unlink($stale);
        }
    }

    private function writeEnvelope(string $path, string $table, array $rows): void
    {
        $this->writeJson($path, [
            'table' => $table,
            'part' => 1,
            'parts' => 1,
            'count' => count($rows),
            'rows' => $rows,
        ]);
    }

    private function writeJson(string $path, array $payload): void
    {
        $json = json_encode(
            $payload,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT,
        );

        if (file_put_contents($path, $json."\n") === false) {
            throw new RuntimeException("Cannot write {$path}");
        }
    }

    private function readJson(string $path): array
    {
        if (! is_file($path)) {
            throw new RuntimeException("Missing portal file {$path}");
        }

        return json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
    }
}
