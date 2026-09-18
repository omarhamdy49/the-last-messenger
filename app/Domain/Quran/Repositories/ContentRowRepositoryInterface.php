<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

use App\Domain\Quran\DTOs\ContentRowData;
use App\Domain\Quran\DTOs\ImportReport;
use App\Domain\Quran\Models\QuranResource;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

/**
 * Rows of translation, tafsir and chapter-info resources.
 */
interface ContentRowRepositoryInterface
{
    public function paginate(QuranResource $resource, ?int $chapter, int $perPage): LengthAwarePaginator;

    public function find(QuranResource $resource, int $rowId): ?Model;

    /**
     * Create or replace the row for the target (verse key / chapter).
     */
    public function save(QuranResource $resource, ContentRowData $data): Model;

    public function update(QuranResource $resource, int $rowId, ContentRowData $data): Model;

    public function delete(QuranResource $resource, int $rowId): void;

    /**
     * Bulk upsert rows parsed from an uploaded file. Unknown targets are itemised.
     *
     * @param  iterable<ContentRowData>  $rows
     */
    public function import(QuranResource $resource, iterable $rows): ImportReport;
}
