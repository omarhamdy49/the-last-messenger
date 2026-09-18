<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use App\Domain\Quran\DTOs\ReaderOptions;
use App\Domain\Quran\DTOs\VerseApiParams;
use App\Domain\Quran\Enums\RecitationStyle;
use App\Domain\Quran\Enums\ResourceKind;
use App\Domain\Quran\Enums\Script;
use App\Domain\Quran\Enums\SectionType;
use App\Domain\Quran\Models\Chapter;
use App\Domain\Quran\Models\MushafPage;
use App\Domain\Quran\Models\QuranResource;
use App\Domain\Quran\Models\Section;
use App\Domain\Quran\Models\Verse;
use App\Domain\Quran\Repositories\AudioRepositoryInterface;
use App\Domain\Quran\Repositories\ChapterRepositoryInterface;
use App\Domain\Quran\Repositories\LanguageRepositoryInterface;
use App\Domain\Quran\Repositories\QuranApiRepositoryInterface;
use App\Domain\Quran\Repositories\ResourceRepositoryInterface;
use App\Domain\Quran\Repositories\SectionRepositoryInterface;
use App\Domain\Quran\Repositories\VerseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * The public API in Quran Foundation Content API v4 shapes, served from local data, so
 * a client written against the QF docs works against /api/v1/quran unchanged. Methods
 * return null for "not found" (the controller answers 404).
 */
final class QuranApiService
{
    /** Verse columns a client may add with `fields`. */
    public const VERSE_FIELDS = [...Verse::TEXT_COLUMNS, 'v1_page', 'v2_page', 'image_url', 'image_width'];

    /** Word columns a client may add with `word_fields`. */
    public const WORD_FIELDS = ['text_uthmani', 'text_qpc_hafs', 'text_indopak', 'code_v1', 'code_v2', 'v1_page', 'v2_page', 'location'];

    /** @var array<string, string>|null iso => lower-case language name */
    private ?array $languageNames = null;

    public function __construct(
        private readonly ChapterRepositoryInterface $chapters,
        private readonly VerseRepositoryInterface $verses,
        private readonly SectionRepositoryInterface $sections,
        private readonly ResourceRepositoryInterface $resources,
        private readonly AudioRepositoryInterface $audio,
        private readonly LanguageRepositoryInterface $languages,
        private readonly QuranApiRepositoryInterface $api,
        private readonly SearchService $search,
    ) {}

    // ------------------------------------------------------------------ chapters

    /**
     * @return array{chapters: list<array<string, mixed>>}
     */
    public function chapters(?string $language): array
    {
        return ['chapters' => $this->chapters->all()->map(fn (Chapter $c): array => $this->chapterShape($c, $language))->values()->all()];
    }

    /**
     * @return array{chapter: array<string, mixed>}|null
     */
    public function chapter(int $id, ?string $language): ?array
    {
        $chapter = $this->chapters->find($id);

        return $chapter === null || ! $chapter->is_published ? null : ['chapter' => $this->chapterShape($chapter, $language)];
    }

    /**
     * @return array{chapter_info: array<string, mixed>|null, resources?: list<array<string, mixed>>}|null
     */
    public function chapterInfo(int $id, ?string $language, ?int $resourceId, bool $includeResources): ?array
    {
        if ($this->chapters->find($id) === null) {
            return null;
        }

        $info = $this->chapters->info($id, $language ?? 'en', $resourceId);
        $data = ['chapter_info' => $info === null ? null : [
            'id' => $info['resource_id'],
            'chapter_id' => $id,
            'language_name' => $this->languageName($info['language_iso']),
            'short_text' => $info['short_text'],
            'source' => $info['source'],
            'text' => $info['text'],
            'resource_id' => $info['resource_id'],
        ]];

        if ($includeResources) {
            $data['resources'] = $this->resources->active(ResourceKind::CHAPTER_INFO)
                ->map(fn (QuranResource $r): array => $this->resourceRef($r, $language))->values()->all();
        }

        return $data;
    }

    // ------------------------------------------------------------------ verses

