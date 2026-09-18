<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

use App\Domain\Quran\DTOs\ReaderOptions;
use App\Domain\Quran\DTOs\VerseEditData;
use App\Domain\Quran\Enums\SectionType;
use App\Domain\Quran\Models\Verse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface VerseRepositoryInterface
{
    /** Columns every verse list selects. */
    public const BASE_COLUMNS = [
        'id', 'chapter_id', 'verse_number', 'verse_key', 'verse_index', 'juz_number', 'hizb_number', 'rub_el_hizb_number',
        'ruku_number', 'manzil_number', 'page_number', 'sajdah_type', 'sajdah_number', 'image_url', 'image_path', 'image_width',
    ];

    /**
     * @param  array{chapter?: int|null, juz?: int|null, page?: int|null, sajdah?: bool|null, locked?: bool|null, q?: string|null}  $filters
     */
    public function paginateAdmin(array $filters, int $perPage): LengthAwarePaginator;

    /**
     * One verse with everything an editor sees: chapter, words + glosses,
     * translations, tafsirs and audio files, each with its resource.
     */
    public function findForAdmin(int $id): ?Verse;

    public function update(int $id, VerseEditData $data): Verse;

    /**
     * Set a word's glosses: resource id => text. Empty text deletes the gloss; ids that
     * are not word-translation/transliteration resources are ignored.
     *
     * @param  array<int, string|null>  $glosses
     */
    public function updateWordGlosses(int $wordId, array $glosses): int;

    /**
     * @param  list<int>  $ids
     */
    public function setLocked(array $ids, bool $locked): int;

    /**
     * @param  list<string>  $extraColumns
     * @return Collection<int, Verse>
     */
    public function byChapter(int $chapterId, array $extraColumns = []): Collection;

    /**
     * @param  list<string>  $extraColumns
     */
    public function pageByChapter(int $chapterId, int $page, int $perPage, array $extraColumns = []): LengthAwarePaginator;

    /**
     * @param  list<string>  $extraColumns
     */
    public function pageBySection(SectionType $type, int $number, int $page, int $perPage, array $extraColumns = []): LengthAwarePaginator;

    /**
     * Verses of a mushaf page. For a non-default mushaf the range comes from its page
     * map.
     *
     * @param  list<string>  $extraColumns
     * @return Collection<int, Verse>
     */
    public function byPage(int $pageNumber, ?int $mushafId, array $extraColumns = []): Collection;

    /**
     * @param  list<string>  $extraColumns
     */
    public function pageByRange(int $fromId, int $toId, int $page, int $perPage, array $extraColumns = []): LengthAwarePaginator;

    /**
     * @param  list<string>  $extraColumns
     */
    public function byKey(string $verseKey, array $extraColumns = []): ?Verse;

    /**
     * @param  list<string>  $keys
     * @param  list<string>  $extraColumns
     * @return Collection<int, Verse>
     */
    public function byKeys(array $keys, array $extraColumns = []): Collection;

    /**
     * @param  array<string, int>  $axisFilter  e.g. ['chapter_id' => 2] or ['juz_number' => 30]
     * @param  list<string>  $extraColumns
     */
    public function random(array $axisFilter, array $extraColumns = []): ?Verse;

    /**
     * Verse references (key, chapter, number) for one chapter or the whole Quran.
     *
     * Deliberately plain rows, not models: the only caller expands a URL template
     * for every verse, and hydrating 6,236 Verse models to read three columns is
     * pure waste.
     *
     * @return list<array{verse_key: string, chapter_id: int, verse_number: int}>
     */
    public function references(?int $chapterId): array;

    /**
     * Whole-Quran (or filtered) id + key + one text column, for the raw scripts API.
     *
     * @param  array<string, int|string>  $axisFilter
     * @return Collection<int, object>
     */
    public function scriptRows(string $column, array $axisFilter): Collection;

    /**
     * Words, glosses, translations, tafsirs and audio for the given verses, in a
     * fixed number of queries regardless of how many verses there are.
     *
     * @param  list<int>  $verseIds
     * @return array<int, array{words: list<array<string, mixed>>, translations: array<int, array{id: int, text: string, foot_notes: array<string, string>|null}>, tafsirs: array<int, array{id: int, text: string}>, audio: ?array{url: string, segments: ?array<int, array<int, int>>, duration_ms: ?int}}>
     */
    public function payload(array $verseIds, ReaderOptions $options): array;
}
