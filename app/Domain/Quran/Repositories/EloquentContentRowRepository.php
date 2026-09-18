<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

use App\Domain\Quran\DTOs\ContentRowData;
use App\Domain\Quran\DTOs\ImportReport;
use App\Domain\Quran\Enums\ResourceKind;
use App\Domain\Quran\Models\ChapterInfo;
use App\Domain\Quran\Models\QuranResource;
use App\Domain\Quran\Models\Tafsir;
use App\Domain\Quran\Models\Translation;
use App\Domain\Quran\Models\Verse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class EloquentContentRowRepository implements ContentRowRepositoryInterface
{
    public function __construct(private readonly ImportRepositoryInterface $bulk) {}

    public function paginate(QuranResource $resource, ?int $chapter, int $perPage): LengthAwarePaginator
    {
        $perPage = min(100, max(1, $perPage));

        if ($resource->kind === ResourceKind::CHAPTER_INFO) {
            return ChapterInfo::query()
                ->select(['id', 'resource_id', 'chapter_id', 'language_iso', 'text', 'short_text', 'source', 'is_locked', 'updated_at'])
                ->with('chapter:id,name_arabic,name_simple')
                ->where('resource_id', $resource->id)
                ->when($chapter !== null, fn (Builder $q) => $q->where('chapter_id', $chapter))
                ->orderBy('chapter_id')
                ->paginate($perPage)
                ->withQueryString();
        }

        $model = $this->verseModel($resource);
        $table = $model->getTable();

        return $model->newQuery()
            ->select(["{$table}.id", "{$table}.resource_id", "{$table}.verse_id", "{$table}.text", "{$table}.is_locked", "{$table}.updated_at",
                ...($resource->kind === ResourceKind::TRANSLATION ? ["{$table}.foot_notes"] : []), 'v.verse_key', 'v.chapter_id'])
            ->join('quran_verses as v', 'v.id', '=', "{$table}.verse_id")
            ->where("{$table}.resource_id", $resource->id)
            ->when($chapter !== null, fn (Builder $q) => $q->where('v.chapter_id', $chapter))
            ->orderBy("{$table}.verse_id")
            ->paginate($perPage)
            ->withQueryString();
    }

    public function find(QuranResource $resource, int $rowId): ?Model
    {
        return $this->modelFor($resource)->newQuery()->where('resource_id', $resource->id)->find($rowId);
    }

    public function save(QuranResource $resource, ContentRowData $data): Model
    {
        return DB::transaction(function () use ($resource, $data): Model {
            $model = $this->modelFor($resource);
            [$targetColumn, $targetId] = $this->target($resource, $data);

            $row = $model->newQuery()->firstOrNew(['resource_id' => $resource->id, $targetColumn => $targetId]);
            $row->fill($this->attributes($resource, $data))->save();
            $this->touchCount($resource, $model->getTable());

            return $row;
        });
    }

    public function update(QuranResource $resource, int $rowId, ContentRowData $data): Model
    {
        return DB::transaction(function () use ($resource, $rowId, $data): Model {
            $row = $this->modelFor($resource)->newQuery()->where('resource_id', $resource->id)->findOrFail($rowId);
            $row->fill($this->attributes($resource, $data))->save();

            return $row;
        });
    }

    public function delete(QuranResource $resource, int $rowId): void
    {
        $model = $this->modelFor($resource);
        $model->newQuery()->where('resource_id', $resource->id)->whereKey($rowId)->delete();
        $this->touchCount($resource, $model->getTable());
    }

    public function import(QuranResource $resource, iterable $rows): ImportReport
    {
        $report = new ImportReport('import:'.$resource->kind->value);
        $table = $this->modelFor($resource)->getTable();
        $isChapterInfo = $resource->kind === ResourceKind::CHAPTER_INFO;
        $verseIds = $isChapterInfo ? [] : DB::table('quran_verses')->pluck('id', 'verse_key')->all();

        $prepared = [];
        foreach ($rows as $data) {
            $report->expected++;
            if ($isChapterInfo) {
                $chapter = (int) $data->target;
                if ($chapter < 1 || $chapter > 114) {
                    $report->fail("chapter:{$data->target}", 'unknown chapter');

                    continue;
                }
                $prepared[] = ['resource_id' => $resource->id, 'chapter_id' => $chapter, 'language_iso' => $resource->language_iso,
                    'text' => $data->text, 'short_text' => $data->shortText, 'source' => $data->source, 'is_locked' => $data->isLocked];

                continue;
            }

            $verseId = $verseIds[(string) $data->target] ?? null;
            if ($verseId === null) {
                $report->fail('verse:'.$data->target, 'unknown verse key');

                continue;
            }
            $row = ['resource_id' => $resource->id, 'verse_id' => (int) $verseId, 'text' => $data->text, 'is_locked' => $data->isLocked];
            if ($resource->kind === ResourceKind::TRANSLATION) {
                $row['foot_notes'] = $data->footNotes === null ? null : json_encode($data->footNotes, JSON_UNESCAPED_UNICODE);
            }
            $prepared[] = $row;
        }

        $uniqueBy = $isChapterInfo ? ['resource_id', 'chapter_id'] : ['resource_id', 'verse_id'];
        $columns = array_values(array_diff(array_keys($prepared[0] ?? []), $uniqueBy));
        if ($prepared !== []) {
            $report->add($this->bulk->upsert($table, $prepared, $uniqueBy, $columns));
        }
        $this->touchCount($resource, $table);

        return $report;
    }

    /**
     * @return array{0: string, 1: int}
     */
    private function target(QuranResource $resource, ContentRowData $data): array
    {
        if ($resource->kind === ResourceKind::CHAPTER_INFO) {
            return ['chapter_id', (int) $data->target];
        }

        $verseId = Verse::query()->where('verse_key', (string) $data->target)->value('id');
        if ($verseId === null) {
            throw new InvalidArgumentException('Unknown verse key '.$data->target);
        }

        return ['verse_id', (int) $verseId];
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(QuranResource $resource, ContentRowData $data): array
    {
        return match ($resource->kind) {
            ResourceKind::TRANSLATION => ['text' => $data->text, 'foot_notes' => $data->footNotes, 'is_locked' => $data->isLocked],
            ResourceKind::TAFSIR => ['text' => $data->text, 'is_locked' => $data->isLocked],
            ResourceKind::CHAPTER_INFO => ['text' => $data->text, 'short_text' => $data->shortText, 'source' => $data->source,
                'language_iso' => $resource->language_iso, 'is_locked' => $data->isLocked],
            default => throw new InvalidArgumentException("{$resource->kind->value} has no content rows"),
        };
    }

    private function modelFor(QuranResource $resource): Model
    {
        return match ($resource->kind) {
            ResourceKind::TRANSLATION => new Translation,
            ResourceKind::TAFSIR => new Tafsir,
            ResourceKind::CHAPTER_INFO => new ChapterInfo,
            default => throw new InvalidArgumentException("{$resource->kind->value} has no content rows"),
        };
    }

    private function verseModel(QuranResource $resource): Model
    {
        $model = $this->modelFor($resource);
        if ($model instanceof ChapterInfo) {
            throw new InvalidArgumentException('chapter infos are not per verse');
        }

        return $model;
    }

    private function touchCount(QuranResource $resource, string $table): void
    {
        DB::table('quran_resources')->where('id', $resource->id)
            ->update(['records_count' => DB::table($table)->where('resource_id', $resource->id)->count()]);
    }
}
