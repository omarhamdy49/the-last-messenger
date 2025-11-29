<?php

declare(strict_types=1);

namespace App\Domain\Shared\Application\Queries;

/**
 * Query Handler Interface
 *
 * Handlers process queries and return data.
 *
 * @template TQuery of Query
 * @template TResult
 */
interface QueryHandler
{
    /**
     * Handle the query
     *
     * @param TQuery $query
     * @return TResult
     */
    public function handle(Query $query): mixed;
}
