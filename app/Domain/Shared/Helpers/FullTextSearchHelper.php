<?php

declare(strict_types=1);

namespace App\Domain\Shared\Helpers;

use Illuminate\Database\Eloquent\Builder;

/**
 * FullTextSearchHelper
 *
 * Applies MySQL MATCH...AGAINST full-text search to Eloquent or query-builder
 * instances. Falls back to LIKE when the term is shorter than MySQL's minimum
 * token length (3 characters by default).
 *
 * Usage — Eloquent Builder:
 *   FullTextSearchHelper::apply($query, ['text', 'text_notashkeel'], $term);
 *
 * Usage — DB::table() query builder:
 *   FullTextSearchHelper::applyRaw($query, ['text', 'text_notashkeel'], $term);
 *
 * Rules:
 *   - Only pass columns that are covered by a FULLTEXT index.
 *   - All columns in a single call must belong to the same index and same table.
 *   - Non-FULLTEXT columns (slug, hadith_number, etc.) must keep LIKE — do not
 *     pass them here.
 */
final class FullTextSearchHelper
{
    /**
     * MySQL FULLTEXT minimum token length.
     * Default is 3 for InnoDB (ft_min_word_len / innodb_ft_min_token_size).
     */
    private const MIN_TOKEN_LENGTH = 3;

    /**
     * Apply MATCH...AGAINST full-text search to an Eloquent Builder query.
     * Falls back to LIKE when the term is shorter than MIN_TOKEN_LENGTH.
     *
     * @param  array<string>  $columns  e.g. ['text', 'text_notashkeel']
     */
    public static function apply(Builder $query, array $columns, string $term): Builder
    {
        $term = trim($term);

        if (strlen($term) < self::MIN_TOKEN_LENGTH
            || $query->getConnection()->getDriverName() !== 'mysql') {
            return $query->where(function (Builder $q) use ($columns, $term) {
                foreach ($columns as $i => $column) {
                    $method = $i === 0 ? 'where' : 'orWhere';
                    $q->{$method}($column, 'LIKE', "%{$term}%");
                }
            });
        }

        $columnList = implode(', ', $columns);
        $safeTerm = self::escapeFullText($term);

        return $query->whereRaw(
            "MATCH({$columnList}) AGAINST(? IN BOOLEAN MODE)",
            ["+{$safeTerm}*"]
        );
    }

    /**
     * Apply MATCH...AGAINST full-text search to a DB::table() query builder.
     * Falls back to LIKE when the term is shorter than MIN_TOKEN_LENGTH.
     *
     * @param  array<string>  $columns
     */
    public static function applyRaw(
        \Illuminate\Database\Query\Builder $query,
        array $columns,
        string $term
    ): \Illuminate\Database\Query\Builder {
        $term = trim($term);

        if (strlen($term) < self::MIN_TOKEN_LENGTH
            || $query->getConnection()->getDriverName() !== 'mysql') {
            return $query->where(function ($q) use ($columns, $term) {
                foreach ($columns as $i => $column) {
                    $method = $i === 0 ? 'where' : 'orWhere';
                    $q->{$method}($column, 'LIKE', "%{$term}%");
                }
            });
        }

        $columnList = implode(', ', $columns);
        $safeTerm = self::escapeFullText($term);

        return $query->whereRaw(
            "MATCH({$columnList}) AGAINST(? IN BOOLEAN MODE)",
            ["+{$safeTerm}*"]
        );
    }

    /**
     * Strip FULLTEXT boolean operators that would cause a MySQL syntax error
     * if left in the query string.
     *
     * Removed: + - > < ( ) ~ * " @
     */
    private static function escapeFullText(string $term): string
    {
        return preg_replace('/[+\-><()\~*"@]+/', ' ', $term);
    }
}
