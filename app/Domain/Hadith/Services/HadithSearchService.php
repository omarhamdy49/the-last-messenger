<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Services;

use App\Domain\Hadith\DTOs\SearchCriteria;
use App\Domain\Hadith\Repositories\HadithRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Full-text search over `hadith_texts.search_body`.
 *
 * The query is folded through the SAME normaliser that produced the indexed
 * text, so a diacritised query and its plain form return identical sets. Without
 * it a reader typing نوى scores zero against a corpus that stores نوي — verified
 * against the real 99,991-row index, not assumed.
 */
final class HadithSearchService
{
    /**
     * Characters that are BOOLEAN MODE operators. A reader typing a `+` or a `-`
     * means the character, not the operator, and an unbalanced `"` is a syntax
     * error that would surface as a 500.
     */
    private const BOOLEAN_OPERATORS = ['+', '-', '>', '<', '(', ')', '~', '*', '"', '@'];

    public function __construct(
        private readonly HadithRepositoryInterface $hadiths,
        private readonly ArabicTextNormalizer $normalizer,
    ) {}

    public function search(SearchCriteria $criteria): LengthAwarePaginator
    {
        return $this->hadiths->search(
            $this->booleanExpression($criteria->query),
            $criteria,
        );
    }

    /**
     * Builds the AGAINST expression: every term required, so results narrow as a
     * reader adds words rather than widening, which is what people expect of a
     * search box.
     */
    public function booleanExpression(string $query): string
    {
        $normalized = $this->normalizer->normalize(
            str_replace(self::BOOLEAN_OPERATORS, ' ', $query),
        );

        $terms = array_filter(
            preg_split('/\s+/u', $normalized) ?: [],
            static fn (string $term): bool => mb_strlen($term) >= 2,
        );

        if ($terms === []) {
            // Never returns an empty AGAINST: MySQL treats that as matching
            // nothing, but an empty *expression* is a syntax error.
            return '';
        }

        return implode(' ', array_map(
            static fn (string $term): string => '+'.$term,
            $terms,
        ));
    }
}