    /**
     * @param  'chapter'|'page'|'range'|SectionType  $axis
     * @param  int|array{0: int, 1: int}  $value  axis number, or [fromId, toId] for a range
     * @return array{verses: list<array<string, mixed>>, pagination: array<string, int|null>}|null
     */
    public function verses(string|SectionType $axis, int|array $value, VerseApiParams $params): ?array
    {
        $extra = $params->fields;
        $paginator = match (true) {
            $axis === 'chapter' => $this->verses->pageByChapter((int) $value, $params->page, $params->perPage, $extra),
            $axis === 'range' => $this->verses->pageByRange($value[0], $value[1], $params->page, $params->perPage, $extra),
            $axis === 'page' => $this->paginate($this->verses->byPage((int) $value, null, $extra), $params),
            $axis instanceof SectionType => $this->verses->pageBySection($axis, (int) $value, $params->page, $params->perPage, $extra),
        };

        if ($paginator->total() === 0) {
            return null;
        }

        return [
            'verses' => $this->verseShapes(collect($paginator->items()), $params),
            'pagination' => self::pagination($paginator),
        ];
    }

    /**
     * @return array{verse: array<string, mixed>}|null
     */
    public function verseByKey(string $key, VerseApiParams $params): ?array
    {
        $verse = $this->verses->byKey($key, $params->fields);

        return $verse === null ? null : ['verse' => $this->verseShapes(collect([$verse]), $params)[0]];
    }

    /**
     * @param  array<string, int>  $axisFilter
     * @return array{verse: array<string, mixed>}|null
     */
    public function randomVerse(array $axisFilter, VerseApiParams $params): ?array
    {
        $verse = $this->verses->random($axisFilter, $params->fields);

        return $verse === null ? null : ['verse' => $this->verseShapes(collect([$verse]), $params)[0]];
    }

    /**
     * Verse id range for a `from`/`to` pair given as verse keys or ids.
     *
     * @return array{0: int, 1: int}|null
     */
    public function rangeIds(string $from, string $to): ?array
    {
        $resolve = fn (string $value): ?int => ctype_digit($value) ? (int) $value : $this->verses->byKey($value)?->id;
        $a = $resolve($from);
        $b = $resolve($to);

        return $a === null || $b === null ? null : [min($a, $b), max($a, $b)];
    }

    // ------------------------------------------------------------------ sections and pages

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    public function sections(SectionType $type): array
    {
        return [self::plural($type) => $this->sections->ofType($type)->map(fn (Section $s): array => $this->sectionShape($s))->values()->all()];
    }

    /**
     * @return array<string, array<string, mixed>>|null
     */
    public function section(SectionType $type, int $number): ?array
    {
        $section = $this->sections->find($type, $number);

        return $section === null ? null : [$type->value => $this->sectionShape($section)];
    }

    /**
     * @return array{pages: list<array<string, mixed>>}
     */
    public function pages(int $mushafId): array
    {
        return ['pages' => $this->api->pages($mushafId)->map(fn (MushafPage $p): array => $this->pageShape($p))->values()->all()];
    }

    /**
     * @return array{page: array<string, mixed>}|null
     */
    public function page(int $mushafId, int $number): ?array
    {
        $page = $this->api->pages($mushafId)->firstWhere('page_number', $number);

        return $page === null ? null : ['page' => $this->pageShape($page)];
    }

    /**
     * @param  array{0: int, 1: int}  $verseIds
     * @return array{total_page: int, lookup_range: array{from: string, to: string}, pages: array<int, array<string, string>>}|null
     */
    public function pagesLookup(int $mushafId, array $verseIds): ?array
    {
        $pages = $this->api->pagesInRange($mushafId, $verseIds[0], $verseIds[1]);
        if ($pages->isEmpty()) {
            return null;
        }

        $ids = $pages->flatMap(fn (MushafPage $p): array => [max($p->first_verse_id, $verseIds[0]), min($p->last_verse_id, $verseIds[1])])
            ->push($verseIds[0], $verseIds[1])->unique()->values()->all();
        $keyById = collect($this->api->verseKeys($ids));

        return [
            'total_page' => $pages->count(),
            'lookup_range' => ['from' => (string) $keyById->get($verseIds[0]), 'to' => (string) $keyById->get($verseIds[1])],
            'pages' => $pages->mapWithKeys(fn (MushafPage $p): array => [$p->page_number => [
                'first_verse_key' => (string) $keyById->get(max($p->first_verse_id, $verseIds[0])),
                'last_verse_key' => (string) $keyById->get(min($p->last_verse_id, $verseIds[1])),
                'from' => (string) $keyById->get(max($p->first_verse_id, $verseIds[0])),
                'to' => (string) $keyById->get(min($p->last_verse_id, $verseIds[1])),
            ]])->all(),
        ];
    }

