<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

use App\Domain\Quran\DTOs\ReaderOptions;
use App\Domain\Quran\DTOs\VerseEditData;
use App\Domain\Quran\Enums\ResourceKind;
use App\Domain\Quran\Enums\SectionType;
use App\Domain\Quran\Import\QuranFoundation\Mappers\VerseMapper;
use App\Domain\Quran\Models\Verse;
use App\Domain\Quran\Models\Word;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class EloquentVerseRepository implements VerseRepositoryInterface
{
    private const MAX_PER_PAGE = 50;

    public function paginateAdmin(array $filters, int $perPage): LengthAwarePaginator
    {
        $q = trim((string) ($filters['q'] ?? ''));

        return Verse::query()
            ->select([...self::BASE_COLUMNS, 'text_uthmani', 'is_locked'])
            ->when(($filters['chapter'] ?? null) !== null, fn (Builder $b) => $b->where('chapter_id', (int) $filters['chapter']))
            ->when(($filters['juz'] ?? null) !== null, fn (Builder $b) => $b->where('juz_number', (int) $filters['juz']))
            ->when(($filters['page'] ?? null) !== null, fn (Builder $b) => $b->where('page_number', (int) $filters['page']))
            ->when(($filters['sajdah'] ?? null) === true, fn (Builder $b) => $b->whereNotNull('sajdah_number'))
            ->when(($filters['locked'] ?? null) !== null, fn (Builder $b) => $b->where('is_locked', (bool) $filters['locked']))
            ->when($q !== '', function (Builder $b) use ($q): void {
                if (Verse::parseKey($q) !== null) {
                    $b->where('verse_key', $q);
                } else {
                    $b->where('text_uthmani_simple', 'like', '%'.mb_substr($q, 0, 50).'%');
                }
            })
            ->orderBy('id')
            ->paginate(min(100, max(1, $perPage)))
            ->withQueryString();
    }

    public function findForAdmin(int $id): ?Verse
    {
        return Verse::query()
            ->with([
                'chapter:id,name_arabic,name_simple,verses_count',
                'words' => fn ($q) => $q->select(['id', 'verse_id', 'position', 'location', 'char_type', 'text_uthmani', 'text_qpc_hafs', 'code_v2', 'page_number', 'line_number', 'audio_url']),
                'words.glosses' => fn ($q) => $q->select(['id', 'word_id', 'resource_id', 'kind', 'language_iso', 'text']),
                'words.glosses.resource:id,name,kind,language_iso',
                'translations' => fn ($q) => $q->select(['id', 'resource_id', 'verse_id', 'text', 'foot_notes', 'is_locked']),
                'translations.resource:id,name,language_iso,is_active',
                'tafsirs' => fn ($q) => $q->select(['id', 'resource_id', 'verse_id', 'text', 'is_locked']),
                'tafsirs.resource:id,name,language_iso,is_active',
                'audioFiles' => fn ($q) => $q->select(['id', 'resource_id', 'verse_id', 'url', 'duration_ms', 'is_local']),
                'audioFiles.resource:id,name,is_active',
            ])
            ->find($id);
    }

    public function update(int $id, VerseEditData $data): Verse
    {
        return DB::transaction(function () use ($id, $data): Verse {
            $verse = Verse::query()->findOrFail($id);
            $changedText = false;

            foreach ($data->texts as $column => $value) {
                if ($verse->getAttribute($column) !== $value) {
                    $verse->setAttribute($column, $value);
                    $changedText = true;
                }
            }

            $verse->sajdah_type = $data->sajdahType;
            $verse->sajdah_number = $data->sajdahNumber;
            $verse->is_locked = $data->isLocked ?? ($changedText ? true : $verse->is_locked);
            $verse->save();

            return $verse;
        });
    }

    public function updateWordGlosses(int $wordId, array $glosses): int
    {
        return DB::transaction(function () use ($wordId, $glosses): int {
            if (! DB::table('quran_words')->where('id', $wordId)->exists()) {
                throw (new ModelNotFoundException)->setModel(Word::class, [$wordId]);
            }

            $resources = DB::table('quran_resources')
                ->select(['id', 'kind', 'language_iso'])
                ->whereIn('id', array_keys($glosses))
                ->whereIn('kind', [ResourceKind::WORD_TRANSLATION->value, ResourceKind::WORD_TRANSLITERATION->value])
                ->get()
                ->keyBy('id');

            $changed = 0;
            foreach ($glosses as $resourceId => $text) {
                $resource = $resources->get($resourceId);
                if ($resource === null) {
                    continue;
                }
                $text = trim((string) $text);
                $key = ['word_id' => $wordId, 'resource_id' => (int) $resourceId];
                if ($text === '') {
                    $changed += DB::table('quran_word_glosses')->where($key)->delete();

                    continue;
                }
                $changed += (int) DB::table('quran_word_glosses')->updateOrInsert($key, [
                    'kind' => $resource->kind === ResourceKind::WORD_TRANSLATION->value ? 'translation' : 'transliteration',
                    'language_iso' => (string) ($resource->language_iso ?? 'en'),
                    'text' => mb_substr($text, 0, 500),
                    'updated_at' => now(),
                ]);
            }

            return $changed;
        });
    }

    public function setLocked(array $ids, bool $locked): int
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));

        return $ids === [] ? 0 : Verse::query()->whereKey($ids)->update(['is_locked' => $locked]);
    }

    public function byChapter(int $chapterId, array $extraColumns = []): Collection
    {
        return $this->select($extraColumns)->where('chapter_id', $chapterId)->orderBy('verse_number')->get();
    }

    public function pageByChapter(int $chapterId, int $page, int $perPage, array $extraColumns = []): LengthAwarePaginator
    {
        return $this->select($extraColumns)->where('chapter_id', $chapterId)->orderBy('verse_number')
            ->paginate($this->perPage($perPage), ['*'], 'page', max(1, $page));
    }

    public function pageBySection(SectionType $type, int $number, int $page, int $perPage, array $extraColumns = []): LengthAwarePaginator
    {
        return $this->select($extraColumns)->where($type->verseColumn(), $number)->orderBy('id')
            ->paginate($this->perPage($perPage), ['*'], 'page', max(1, $page));
    }

    public function byPage(int $pageNumber, ?int $mushafId, array $extraColumns = []): Collection
    {
        $defaultMushaf = (int) config('quran.mushaf.default_id', 1);

        if ($mushafId !== null && $mushafId !== $defaultMushaf) {
            $range = DB::table('quran_mushaf_pages')->where('mushaf_id', $mushafId)->where('page_number', $pageNumber)
                ->first(['first_verse_id', 'last_verse_id']);
            if ($range === null) {
                return new Collection;
            }

            return $this->select($extraColumns)->whereBetween('id', [$range->first_verse_id, $range->last_verse_id])->orderBy('id')->get();
        }

        return $this->select($extraColumns)->where('page_number', $pageNumber)->orderBy('id')->get();
    }

    public function pageByRange(int $fromId, int $toId, int $page, int $perPage, array $extraColumns = []): LengthAwarePaginator
    {
        return $this->select($extraColumns)->whereBetween('id', [min($fromId, $toId), max($fromId, $toId)])->orderBy('id')
            ->paginate($this->perPage($perPage), ['*'], 'page', max(1, $page));
    }

    public function byKey(string $verseKey, array $extraColumns = []): ?Verse
    {
        return Verse::parseKey($verseKey) === null ? null : $this->select($extraColumns)->where('verse_key', trim($verseKey))->first();
    }

    public function byKeys(array $keys, array $extraColumns = []): Collection
    {
        $keys = array_values(array_filter(array_unique($keys), static fn (string $k): bool => Verse::parseKey($k) !== null));

        return $keys === [] ? new Collection : $this->select($extraColumns)->whereIn('verse_key', $keys)->orderBy('id')->get();
    }

    public function random(array $axisFilter, array $extraColumns = []): ?Verse
    {
        $allowed = ['chapter_id', 'juz_number', 'hizb_number', 'rub_el_hizb_number', 'manzil_number', 'ruku_number', 'page_number'];
        $filter = array_intersect_key($axisFilter, array_flip($allowed));

        // Pick an id inside the filtered id range, then take the first verse at or after
        // it — an indexed seek rather than ORDER BY RAND() over the whole table.
        $bounds = Verse::query()->where($filter)->selectRaw('MIN(id) as lo, MAX(id) as hi')->first();
        if ($bounds === null || $bounds->lo === null) {
            return null;
        }

        $target = random_int((int) $bounds->lo, (int) $bounds->hi);

        return $this->select($extraColumns)->where($filter)->where('id', '>=', $target)->orderBy('id')->first();
    }

    public function references(?int $chapterId): array
    {
        return DB::table('quran_verses')
            ->select(['verse_key', 'chapter_id', 'verse_number'])
            ->when($chapterId !== null, fn (QueryBuilder $q): QueryBuilder => $q->where('chapter_id', $chapterId))
            ->orderBy('id')
            ->get()
            ->map(static fn (object $row): array => [
                'verse_key' => (string) $row->verse_key,
                'chapter_id' => (int) $row->chapter_id,
                'verse_number' => (int) $row->verse_number,
            ])
            ->all();
    }

    public function scriptRows(string $column, array $axisFilter): Collection
    {
        if (! in_array($column, Verse::TEXT_COLUMNS, true)) {
            return new Collection;
        }

        $extra = match ($column) {
            'code_v1' => ['v1_page'],
            'code_v2' => ['v2_page'],
            default => [],
        };

        return DB::table('quran_verses')
            ->select(['id', 'verse_key', $column, ...$extra])
            ->where($axisFilter)
            ->orderBy('id')
            ->get();
    }

    public function payload(array $verseIds, ReaderOptions $options): array
    {
        $verseIds = array_values(array_unique(array_map('intval', $verseIds)));
        $payload = [];
        foreach ($verseIds as $id) {
            $payload[$id] = ['words' => [], 'translations' => [], 'tafsirs' => [], 'audio' => null];
        }
        if ($verseIds === []) {
            return $payload;
        }

        if ($options->words) {
            $this->attachWords($payload, $verseIds, $options);
        }

        if ($options->translationIds !== []) {
            $rows = DB::table('quran_translations')
                ->select(['id', 'resource_id', 'verse_id', 'text', 'foot_notes'])
                ->whereIn('verse_id', $verseIds)
                ->whereIn('resource_id', $options->translationIds)
                ->get();
            foreach ($rows as $row) {
                $payload[(int) $row->verse_id]['translations'][(int) $row->resource_id] = [
                    'id' => (int) $row->id,
                    'text' => (string) $row->text,
                    'foot_notes' => $row->foot_notes === null ? null : json_decode((string) $row->foot_notes, true),
                ];
            }
        }

        if ($options->tafsirIds !== []) {
            $rows = DB::table('quran_tafsirs')
                ->select(['id', 'resource_id', 'verse_id', 'text'])
                ->whereIn('verse_id', $verseIds)
                ->whereIn('resource_id', $options->tafsirIds)
                ->get();
            foreach ($rows as $row) {
                $payload[(int) $row->verse_id]['tafsirs'][(int) $row->resource_id] = ['id' => (int) $row->id, 'text' => (string) $row->text];
            }
        }

        if ($options->recitationId !== null) {
            $rows = DB::table('quran_audio_files')
                ->select(['verse_id', 'url', 'segments', 'duration_ms'])
                ->where('resource_id', $options->recitationId)
                ->whereIn('verse_id', $verseIds)
                ->get();
            foreach ($rows as $row) {
                $payload[(int) $row->verse_id]['audio'] = [
                    'url' => (string) $row->url,
                    'segments' => $row->segments === null ? null : json_decode((string) $row->segments, true),
                    'duration_ms' => $row->duration_ms === null ? null : (int) $row->duration_ms,
                ];
            }
        }

        return $payload;
    }

    /**
     * @param  array<int, array<string, mixed>>  $payload
     * @param  list<int>  $verseIds
     */
    private function attachWords(array &$payload, array $verseIds, ReaderOptions $options): void
    {
        $words = DB::table('quran_words')
            ->select(['id', 'verse_id', 'position', 'location', 'char_type', 'text_uthmani', 'text_qpc_hafs', 'text_indopak', 'code_v1', 'code_v2', 'v1_page', 'v2_page', 'page_number', 'line_number', 'audio_url'])
            ->whereIn('verse_id', $verseIds)
            ->orderBy('verse_id')
            ->orderBy('position')
            ->get();

        $glosses = [];
        if ($words->isNotEmpty()) {
            $language = $options->wbwLanguage ?? (string) config('quran.defaults.wbw_language', 'en');
            $rows = DB::table('quran_word_glosses as g')
                ->join('quran_resources as r', 'r.id', '=', 'g.resource_id')
                ->select(['g.word_id', 'g.kind', 'g.text', 'r.is_default', 'r.id as resource_id'])
                ->whereIn('g.word_id', $words->pluck('id')->all())
                ->where('r.is_active', true)
                ->where(fn ($q) => $q->where(fn ($t) => $t->where('g.kind', 'translation')->where('g.language_iso', $language))
                    ->orWhere('g.kind', 'transliteration'))
                // Default resource first, then lowest id, so the first gloss per word wins.
                ->orderByDesc('r.is_default')
                ->orderBy('r.id')
                ->get();
            foreach ($rows as $row) {
                $glosses[(int) $row->word_id][$row->kind] ??= (string) $row->text;
            }
        }

        foreach ($words as $word) {
            $payload[(int) $word->verse_id]['words'][] = [
                'id' => (int) $word->id,
                'position' => (int) $word->position,
                'location' => (string) $word->location,
                'char_type' => (string) $word->char_type,
                'text_uthmani' => $word->text_uthmani,
                'text_qpc_hafs' => $word->text_qpc_hafs,
                'text_indopak' => $word->text_indopak,
                'code_v1' => $word->code_v1,
                'code_v2' => $word->code_v2,
                'v1_page' => $word->v1_page === null ? null : (int) $word->v1_page,
                'v2_page' => $word->v2_page === null ? null : (int) $word->v2_page,
                'page_number' => (int) $word->page_number,
                'line_number' => (int) $word->line_number,
                'audio_url' => VerseMapper::absoluteAudio($word->audio_url),
                'translation' => $glosses[(int) $word->id]['translation'] ?? null,
                'transliteration' => $glosses[(int) $word->id]['transliteration'] ?? null,
            ];
        }
    }

    /**
     * @param  list<string>  $extraColumns
     * @return Builder<Verse>
     */
    private function select(array $extraColumns): Builder
    {
        $extra = array_values(array_intersect($extraColumns, [...Verse::TEXT_COLUMNS, 'v1_page', 'v2_page', 'is_locked']));

        return Verse::query()->select(array_values(array_unique([...self::BASE_COLUMNS, ...$extra])));
    }

    private function perPage(int $perPage): int
    {
        return min(self::MAX_PER_PAGE, max(1, $perPage));
    }
}
