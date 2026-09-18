<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

use App\Domain\Quran\Models\Mushaf;
use App\Domain\Quran\Models\MushafPage;
use Illuminate\Support\Collection;

interface MushafRepositoryInterface
{
    /**
     * @return Collection<int, Mushaf>
     */
    public function all(): Collection;

    /**
     * @return Collection<int, Mushaf>
     */
    public function active(): Collection;

    public function default(): ?Mushaf;

    public function find(int $id): ?Mushaf;

    public function page(int $mushafId, int $pageNumber): ?MushafPage;

    /**
     * Page number that holds a verse in this mushaf (null when the layout is not synced).
     */
    public function pageOfVerse(int $mushafId, int $verseId): ?int;

    /**
     * Words of one page grouped by line, in reading order, with the fields the reader
     * renders. For the default mushaf without a synced layout it falls back to
     * quran_words.
     *
     * @return array<int, list<array{word_id: int, verse_id: int, verse_key: string, position: int, text: ?string, char_type: string, code_v1: ?string, code_v2: ?string, text_qpc_hafs: ?string}>>
     */
    public function pageLines(int $mushafId, int $pageNumber): array;

    /**
     * A chapter's words laid out page → line in one query, Unicode text (the reader's
     * reading view). Falls back to quran_words for the default mushaf without a layout.
     *
     * @return list<array{page: int, lines: list<array{line: int, words: list<array{text: string, type: string, verse: int, location: string}>}>}>
     */
    public function chapterLines(int $mushafId, int $chapterId): array;

    /**
     * @param  array{is_active?: bool, is_default?: bool, name?: string}  $attributes
     */
    public function update(int $id, array $attributes): Mushaf;
}