    /**
     * @param  array<string, int|string>  $axisFilter
     * @return array{verses: list<array<string, mixed>>, meta: array{filters: array<string, int|string>}}
     */
    public function scriptVerses(Script $script, array $axisFilter): array
    {
        return [
            'verses' => $this->verses->scriptRows($script->value, $axisFilter)->map(static fn (object $row): array => (array) $row)->values()->all(),
            'meta' => ['filters' => $axisFilter],
        ];
    }

    /**
     * @param  array<string, int|string>  $axisFilter
     * @return array{0: int, 1: int}|null
     */
    public function axisRange(array $axisFilter): ?array
    {
        return $this->api->verseIdRange($axisFilter);
    }

    // ------------------------------------------------------------------ translations, tafsirs, resources

    /**
     * @param  array<string, int|string>  $axisFilter
     * @return array{translations: list<array<string, mixed>>, meta: array<string, mixed>}|null
     */
    public function translationFull(int $resourceId, array $axisFilter, bool $footNotes): ?array
    {
        $resource = $this->resources->activeByIds(ResourceKind::TRANSLATION, [$resourceId])->first();
        if ($resource === null) {
            return null;
        }

        return [
            'translations' => $this->api->translationRows($resourceId, $axisFilter)->map(static function (object $row) use ($resourceId, $footNotes): array {
                $shape = ['resource_id' => $resourceId, 'id' => (int) $row->id, 'verse_key' => (string) $row->verse_key, 'text' => (string) $row->text];
                if ($footNotes) {
                    $shape['foot_notes'] = $row->foot_notes === null ? (object) [] : json_decode((string) $row->foot_notes, true);
                }

                return $shape;
            })->values()->all(),
            'meta' => ['translation_name' => $resource->name, 'author_name' => $resource->author_name, 'filters' => $axisFilter],
        ];
    }

    /**
     * @param  array<string, int|string>  $axisFilter
     * @return array{tafsirs: list<array<string, mixed>>, meta: array<string, mixed>, pagination: array<string, int|null>}|null
     */
    public function tafsirFull(int $resourceId, array $axisFilter, int $page, int $perPage): ?array
    {
        $resource = $this->resources->activeByIds(ResourceKind::TAFSIR, [$resourceId])->first();
        if ($resource === null) {
            return null;
        }
        $rows = $this->api->tafsirRows($resourceId, $axisFilter, $page, $perPage);

        return [
            'tafsirs' => collect($rows->items())->map(static fn (object $row): array => [
                'resource_id' => $resourceId, 'id' => (int) $row->id, 'verse_key' => (string) $row->verse_key, 'text' => (string) $row->text,
            ])->values()->all(),
            'meta' => ['tafsir_name' => $resource->name, 'author_name' => $resource->author_name, 'filters' => $axisFilter],
            'pagination' => self::pagination($rows),
        ];
    }

    /**
     * @return array<string, mixed>|null keyed by the catalogue name (`translations`, `languages`, …)
     */
    public function resourceIndex(string $segment, ?string $language): ?array
    {
        if ($segment === 'languages') {
            return ['languages' => $this->languages->active()->map(static fn ($l): array => [
                'id' => $l->id, 'name' => $l->name, 'iso_code' => $l->iso_code, 'native_name' => $l->native_name,
                'direction' => $l->direction, 'translations_count' => $l->translations_count,
            ])->values()->all()];
        }

        if ($segment === 'recitation_styles') {
            $styles = [];
            foreach (RecitationStyle::cases() as $style) {
                $styles[$style->value] = $style->label($language ?? 'en');
            }

            return ['recitation_styles' => $styles];
        }

        $kind = self::kindForSegment($segment);
        if ($kind === null) {
            return null;
        }

        $key = $kind === ResourceKind::CHAPTER_RECITER ? 'reciters' : $segment;

        return [$key => $this->resources->active($kind)->map(fn (QuranResource $r): array => $this->resourceRef($r, $language))->values()->all()];
    }

