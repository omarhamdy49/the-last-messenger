<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Repositories;

use App\Domain\Hadith\DTOs\SearchCriteria;
use App\Domain\Hadith\Enums\GradeScope;
use App\Domain\Hadith\Enums\HadithGrade;
use App\Domain\Hadith\Models\Hadith;
use App\Domain\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;

class HadithRepository extends BaseRepository implements HadithRepositoryInterface
{
    /**
     * Columns a detail render needs — nothing more. `select *` on this table
     * would be harmless today but is the habit that grows a 20-column read.
     *
     * @var list<string>
     */
    private const DETAIL_COLUMNS = [
        'id', 'collection_id', 'book_id', 'chapter_id', 'primary_narrator_id', 'hadith_number',
        'in_book_number', 'volume_number', 'sunnah_urn', 'sunnah_urn_en',
        'display_grade', 'is_published', 'source_reference', 'source_metadata', 'source_unavailable_fields',
    ];

    /**
     * Foreign keys must be in the parent select for these shorthands to resolve.
     *
     * @var array<string, mixed>
     */
    private const DETAIL_RELATIONS = [
        // is_published is selected because HadithVisibility::allowsHadith() reads
        // it. Omitting a column the caller checks makes it silently NULL, which
        // reads as "unpublished" and 404s every page.
        'collection:id,code,name,reference_template,has_volumes,is_published',
        'book:id,collection_id,book_number,title',
        'chapter:id,book_id,chapter_number,title',
        'primaryNarrator:id,slug,name',
        // search_body is the diacritic-folded form; list pages put it on the
        // card so the in-page filter folds the same way the server does.
        'texts:id,hadith_id,locale,body,search_body,narrated_by,sanad',
        'gradings:id,hadith_id,scholar_id,grade,scope,part_label,raw_grade,locale',
        'gradings.scholar:id,name',
    ];

    protected function getModel(): Model
    {
        return new Hadith;
    }

    public function findCanonical(string $collectionCode, string $hadithNumber, bool $publishedOnly = true): ?Hadith
    {
        return Hadith::query()
            ->select(self::DETAIL_COLUMNS)
            ->with(self::DETAIL_RELATIONS)
            ->whereHas('collection', fn (Builder $query) => $query
                ->where('code', $collectionCode)
                ->when($publishedOnly, fn (Builder $q) => $q->where('is_published', true)))
            ->where('hadith_number', $hadithNumber)
            ->when($publishedOnly, fn (Builder $query) => $query->where('is_published', true))
            // A number can cover two different hadiths — Tirmidhi has 52 such
            // pairs — so the canonical URL resolves to the first in reading
            // order, which is what sunnah.com's own /tirmidhi:1322 shows.
            ->orderBy('in_book_number')
            ->orderBy('id')
            ->first();
    }

    public function findByUrn(int $urn, bool $publishedOnly = true): ?Hadith
    {
        return Hadith::query()
            ->select(self::DETAIL_COLUMNS)
            ->with(self::DETAIL_RELATIONS)
            // Both columns are separately UNIQUE, so this resolves either
            // language's permalink without a union.
            ->where(fn (Builder $query) => $query
                ->where('sunnah_urn', $urn)
                ->orWhere('sunnah_urn_en', $urn))
            ->when($publishedOnly, fn (Builder $query) => $query->where('is_published', true))
            ->first();
    }

    public function paginateForBook(int $bookId, int $perPage, bool $publishedOnly = true): LengthAwarePaginator
    {
        return Hadith::query()
            ->select(self::DETAIL_COLUMNS)
            ->with(self::DETAIL_RELATIONS)
            ->where('book_id', $bookId)
            ->when($publishedOnly, fn (Builder $query) => $query->where('is_published', true))
            ->orderBy('in_book_number')
            ->paginate($perPage);
    }

