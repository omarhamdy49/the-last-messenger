<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use App\Domain\Quran\DTOs\ContentRowData;
use App\Domain\Quran\DTOs\ImportReport;
use App\Domain\Quran\Models\QuranResource;
use App\Domain\Quran\Repositories\ContentRowRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use JsonException;

/**
 * Editor CRUD and file import for translation, tafsir and chapter-info rows.
 */
final class ContentRowService
{
    public function __construct(private readonly ContentRowRepositoryInterface $rows) {}

    public function paginate(QuranResource $resource, ?int $chapter, int $perPage): LengthAwarePaginator
    {
        return $this->rows->paginate($resource, $chapter, $perPage);
    }

    public function find(QuranResource $resource, int $rowId): ?Model
    {
        return $this->rows->find($resource, $rowId);
    }

    public function save(QuranResource $resource, ContentRowData $data): Model
    {
        $row = $this->rows->save($resource, $data);
        QuranCacheKeys::flushContent();

        return $row;
    }

    public function update(QuranResource $resource, int $rowId, ContentRowData $data): Model
    {
        $row = $this->rows->update($resource, $rowId, $data);
        QuranCacheKeys::flushContent();

        return $row;
    }

    public function delete(QuranResource $resource, int $rowId): void
    {
        $this->rows->delete($resource, $rowId);
        QuranCacheKeys::flushContent();
    }

    /**
     * Import a JSON array (`[{verse_key|chapter_id, text, foot_notes?, short_text?, source?}]`)
     * or a CSV with a header row (`verse_key,text` / `chapter_id,text`).
     */
    public function importFile(QuranResource $resource, string $path, string $extension): ImportReport
    {
        $contents = (string) file_get_contents($path);
        $rows = strtolower($extension) === 'csv' ? $this->fromCsv($contents) : $this->fromJson($contents);

        $report = $this->rows->import($resource, $rows);
        QuranCacheKeys::flushContent();

        return $report;
    }

    /**
     * @return list<ContentRowData>
     */
    private function fromJson(string $contents): array
    {
        try {
            $items = json_decode($contents, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new InvalidArgumentException('The file is not valid JSON.');
        }

        if (! is_array($items) || ! array_is_list($items)) {
            throw new InvalidArgumentException('The JSON file must contain a list of rows.');
        }

        $rows = [];
        foreach ($items as $item) {
            if (! is_array($item) || ! isset($item['text']) || (! isset($item['verse_key']) && ! isset($item['chapter_id']))) {
                continue;
            }
            $footNotes = null;
            if (isset($item['foot_notes']) && is_array($item['foot_notes'])) {
                $footNotes = array_map('strval', $item['foot_notes']);
            }
            $rows[] = new ContentRowData(
                target: isset($item['verse_key']) ? (string) $item['verse_key'] : (int) $item['chapter_id'],
                text: (string) $item['text'],
                footNotes: $footNotes,
                shortText: isset($item['short_text']) ? (string) $item['short_text'] : null,
                source: isset($item['source']) ? (string) $item['source'] : null,
            );
        }

        return $rows;
    }

    /**
     * @return list<ContentRowData>
     */
    private function fromCsv(string $contents): array
    {
        $lines = preg_split("/\r\n|\n|\r/", trim($contents)) ?: [];
        $header = str_getcsv((string) array_shift($lines));
        $targetColumn = in_array('verse_key', $header, true) ? 'verse_key' : 'chapter_id';
        $targetIndex = array_search($targetColumn, $header, true);
        $textIndex = array_search('text', $header, true);

        if ($targetIndex === false || $textIndex === false) {
            throw new InvalidArgumentException('The CSV header must contain verse_key (or chapter_id) and text.');
        }

        $rows = [];
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $cells = str_getcsv($line);
            $rows[] = new ContentRowData(
                target: $targetColumn === 'verse_key' ? (string) ($cells[$targetIndex] ?? '') : (int) ($cells[$targetIndex] ?? 0),
                text: (string) ($cells[$textIndex] ?? ''),
            );
        }

        return $rows;
    }
}
