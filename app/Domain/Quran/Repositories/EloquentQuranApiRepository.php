<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

use App\Domain\Quran\Models\MushafPage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class EloquentQuranApiRepository implements QuranApiRepositoryInterface
{
    private const PAGE_COLUMNS = ['id', 'mushaf_id', 'page_number', 'first_verse_id', 'last_verse_id', 'verses_count', 'verse_mapping'];

    private const AXES = ['chapter_id', 'juz_number', 'hizb_number', 'rub_el_hizb_number', 'manzil_number', 'ruku_number', 'page_number', 'verse_key'];

    public function pages(int $mushafId): Collection
    {
        return MushafPage::query()->select(self::PAGE_COLUMNS)->where('mushaf_id', $mushafId)->orderBy('page_number')->get();
    }

    public function pagesInRange(int $mushafId, int $fromVerseId, int $toVerseId): Collection
    {
        return MushafPage::query()->select(self::PAGE_COLUMNS)
            ->where('mushaf_id', $mushafId)
            ->where('first_verse_id', '<=', max($fromVerseId, $toVerseId))
            ->where('last_verse_id', '>=', min($fromVerseId, $toVerseId))
            ->orderBy('page_number')
            ->get();
    }

    public function verseIdRange(array $axisFilter): ?array
    {
        $row = DB::table('quran_verses')->where($this->filter($axisFilter, ''))->selectRaw('MIN(id) as lo, MAX(id) as hi')->first();

        return $row === null || $row->lo === null ? null : [(int) $row->lo, (int) $row->hi];
    }

    public function translationRows(int $resourceId, array $axisFilter): Collection
    {
        return $this->rows('quran_translations', $resourceId, $axisFilter, ['t.foot_notes'])->get();
    }

    public function tafsirRows(int $resourceId, array $axisFilter, int $page, int $perPage): LengthAwarePaginator
    {
        return $this->rows('quran_tafsirs', $resourceId, $axisFilter, [])
            ->paginate(max(1, min(50, $perPage)), ['*'], 'page', max(1, $page));
    }

    public function footNote(string $id): ?array
    {
        if (preg_match('/^\d{1,12}$/', $id) !== 1) {
            return null;
        }

        $row = DB::table('quran_translations')
            ->select(['resource_id', DB::raw('JSON_UNQUOTE(JSON_EXTRACT(foot_notes, ?)) as note')])
            ->addBinding('$."'.$id.'"', 'select')
            ->whereNotNull('foot_notes')
            ->whereRaw('JSON_CONTAINS_PATH(foot_notes, \'one\', ?)', ['$."'.$id.'"'])
            ->first();

        return $row === null ? null : ['id' => $id, 'text' => (string) $row->note, 'resource_id' => (int) $row->resource_id];
    }

    public function verseKeys(array $verseIds): array
    {
        return DB::table('quran_verses')->whereIn('id', array_map('intval', $verseIds))->pluck('verse_key', 'id')
            ->map(static fn (mixed $v): string => (string) $v)->all();
    }

    /**
     * @param  array<string, int|string>  $axisFilter
     * @param  list<string>  $extra
     */
    private function rows(string $table, int $resourceId, array $axisFilter, array $extra): Builder
    {
        return DB::table($table.' as t')
            ->join('quran_verses as v', 'v.id', '=', 't.verse_id')
            ->select(['t.id', 't.verse_id', 'v.verse_key', 't.text', ...$extra])
            ->where('t.resource_id', $resourceId)
            ->where($this->filter($axisFilter, 'v.'))
            ->orderBy('t.verse_id');
    }

    /**
     * @param  array<string, int|string>  $axisFilter
     * @return array<string, int|string>
     */
    private function filter(array $axisFilter, string $prefix): array
    {
        $filter = [];
        foreach (array_intersect_key($axisFilter, array_flip(self::AXES)) as $column => $value) {
            $filter[$prefix.$column] = $value;
        }

        return $filter;
    }
}
