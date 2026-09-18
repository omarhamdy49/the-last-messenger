<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * ponytail: LIKE over the 6,236 verses and over translation rows filtered by resource
 * (≈ 6,236 rows each). Correct inside test transactions, fast enough at this size;
 * switch to the existing FULLTEXT indexes (qv_text_ft, qt_text_ft) if search traffic grows.
 */
final class EloquentSearchRepository implements SearchRepositoryInterface
{
    public function verses(array $arabicTerms, array $translationTerms, array $translationIds, int $page, int $size): array
    {
        $arabicTerms = array_values(array_filter($arabicTerms, static fn (string $t): bool => mb_strlen($t) >= 2));
        $translationTerms = array_values(array_filter($translationTerms, static fn (string $t): bool => mb_strlen($t) >= 2));
        $firstTranslation = $translationIds[0] ?? null;

        $query = DB::table('quran_verses as v')
            ->when($firstTranslation !== null, fn (Builder $q) => $q->leftJoin('quran_translations as t', fn ($j) => $j
                ->on('t.verse_id', '=', 'v.id')->whereIn('t.resource_id', $translationIds)))
            ->where(function (Builder $q) use ($arabicTerms, $translationTerms, $firstTranslation): void {
                if ($arabicTerms !== []) {
                    $q->orWhere(function (Builder $a) use ($arabicTerms): void {
                        foreach ($arabicTerms as $term) {
                            $a->where('v.text_imlaei_simple', 'like', '%'.self::escape($term).'%');
                        }
                    });
                }
                if ($firstTranslation !== null && $translationTerms !== []) {
                    $q->orWhere(function (Builder $t) use ($translationTerms): void {
                        foreach ($translationTerms as $term) {
                            $t->where('t.text', 'like', '%'.self::escape($term).'%');
                        }
                    });
                }
                if ($arabicTerms === [] && ($firstTranslation === null || $translationTerms === [])) {
                    $q->whereRaw('1 = 0');
                }
            });

        $total = (clone $query)->distinct()->count('v.id');

        $rows = $query
            ->select(['v.id', 'v.verse_key', 'v.chapter_id', 'v.verse_number', 'v.text_uthmani',
                $firstTranslation !== null ? DB::raw('MIN(t.text) as translation') : DB::raw('NULL as translation')])
            ->groupBy('v.id', 'v.verse_key', 'v.chapter_id', 'v.verse_number', 'v.text_uthmani')
            ->orderBy('v.id')
            ->forPage($page, $size)
            ->get()
            ->all();

        return ['rows' => $rows, 'total' => $total];
    }

    private static function escape(string $term): string
    {
        return addcslashes($term, '%_\\');
    }
}