    public function paginateForNarrator(int $narratorId, int $perPage = 20): LengthAwarePaginator
    {
        return Hadith::query()
            ->select(self::DETAIL_COLUMNS)
            ->with(self::DETAIL_RELATIONS)
            ->where('primary_narrator_id', $narratorId)
            ->where('is_published', true)
            ->whereHas('collection', fn (Builder $query) => $query->where('is_published', true))
            ->orderBy('id')
            ->paginate($perPage);
    }

    public function randomPublished(?HadithGrade $grade = null): ?Hadith
    {
        $base = Hadith::query()->where('is_published', true)
            ->when($grade !== null, fn (Builder $query) => $query->where('display_grade', $grade));

        // Two cheap index reads instead of ORDER BY RAND(), which materialises
        // and sorts every matching row. Both hit (is_published, id).
        $count = (clone $base)->count();

        if ($count === 0) {
            return null;
        }

        $id = (clone $base)->select('id')->skip(random_int(0, $count - 1))->take(1)->value('id');

        return $id === null ? null : Hadith::query()
            ->select(self::DETAIL_COLUMNS)
            ->with(self::DETAIL_RELATIONS)
            ->find($id);
    }

    public function search(string $expression, SearchCriteria $criteria): LengthAwarePaginator
    {
        if ($expression === '') {
            // An empty AGAINST expression is a MySQL syntax error, and a query
            // of only stop-words legitimately matches nothing.
            return new \Illuminate\Pagination\LengthAwarePaginator(
                [], 0, $criteria->perPage(), $criteria->page(),
                ['path' => Paginator::resolveCurrentPath()],
            );
        }

        $matches = DB::table('hadith_texts')
            ->select('hadith_id')
            ->selectRaw('MAX(MATCH(search_body) AGAINST (? IN BOOLEAN MODE)) AS score', [$expression])
            ->whereRaw('MATCH(search_body) AGAINST (? IN BOOLEAN MODE)', [$expression])
            ->when($criteria->locale !== null, fn ($query) => $query->where('locale', $criteria->locale))
            // One row per hadith: a bilingual record matches twice and must not
            // occupy two result slots.
            ->groupBy('hadith_id');

        $query = Hadith::query()
            ->select(self::DETAIL_COLUMNS)
            ->addSelect('matches.score')
            ->with(self::DETAIL_RELATIONS)
            ->joinSub($matches, 'matches', 'matches.hadith_id', '=', 'hadith_hadiths.id')
            ->where('hadith_hadiths.is_published', true)
            ->when($criteria->grade !== null, fn (Builder $q) => $q->where('display_grade', $criteria->grade))
            ->when($criteria->collectionCode !== null, fn (Builder $q) => $q
                ->whereHas('collection', fn (Builder $c) => $q->getQuery() && $c->where('code', $criteria->collectionCode)))
            ->orderByDesc('matches.score')
            ->orderBy('hadith_hadiths.id');

        return $query->paginate($criteria->perPage(), ['*'], 'page', $criteria->page());
    }

    public function recomputeDisplayGrade(int $hadithId): ?HadithGrade
    {
        // data-model.md §4: only hadith-scope claims count; exactly one distinct
        // grade wins; zero rows, conflicting grades, or any part-labelled claim
        // yields NULL. Computed in one aggregate rather than by loading rows.
        $summary = DB::table('hadith_gradings')
            ->where('hadith_id', $hadithId)
            ->where('scope', GradeScope::HADITH->value)
            ->selectRaw('COUNT(DISTINCT grade) AS distinct_grades')
            ->selectRaw('MIN(grade) AS only_grade')
            ->selectRaw('SUM(part_label IS NOT NULL) AS labelled')
            ->first();

        $grade = ((int) ($summary->distinct_grades ?? 0) === 1 && (int) ($summary->labelled ?? 0) === 0)
            ? HadithGrade::tryFrom((string) $summary->only_grade)
            : null;

        if ($grade === HadithGrade::UNKNOWN) {
            $grade = null;
        }

        // Not ->update() on the model: display_grade is absent from $fillable by
        // design, and this is the one place allowed to write it.
        DB::table('hadith_hadiths')->where('id', $hadithId)->update(['display_grade' => $grade?->value]);

        return $grade;
    }
}