    /**
     * @return array{info: array{id: int, info: string}}|null
     */
    public function resourceInfo(string $segment, int $id): ?array
    {
        $kind = self::kindForSegment($segment);
        $resource = $kind === null ? null : $this->resources->activeByIds($kind, [$id])->first();

        return $resource === null ? null : ['info' => ['id' => $resource->id, 'info' => (string) $resource->info]];
    }

    /**
     * @return array{foot_note: array{id: string, text: string, language_name: ?string}}|null
     */
    public function footNote(string $id): ?array
    {
        $note = $this->api->footNote($id);
        if ($note === null) {
            return null;
        }
        $resource = $this->resources->activeByIds(ResourceKind::TRANSLATION, [$note['resource_id']])->first();

        return $resource === null ? null : ['foot_note' => ['id' => $note['id'], 'text' => $note['text'], 'language_name' => $this->languageName($resource->language_iso)]];
    }

    // ------------------------------------------------------------------ audio

    /**
     * @param  array<string, int|string>  $axisFilter
     * @return array{audio_files: list<array<string, mixed>>, meta: array<string, mixed>}|null
     */
    public function recitationFiles(int $resourceId, array $axisFilter): ?array
    {
        $recitation = $this->resources->activeByIds(ResourceKind::RECITATION, [$resourceId])->first();
        if ($recitation === null) {
            return null;
        }

        return [
            'audio_files' => $this->audio->ayahFilesWhere($resourceId, $axisFilter)->map(static fn (object $f): array => [
                'id' => (int) $f->id,
                'verse_key' => (string) $f->verse_key,
                'url' => AudioService::absolutize($f->url),
                'segments' => $f->segments === null ? null : json_decode((string) $f->segments, true),
                'duration' => $f->duration_ms === null ? null : (int) $f->duration_ms,
                'format' => $f->format,
            ])->values()->all(),
            'meta' => ['reciter_name' => $recitation->name, 'recitation_style' => $recitation->style?->value],
        ];
    }

    /**
     * @return array{audio_files: list<array<string, mixed>>}|null
     */
    public function chapterRecitations(int $resourceId): ?array
    {
        if ($this->resources->activeByIds(ResourceKind::CHAPTER_RECITER, [$resourceId])->isEmpty()) {
            return null;
        }

        return ['audio_files' => $this->audio->chapterFiles($resourceId)->map(fn ($f): array => $this->chapterFileShape($f, false))->values()->all()];
    }

    /**
     * @return array{audio_file: array<string, mixed>}|null
     */
    public function chapterRecitation(int $resourceId, int $chapter, bool $segments): ?array
    {
        if ($this->resources->activeByIds(ResourceKind::CHAPTER_RECITER, [$resourceId])->isEmpty()) {
            return null;
        }
        $file = $this->audio->chapterFile($resourceId, $chapter, withTimestamps: true);

        return $file === null ? null : ['audio_file' => $this->chapterFileShape($file, true, $segments)];
    }

    /**
     * Where a verse (or a word / word range of it) starts and ends in a chapter file.
     *
     * @return array{result: array{timestamp_from: int, timestamp_to: int}}|null
     */
    public function audioTimestamp(int $resourceId, int $chapter, ?string $verseKey, ?int $word, ?int $wordFrom, ?int $wordTo, AudioService $service): ?array
    {
        $timings = $service->timings($resourceId, $chapter);
        if ($timings === []) {
            return null;
        }

        if ($verseKey === null) {
            return ['result' => ['timestamp_from' => $timings[0]['from'], 'timestamp_to' => $timings[count($timings) - 1]['to']]];
        }

        $timing = collect($timings)->firstWhere('verse_key', $verseKey);
        if ($timing === null) {
            return null;
        }

        $from = $wordFrom ?? $word;
        $to = $wordTo ?? $word;
        if ($from !== null && $to !== null && is_array($timing['segments'])) {
            $segments = collect($timing['segments'])->filter(static fn (array $s): bool => $s[0] >= $from && $s[0] <= $to);
            if ($segments->isNotEmpty()) {
                return ['result' => ['timestamp_from' => (int) $segments->min(1), 'timestamp_to' => (int) $segments->max(2)]];
            }
        }

        return ['result' => ['timestamp_from' => $timing['from'], 'timestamp_to' => $timing['to']]];
    }

