<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

use App\Domain\Quran\DTOs\ChapterData;
use App\Domain\Quran\Enums\RevelationPlace;
use App\Domain\Quran\Models\Chapter;
use App\Domain\Quran\Services\QuranCacheKeys;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class EloquentChapterRepository implements ChapterRepositoryInterface
{
    private const COLUMNS = [
        'id', 'revelation_place', 'revelation_order', 'bismillah_pre', 'name_simple', 'name_complex', 'name_arabic',
        'verses_count', 'first_page', 'last_page', 'revelation_location_id', 'cover_image', 'is_published',
    ];

    public function all(bool $publishedOnly = true): Collection
    {
        $key = QuranCacheKeys::chapters($publishedOnly ? 'published' : 'all');

        return Cache::remember($key, 3600, fn (): Collection => Chapter::query()
            ->select(self::COLUMNS)
            ->with('translations:id,chapter_id,locale,name')
            ->when($publishedOnly, fn (Builder $q) => $q->published())
            ->orderBy('id')
            ->get());
    }

    public function find(int $id): ?Chapter
    {
        return Chapter::query()->select(self::COLUMNS)->with('translations:id,chapter_id,locale,name')->find($id);
    }

    public function firstJuzMap(): array
    {
        return Cache::remember(QuranCacheKeys::firstJuzMap(), 86400, static fn (): array => DB::table('quran_verses')
            ->where('verse_number', 1)
            ->pluck('juz_number', 'chapter_id')
            ->map(static fn (mixed $v): int => (int) $v)
            ->all());
    }

    public function info(int $chapter, string $locale, ?int $resourceId = null): ?array
    {
        $row = DB::table('quran_chapter_infos as i')
            ->join('quran_resources as r', 'r.id', '=', 'i.resource_id')
            ->select(['i.text', 'i.short_text', 'i.source', 'i.language_iso', 'r.id as resource_id', 'r.name as resource_name', 'r.language_iso as resource_language'])
            ->where('i.chapter_id', $chapter)
            ->where('r.is_active', true)
            ->when($resourceId !== null, fn ($q) => $q->where('r.id', $resourceId))
            ->orderByRaw('CASE WHEN COALESCE(i.language_iso, r.language_iso) = ? THEN 0 WHEN COALESCE(i.language_iso, r.language_iso) = ? THEN 1 ELSE 2 END', [$locale, 'ar'])
            ->orderByDesc('r.is_default')
            ->orderBy('r.display_order')
            ->first();

        return $row === null ? null : [
            'text' => (string) $row->text,
            'short_text' => $row->short_text === null ? null : (string) $row->short_text,
            'source' => $row->source === null ? null : (string) $row->source,
            'language_iso' => $row->language_iso ?? $row->resource_language,
            'resource_id' => (int) $row->resource_id,
            'resource_name' => (string) $row->resource_name,
        ];
    }

    public function infos(int $chapter): array
    {
        return DB::table('quran_chapter_infos as i')
            ->join('quran_resources as r', 'r.id', '=', 'i.resource_id')
            ->select(['i.text', 'i.short_text', 'i.source', 'i.language_iso', 'r.name as resource_name', 'r.language_iso as resource_language', 'r.is_default', 'r.display_order'])
            ->where('i.chapter_id', $chapter)
            ->where('r.is_active', true)
            ->orderByDesc('r.is_default')
            ->orderBy('r.display_order')
            ->get()
            // One entry per language: the first row for a language is the best source
            // for it, given the ordering above.
            ->groupBy(fn (object $row): string => (string) ($row->language_iso ?? $row->resource_language ?? ''))
            ->map(fn ($rows): array => [
                'language' => (string) ($rows->first()->language_iso ?? $rows->first()->resource_language ?? ''),
                'text' => (string) $rows->first()->text,
                'source' => $rows->first()->source === null ? null : (string) $rows->first()->source,
                'resource' => (string) $rows->first()->resource_name,
            ])
            ->values()
            ->all();
    }

    public function findForAdmin(int $id): ?Chapter
    {
        return Chapter::query()
            ->select(self::COLUMNS)
            ->with([
                'translations:id,chapter_id,locale,name',
                'location.translations',
                'infos' => fn ($q) => $q->select(['id', 'resource_id', 'chapter_id', 'language_iso', 'short_text', 'is_locked', 'updated_at']),
                'infos.resource:id,name,language_iso,is_active',
                'audioFiles' => fn ($q) => $q->select(['id', 'resource_id', 'chapter_id', 'url', 'duration_ms', 'has_timestamps', 'is_local']),
                'audioFiles.resource:id,name,is_active',
            ])
            ->find($id);
    }

    public function paginateAdmin(array $filters, int $perPage): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));

        return Chapter::query()
            ->select(self::COLUMNS)
            ->with('translations:id,chapter_id,locale,name')
            ->when($search !== '', fn (Builder $q) => $this->applySearch($q, $search))
            ->when(in_array($filters['revelation_place'] ?? null, RevelationPlace::values(), true),
                fn (Builder $q) => $q->where('revelation_place', $filters['revelation_place']))
            ->when(($filters['published'] ?? '') !== '', fn (Builder $q) => $q->where('is_published', $filters['published'] === '1'))
            ->orderBy('id')
            ->paginate(min(114, max(1, $perPage)))
            ->withQueryString();
    }

    public function update(int $id, ChapterData $data): Chapter
    {
        return DB::transaction(function () use ($id, $data): Chapter {
            $chapter = Chapter::query()->findOrFail($id);

            $attributes = ['is_published' => $data->isPublished, 'revelation_location_id' => $data->locationId];
            if ($data->removeCover) {
                $attributes['cover_image'] = null;
            } elseif ($data->coverPath !== null) {
                $attributes['cover_image'] = $data->coverPath;
            }
            $chapter->update($attributes);

            // Arabic always mirrors QF's name_arabic unless an editor supplies one.
            $names = array_filter($data->name, static fn (?string $v): bool => $v !== null && trim($v) !== '');
            if ($names !== []) {
                $chapter->syncTranslations(['name' => $names]);
            }

            return $chapter->fresh(['translations']) ?? $chapter;
        });
    }

    public function search(string $term, int $limit): Collection
    {
        return Chapter::query()
            ->select(self::COLUMNS)
            ->with('translations:id,chapter_id,locale,name')
            ->tap(fn (Builder $q) => $this->applySearch($q, $term))
            ->orderBy('id')
            ->limit(min(50, max(1, $limit)))
            ->get();
    }

    public function stats(): array
    {
        $row = DB::table('quran_chapters')->selectRaw(
            'COUNT(*) as total, SUM(revelation_place = ?) as makkah, SUM(revelation_place = ?) as madinah, COALESCE(SUM(verses_count), 0) as verses, SUM(is_published) as published',
            [RevelationPlace::MAKKAH->value, RevelationPlace::MADINAH->value],
        )->first();

        return [
            'total' => (int) ($row->total ?? 0),
            'makkah' => (int) ($row->makkah ?? 0),
            'madinah' => (int) ($row->madinah ?? 0),
            'verses' => (int) ($row->verses ?? 0),
            'published' => (int) ($row->published ?? 0),
        ];
    }

    /**
     * @param  Builder<Chapter>  $query
     */
    private function applySearch(Builder $query, string $term): void
    {
        $term = mb_substr(trim($term), 0, 100);
        $query->where(function (Builder $q) use ($term): void {
            if (ctype_digit($term)) {
                $q->orWhere('id', (int) $term);
            }
            // ponytail: contains-search over 114 rows; "Fatihah" must find "Al-Fatihah".
            $q->orWhere('name_arabic', 'like', '%'.$term.'%')
                ->orWhere('name_simple', 'like', '%'.$term.'%')
                ->orWhereHas('translations', fn (Builder $t) => $t->where('name', 'like', '%'.$term.'%'));
        });
    }
}
