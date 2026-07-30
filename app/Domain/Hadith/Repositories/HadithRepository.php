<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Repositories;

use App\Domain\Hadith\Enums\GradeScope;
use App\Domain\Hadith\Enums\HadithGrade;
use App\Domain\Hadith\Models\Hadith;
use App\Domain\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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
        'id', 'collection_id', 'book_id', 'chapter_id', 'hadith_number',
        'in_book_number', 'volume_number', 'sunnah_urn', 'sunnah_urn_en',
        'display_grade', 'is_published',
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
        'texts:id,hadith_id,locale,body,narrated_by,sanad',
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

    public function recomputeDisplayGrade(int $hadithId): HadithGrade
    {
        // data-model.md §4: only hadith-scope claims count; exactly one distinct
        // grade wins; zero rows, conflicting grades, or any part-labelled claim
        // yields `unknown`. Computed in one aggregate rather than by loading rows.
        $summary = DB::table('hadith_gradings')
            ->where('hadith_id', $hadithId)
            ->where('scope', GradeScope::HADITH->value)
            ->selectRaw('COUNT(DISTINCT grade) AS distinct_grades')
            ->selectRaw('MIN(grade) AS only_grade')
            ->selectRaw('SUM(part_label IS NOT NULL) AS labelled')
            ->first();

        $grade = ((int) ($summary->distinct_grades ?? 0) === 1 && (int) ($summary->labelled ?? 0) === 0)
            ? HadithGrade::from((string) $summary->only_grade)
            : HadithGrade::UNKNOWN;

        // Not ->update() on the model: display_grade is absent from $fillable by
        // design, and this is the one place allowed to write it.
        DB::table('hadith_hadiths')->where('id', $hadithId)->update(['display_grade' => $grade->value]);

        return $grade;
    }
}