    /**
     * The verse being recited at a millisecond offset of a chapter file.
     *
     * @return array{result: array<string, mixed>}|null
     */
    public function audioLookup(int $resourceId, int $chapter, int $timestamp, bool $segments, AudioService $service): ?array
    {
        $timing = AudioService::timingAt($service->timings($resourceId, $chapter), $timestamp);
        if ($timing === null) {
            return null;
        }

        $result = [
            'verse_key' => $timing['verse_key'],
            'timestamp_from' => $timing['from'],
            'timestamp_to' => $timing['to'],
            'duration' => $timing['to'] - $timing['from'],
        ];
        if ($segments) {
            $result['segments'] = $timing['segments'];
        }

        return ['result' => $result];
    }

    // ------------------------------------------------------------------ search

    /**
     * @param  list<int>  $translationIds
     * @return array{result: array{navigation: list<array<string, mixed>>, verses: list<array<string, mixed>>}, pagination?: array<string, int|null>}
     */
    public function search(string $mode, string $query, string $locale, int $page, int $size, array $translationIds, bool $exact, bool $highlight, int $navigationLimit, int $verseLimit): array
    {
        $navigation = array_map(static fn (array $hit): array => [
            'result_type' => $hit['result_type'], 'key' => $hit['key'], 'name' => $hit['name'], 'isArabic' => (bool) preg_match('/\p{Arabic}/u', $hit['name']), 'isTransliteration' => false,
        ], $this->search->quick($query, $locale, $navigationLimit, 0)['navigation']);

        if ($mode === 'quick') {
            $verses = $this->search->advanced($query, $translationIds, $exact, $highlight, 1, $verseLimit, $locale);

            return ['result' => ['navigation' => $navigation, 'verses' => $this->searchVerses($verses['verses'])]];
        }

        $verses = $this->search->advanced($query, $translationIds, $exact, $highlight, $page, $size, $locale);

        return [
            'result' => ['navigation' => $navigation, 'verses' => $this->searchVerses($verses['verses'])],
            'pagination' => [
                'per_page' => $verses['pagination']['per_page'],
                'current_page' => $verses['pagination']['current_page'],
                'next_page' => $verses['pagination']['current_page'] < $verses['pagination']['total_pages'] ? $verses['pagination']['current_page'] + 1 : null,
                'total_pages' => $verses['pagination']['total_pages'],
                'total_records' => $verses['pagination']['total_records'],
            ],
        ];
    }

    // ------------------------------------------------------------------ shapes

