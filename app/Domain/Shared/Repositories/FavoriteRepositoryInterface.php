<?php

declare(strict_types=1);

namespace App\Domain\Shared\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

/**
 * Favorite (bookmark) repository contract.
 *
 * Lives in Shared so domain services can depend on the abstraction; the
 * concrete implementation (which touches the Favorite Eloquent model) lives in
 * the Infrastructure layer. Deliberately does NOT extend the generic
 * RepositoryInterface — its surface is bookmark-specific and must not leak the
 * Favorite model type into Shared.
 */
interface FavoriteRepositoryInterface
{
    /**
     * Paginate a user's favorites, optionally constrained to a single morph
     * class, newest first. The polymorphic target is eager-loaded to avoid N+1.
     *
     * @param  class-string<Model>|null  $favorableType  Morph class to filter by, or null for all.
     */
    public function paginateForUser(int $userId, ?string $favorableType, int $perPage): LengthAwarePaginator;

    /**
     * Per-type counts for a user, keyed by morph class string.
     *
     * @return array<string, int>
     */
    public function countsByTypeForUser(int $userId): array;

    /**
     * Total number of favorites a user has.
     */
    public function totalForUser(int $userId): int;

    /**
     * Whether the user already bookmarked the given content.
     *
     * @param  class-string<Model>  $favorableType
     */
    public function exists(int $userId, string $favorableType, int $favorableId): bool;

    /**
     * All content ids the user has bookmarked for one morph class. Used to
     * hydrate initial button state on listing pages in a single query.
     *
     * @param  class-string<Model>  $favorableType
     * @return list<int>
     */
    public function idsForUser(int $userId, string $favorableType): array;

    /**
     * Idempotently create a favorite. Returns true if a new row was inserted,
     * false if it already existed.
     *
     * @param  class-string<Model>  $favorableType
     */
    public function add(int $userId, string $favorableType, int $favorableId): bool;

    /**
     * Remove a user's favorite for the given content. Returns the number of
     * rows deleted (0 or 1) — ownership is enforced by the user_id predicate.
     *
     * @param  class-string<Model>  $favorableType
     */
    public function remove(int $userId, string $favorableType, int $favorableId): int;
}
