<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

interface SearchRepositoryInterface
{
    /**
     * Verses whose normalised Arabic text contains every Arabic term, or whose text in
     * one of the translation resources contains every translation term.
     *
     * @param  list<string>  $arabicTerms  already normalised
     * @param  list<string>  $translationTerms
     * @param  list<int>  $translationIds
     * @return array{rows: list<object>, total: int} rows: id, verse_key, chapter_id, verse_number, text_uthmani, translation
     */
    public function verses(array $arabicTerms, array $translationTerms, array $translationIds, int $page, int $size): array;
}