    /**
     * @param  Collection<int, Verse>  $verses
     * @return list<array<string, mixed>>
     */
    private function verseShapes(Collection $verses, VerseApiParams $params): array
    {
        $translationIds = $this->resources->activeByIds(ResourceKind::TRANSLATION, $params->translationIds)->pluck('id')->all();
        $tafsirIds = $this->resources->activeByIds(ResourceKind::TAFSIR, $params->tafsirIds)->pluck('id')->all();
        $audioId = $params->audioId !== null && $this->resources->activeByIds(ResourceKind::RECITATION, [$params->audioId])->isNotEmpty() ? $params->audioId : null;

        $needsPayload = $params->words || $translationIds !== [] || $tafsirIds !== [] || $audioId !== null;
        $payload = $needsPayload ? $this->verses->payload($verses->pluck('id')->all(), new ReaderOptions(
            translationIds: $translationIds, tafsirIds: $tafsirIds, words: $params->words,
            wbwLanguage: $params->language, recitationId: $audioId,
        )) : [];
        $resources = $this->resources->active(ResourceKind::TRANSLATION)->concat($this->resources->active(ResourceKind::TAFSIR))->keyBy('id');
        $wordLanguage = $this->languageName($params->language ?? (string) config('quran.defaults.wbw_language', 'en'));

        return $verses->map(function (Verse $verse) use ($params, $payload, $resources, $wordLanguage): array {
            $shape = [
                'id' => $verse->id,
                'chapter_id' => $verse->chapter_id,
                'verse_number' => $verse->verse_number,
                'verse_key' => $verse->verse_key,
                'verse_index' => $verse->verse_index ?? $verse->id,
                'juz_number' => $verse->juz_number,
                'hizb_number' => $verse->hizb_number,
                'rub_el_hizb_number' => $verse->rub_el_hizb_number,
                'ruku_number' => $verse->ruku_number,
                'manzil_number' => $verse->manzil_number,
                'page_number' => $verse->page_number,
                'sajdah_type' => $verse->sajdah_type,
                'sajdah_number' => $verse->sajdah_number,
            ];
            foreach ($params->fields as $field) {
                $shape[$field] = $field === 'image_url' ? AudioService::absolutize($verse->image_url) : $verse->getAttribute($field);
            }

            $parts = $payload[$verse->id] ?? null;
            if ($parts === null) {
                return $shape;
            }

            if ($params->words) {
                $shape['words'] = array_map(static function (array $w) use ($params, $wordLanguage): array {
                    $word = [
                        'id' => $w['id'],
                        'position' => $w['position'],
                        'audio_url' => $w['audio_url'],
                        'char_type_name' => $w['char_type'],
                        'text' => $w['text_uthmani'],
                        'page_number' => $w['page_number'],
                        'line_number' => $w['line_number'],
                        'translation' => ['text' => $w['translation'], 'language_name' => $wordLanguage],
                        'transliteration' => ['text' => $w['transliteration'], 'language_name' => 'english'],
                    ];
                    foreach ($params->wordFields as $field) {
                        $word[$field] = $w[$field] ?? null;
                    }

                    return $word;
                }, $parts['words']);
            }
            if ($params->translationIds !== []) {
                $shape['translations'] = array_values(array_map(fn (int $resourceId, array $row): array => [
                    'resource_id' => $resourceId,
                    'resource_name' => $resources->get($resourceId)?->name,
                    'id' => $row['id'],
                    'text' => $row['text'],
                    'foot_notes' => $row['foot_notes'] ?? (object) [],
                    'language_name' => $this->languageName($resources->get($resourceId)?->language_iso),
                ], array_keys($parts['translations']), $parts['translations']));
            }
            if ($params->tafsirIds !== []) {
                $shape['tafsirs'] = array_values(array_map(fn (int $resourceId, array $row): array => [
                    'id' => $row['id'],
                    'resource_id' => $resourceId,
                    'name' => $resources->get($resourceId)?->name,
                    'language_name' => $this->languageName($resources->get($resourceId)?->language_iso),
                    'text' => $row['text'],
                ], array_keys($parts['tafsirs']), $parts['tafsirs']));
            }
            if ($params->audioId !== null) {
                $shape['audio'] = $parts['audio'] === null ? null : ['verse_key' => $verse->verse_key, 'url' => AudioService::absolutize($parts['audio']['url']), 'segments' => $parts['audio']['segments']];
            }

            return $shape;
        })->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function chapterShape(Chapter $chapter, ?string $language): array
    {
        $language = $language ?? 'en';
        $translated = $language === 'ar' ? $chapter->name_arabic : $chapter->getTranslation('name', $language, false);
        $translatedLanguage = $translated === null ? 'en' : $language;
        $translated ??= $chapter->getTranslation('name', 'en', false) ?? $chapter->name_simple;

        return [
            'id' => $chapter->id,
            'revelation_place' => $chapter->revelation_place->value,
            'revelation_order' => $chapter->revelation_order,
            'bismillah_pre' => $chapter->bismillah_pre,
            'name_simple' => $chapter->name_simple,
            'name_complex' => $chapter->name_complex,
            'name_arabic' => $chapter->name_arabic,
            'verses_count' => $chapter->verses_count,
            'pages' => [$chapter->first_page, $chapter->last_page],
            'translated_name' => ['language_name' => $this->languageName($translatedLanguage), 'name' => $translated],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function sectionShape(Section $section): array
    {
        $shape = [
            'id' => $section->id,
            $section->type->value.'_number' => $section->number,
            'verse_mapping' => $section->verse_mapping,
            'first_verse_id' => $section->first_verse_id,
            'last_verse_id' => $section->last_verse_id,
            'verses_count' => $section->verses_count,
        ];
        if ($section->surah_ruku_number !== null) {
            $shape['surah_ruku_number'] = $section->surah_ruku_number;
        }

        return $shape;
    }

    /**
     * @return array<string, mixed>
     */
    private function pageShape(MushafPage $page): array
    {
        return [
            'id' => $page->id,
            'page_number' => $page->page_number,
            'verse_mapping' => $page->verse_mapping,
            'first_verse_id' => $page->first_verse_id,
            'last_verse_id' => $page->last_verse_id,
            'verses_count' => $page->verses_count,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function resourceRef(QuranResource $resource, ?string $language): array
    {
        $language ??= 'en';
        $translated = $resource->getTranslation('name', $language, false);

        return array_filter([
            'id' => $resource->id,
            'name' => $resource->name,
            'author_name' => $resource->author_name,
            'slug' => $resource->slug,
            'language_name' => $this->languageName($resource->language_iso),
            'translated_name' => ['name' => $translated ?? $resource->name, 'language_name' => $this->languageName($translated === null ? 'en' : $language)],
            'style' => $resource->style?->value,
            'qirat' => $resource->qirat,
        ], static fn (mixed $v): bool => $v !== null);
    }

    /**
     * @return array<string, mixed>
     */
    private function chapterFileShape(object $file, bool $withTimestamps, bool $segments = false): array
    {
        $shape = [
            'id' => (int) $file->id,
            'chapter_id' => (int) $file->chapter_id,
            'file_size' => $file->file_size === null ? null : (int) $file->file_size,
            'format' => $file->format,
            'audio_url' => AudioService::absolutize($file->url),
            'duration' => $file->duration_ms === null ? null : (int) $file->duration_ms,
        ];

        if ($withTimestamps) {
            $shape['timestamps'] = $file->verseTimestamps->map(static fn ($t): array => array_filter([
                'verse_key' => (string) $t->verse?->verse_key,
                'timestamp_from' => (int) $t->timestamp_from,
                'timestamp_to' => (int) $t->timestamp_to,
                'duration' => $t->duration === null ? null : (int) $t->duration,
                'segments' => $segments ? $t->segments : null,
            ], static fn (mixed $v): bool => $v !== null))->values()->all();
        }

        return $shape;
    }

    /**
     * @param  list<array<string, mixed>>  $hits
     * @return list<array<string, mixed>>
     */
    private function searchVerses(array $hits): array
    {
        return array_map(static fn (array $hit): array => [
            'result_type' => 'ayah',
            'key' => $hit['verse_key'],
            'name' => $hit['text'],
            'arabic' => $hit['text'],
            'isArabic' => true,
            'isTransliteration' => false,
            'highlighted' => $hit['translation'],
        ], $hits);
    }

    private function languageName(?string $iso): ?string
    {
        if ($iso === null) {
            return null;
        }
        $this->languageNames ??= array_map('strtolower', $this->languages->options());

        return $this->languageNames[$iso] ?? $iso;
    }

    /**
     * @param  Collection<int, Verse>  $items
     */
    private function paginate(Collection $items, VerseApiParams $params): LengthAwarePaginator
    {
        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items->forPage($params->page, $params->perPage)->values(), $items->count(), $params->perPage, $params->page,
        );
    }

    /**
     * @return array{per_page: int, current_page: int, next_page: int|null, total_pages: int, total_records: int}
     */
    private static function pagination(LengthAwarePaginator $paginator): array
    {
        return [
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'next_page' => $paginator->hasMorePages() ? $paginator->currentPage() + 1 : null,
            'total_pages' => $paginator->lastPage(),
            'total_records' => $paginator->total(),
        ];
    }

    public static function plural(SectionType $type): string
    {
        return $type->value.'s';
    }

    public static function kindForSegment(string $segment): ?ResourceKind
    {
        return match ($segment) {
            'translations' => ResourceKind::TRANSLATION,
            'tafsirs' => ResourceKind::TAFSIR,
            'recitations' => ResourceKind::RECITATION,
            'chapter_reciters' => ResourceKind::CHAPTER_RECITER,
            'chapter_infos' => ResourceKind::CHAPTER_INFO,
            'verse_media' => ResourceKind::VERSE_MEDIA,
            default => null,
        };
    }
}
