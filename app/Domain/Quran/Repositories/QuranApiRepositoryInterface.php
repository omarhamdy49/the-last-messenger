<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

use App\Domain\Quran\Models\MushafPage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Bulk reads behind the QF-shaped public API that the reader repositories do not cover.
 * Axis filters use verse columns: chapter_id, juz_number, hizb_number, rub_el_hizb_number,
 * manzil_number, ruku_number, page_number, verse_key.
 */
interface QuranApiRepositoryInterface
{
    /**
     * @return Collection<int, MushafPage>
     */
    public function pages(int $mushafId): Collection;

    /**
     * Pages of a mushaf that overlap a verse id range.
     *
     * @return Collection<int, MushafPage>
     */
    public function pagesInRange(int $mushafId, int $fromVerseId, int $toVerseId): Collection;

    /**
     * @param  array<string, int|string>  $axisFilter
     * @return array{0: int, 1: int}|null lowest and highest verse id matching the filter
     */
    public function verseIdRange(array $axisFilter): ?array;

    /**
     * @param  array<string, int|string>  $axisFilter
     * @return Collection<int, object{id: int, verse_id: int, verse_key: string, text: string, foot_notes: ?string}>
     */
    public function translationRows(int $resourceId, array $axisFilter): Collection;

    /**
     * @param  array<string, int|string>  $axisFilter
     */
    public function tafsirRows(int $resourceId, array $axisFilter, int $page, int $perPage): LengthAwarePaginator;

    /**
     * @return array{id: string, text: string, resource_id: int}|null
     */
    public function footNote(string $id): ?array;

    /**
     * @param  list<int>  $verseIds
     * @return array<int, string> verse id => verse key
     */
    public function verseKeys(array $verseIds): array;
}
