<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Repositories;

use App\Domain\Hadith\Enums\HadithGrade;
use App\Domain\Hadith\Models\Hadith;
use App\Domain\Shared\Contracts\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface HadithRepositoryInterface extends RepositoryInterface
{
    /**
     * The canonical lookup, hitting UNIQUE (collection_id, hadith_number).
     * `$hadithNumber` may be non-numeric (`1a`).
     *
     * Returns the row with `collection`, `book`, `chapter`, `texts` and
     * `gradings.scholar` eager-loaded, so HadithDetail::fromModel never queries.
     */
    public function findCanonical(string $collectionCode, string $hadithNumber, bool $publishedOnly = true): ?Hadith;

    /**
     * Resolve an external sunnah.com permalink against either urn column.
     * ~35% of records carry neither, so a miss is ordinary, not an error.
     */
    public function findByUrn(int $urn, bool $publishedOnly = true): ?Hadith;

    /**
     * A book's hadiths in reading order, using the
     * (book_id, is_published, in_book_number) index.
     *
     * Query count must be constant with respect to `$perPage`.
     */
    public function paginateForBook(int $bookId, int $perPage, bool $publishedOnly = true): LengthAwarePaginator;

    /**
     * One random published hadith.
     *
     * Must use the (is_published, id) index rather than ORDER BY RAND(), which
     * sorts the whole table on every call.
     */
    public function randomPublished(?HadithGrade $grade = null): ?Hadith;

    /**
     * Recompute `display_grade` from this hadith's `hadith`-scope gradings and
     * persist it. Must run inside the caller's transaction alongside the grading
     * write, or a concurrent read sees a grade that contradicts its claims.
     */
    public function recomputeDisplayGrade(int $hadithId): HadithGrade;
}
