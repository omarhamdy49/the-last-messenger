<?php

declare(strict_types=1);

namespace App\Domain\Shared\Contracts;

interface BulkOperationHandlerInterface
{
    /**
     * The module key this handler supports (e.g. 'articles', 'hadith', 'quran_verses').
     * Must match the module string stored on BulkOperation records.
     */
    public function getSupportedModule(): string;

    /**
     * Execute a bulk operation against a set of entity IDs.
     * Delegates all progress tracking to the caller via the returned counts.
     *
     * @param  string  $operationType  One of the BulkOperationTypeEnum string values
     * @param  int[]  $ids
     * @param  array<string, mixed>  $parameters  Operation-specific options (fields, tag_ids, etc.)
     * @return array{successCount: int, failedCount: int, errors: array<int, string>}
     */
    public function handle(string $operationType, array $ids, array $parameters = []): array;

    /**
     * Return a preview of entities that would be affected, without modifying anything.
     *
     * @param  int[]  $ids
     * @return array<int, array{id: int, title: string}>
     */
    public function preview(array $ids): array;

    /**
     * Export the given entity IDs in the requested format.
     *
     * @param  int[]  $ids
     * @param  string  $format  'json' or 'csv'
     * @return string Serialized export content
     */
    public function export(array $ids, string $format): string;
}
