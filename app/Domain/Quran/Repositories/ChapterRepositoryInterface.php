<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

use App\Domain\Quran\DTOs\ChapterData;
use App\Domain\Quran\Models\Chapter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ChapterRepositoryInterface
{
    /**
     * All 114 chapters with names (cached per locale).
     *
     * @return Collection<int, Chapter>
     */
    public function all(bool $publishedOnly = true): Collection;

    public function find(int $id): ?Chapter;

    /**
     * Chapter with its location, info rows (+ resource) and audio files (+ reciter) for the admin show page.
     */
    public function findForAdmin(int $id): ?Chapter;

    /**
     * @return array<int, int> chapter number => juz of its first verse
     */
    public function firstJuzMap(): array;

    /**
     * The chapter introduction for a locale: an active chapter-info resource in that
     * language first, then Arabic, then any language.
     *
     * @return array{text: string, short_text: ?string, source: ?string, language_iso: ?string, resource_id: int, resource_name: string}|null
     */
    /**
     * Every available introduction for a chapter, one per language, best source first.
     *
     * @return list<array{language: string, text: string, source: ?string, resource: string}>
     */
    public function infos(int $chapter): array;

    public function info(int $chapter, string $locale, ?int $resourceId = null): ?array;

    /**
     * @param  array{search?: string|null, revelation_place?: string|null, published?: string|null}  $filters
     */
    public function paginateAdmin(array $filters, int $perPage): LengthAwarePaginator;

    public function update(int $id, ChapterData $data): Chapter;

    /**
     * Chapters whose number, Arabic/simple name or any localized name matches.
     *
     * @return Collection<int, Chapter>
     */
    public function search(string $term, int $limit): Collection;

    /**
     * @return array{total: int, makkah: int, madinah: int, verses: int, published: int}
     */
    public function stats(): array;
}
