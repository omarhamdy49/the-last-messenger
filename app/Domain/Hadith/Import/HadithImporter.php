<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Import;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithChapter;
use App\Domain\Hadith\Models\HadithCollection;
use App\Domain\Hadith\Models\HadithGrading;
use App\Domain\Hadith\Models\HadithText;
use App\Domain\Hadith\Repositories\HadithRepositoryInterface;
use App\Domain\Hadith\Repositories\ScholarRepositoryInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Imports one extracted book file into the schema.
 *
 * Idempotent by design: identity is `(collection_id, hadith_number)`, so running
 * the same file twice changes nothing. Child rows are reconciled rather than
 * appended — a corrected source that drops a grading must drop it here too, or
 * the record accumulates verdicts nobody asserts any more.
 */
final class HadithImporter
{
    /**
     * Columns the source owns. A routine re-import overwrites these freely,
     * because the source is their authority.
     *
     * @var list<string>
     */
    private const SOURCE_OWNED = [
        'in_book_number', 'volume_number', 'sunnah_urn', 'sunnah_urn_en', 'chapter_id', 'book_id',
    ];

    /**
     * Columns an editor owns. A re-import must NOT touch these, or a night's
     * curation is silently undone by a routine refresh. `--reset` overrides.
     *
     * @var list<string>
     */
    private const EDITOR_OWNED = ['is_published'];

    /** @var array<string, int> */
    private array $counts = [];

    public function __construct(
        private readonly SourceFileReader $reader,
        private readonly HadithRecordMapper $mapper,
        private readonly HadithRepositoryInterface $hadiths,
        private readonly ScholarRepositoryInterface $scholars,
    ) {}

    /**
     * @return array<string, int> per-table counts for the import run
     */
    public function importFile(string $path, string $collectionCode, bool $reset = false): array
    {
        $payload = $this->reader->read($path);

        $collection = HadithCollection::query()->where('code', $collectionCode)->first();

        if ($collection === null) {
            throw new RuntimeException("Unknown collection '{$collectionCode}' — seed it before importing.");
        }

        // One transaction per book: a book is the unit a reader sees, so a
        // half-imported one is worse than none. Transactions live in this
        // boundary class rather than in the command.
        return DB::transaction(function () use ($payload, $collection, $reset): array {
            $book = $this->upsertBook($collection, $payload);
            $chapters = $this->upsertChapters($book, $payload['chapters']);

            foreach ($payload['records'] as $record) {
                $this->upsertRecord($collection, $book, $chapters, $record, $reset);
            }

            return $this->counts;
        });
    }

    /**
     * @return list<string>
     */
    public function unmappedGrades(): array
    {
        return $this->mapper->unmappedGrades();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function upsertBook(HadithCollection $collection, array $payload): HadithBook
    {
        $mapped = $this->mapper->mapBook($payload['book'], (string) ($payload['source']['book_number'] ?? '0'));

        $book = HadithBook::query()->firstOrNew([
            'collection_id' => $collection->id,
            'book_number' => $mapped['book_number'],
        ]);

        $book->fill([
            'title' => $mapped['title'] !== [] ? $mapped['title'] : ($book->getTranslations('title') ?: ['ar' => $mapped['book_number']]),
            'sort_order' => $book->sort_order ?: $this->sortOrderFor($mapped['book_number']),
        ]);

        // A book nobody has published stays unpublished; publication is editorial.
        $book->is_published = $book->exists ? $book->is_published : true;
        $this->tally($book->exists ? 'books_updated' : 'books_created');
        $book->save();

        return $book;
    }

    /**
     * @param  list<array<string, mixed>>  $chapters
     * @return array<string, HadithChapter> keyed by chapter_number
     */
    private function upsertChapters(HadithBook $book, array $chapters): array
    {
        $byNumber = [];

        foreach ($chapters as $index => $chapter) {
            $mapped = $this->mapper->mapChapter($chapter, $index + 1);

            $row = HadithChapter::query()->firstOrNew([
                'book_id' => $book->id,
                'chapter_number' => $mapped['chapter_number'],
            ]);

            $row->fill([
                'sort_order' => $mapped['sort_order'],
                'title' => $mapped['title'] !== [] ? $mapped['title'] : ($row->getTranslations('title') ?: ['ar' => $mapped['chapter_number']]),
                'intro' => $mapped['intro'] !== [] ? $mapped['intro'] : $row->getTranslations('intro'),
            ]);
            $row->is_published = $row->exists ? $row->is_published : true;

            $this->tally($row->exists ? 'chapters_updated' : 'chapters_created');
            $row->save();

            $byNumber[$mapped['chapter_number']] = $row;
        }

        return $byNumber;
    }

    /**
     * @param  array<string, HadithChapter>  $chapters
     * @param  array<string, mixed>  $record
     */
    private function upsertRecord(
        HadithCollection $collection,
        HadithBook $book,
        array $chapters,
        array $record,
        bool $reset,
    ): void {
        $mapped = $this->mapper->mapRecord($record);
        $number = $mapped['hadith']['hadith_number'];

        if ($number === '') {
            $this->tally('records_skipped_no_number');

            return;
        }

        $hadith = Hadith::query()->firstOrNew([
            'collection_id' => $collection->id,
            'hadith_number' => $number,
        ]);

        $attributes = $mapped['hadith'];
        unset($attributes['hadith_number']);
        $attributes['book_id'] = $book->id;
        $attributes['chapter_id'] = $chapters[$mapped['chapter_number']]->id ?? null;

        $existed = $hadith->exists;

        foreach (self::SOURCE_OWNED as $column) {
            if (array_key_exists($column, $attributes)) {
                $hadith->{$column} = $attributes[$column];
            }
        }

        // Editor-owned fields survive a routine re-import; --reset restores the
        // source's view of them.
        if (! $existed || $reset) {
            foreach (self::EDITOR_OWNED as $column) {
                $hadith->{$column} = true;
            }
        }

        $hadith->save();
        $this->tally($existed ? 'hadiths_updated' : 'hadiths_created');

        $this->reconcileTexts($hadith, $mapped['texts']);
        $this->reconcileGradings($hadith, $mapped['gradings']);

        // Recomputed in the same transaction as the grading writes, so no reader
        // can observe a display_grade that contradicts the rows behind it.
        $this->hadiths->recomputeDisplayGrade($hadith->id);
    }

    /**
     * @param  list<array<string, mixed>>  $texts
     */
    private function reconcileTexts(Hadith $hadith, array $texts): void
    {
        $keep = [];

        foreach ($texts as $text) {
            $row = HadithText::query()->firstOrNew([
                'hadith_id' => $hadith->id,
                'locale' => $text['locale'],
            ]);
            $row->fill($text);
            $row->save();

            $keep[] = $row->id;
            $this->tally('texts_written');
        }

        // A locale the corrected source no longer carries must disappear, not
        // linger as a stale translation next to a fixed one.
        $removed = HadithText::query()
            ->where('hadith_id', $hadith->id)
            ->when($keep !== [], fn ($query) => $query->whereNotIn('id', $keep))
            ->delete();

        $this->tally('texts_removed', $removed);
    }

    /**
     * @param  list<array<string, mixed>>  $gradings
     */
    private function reconcileGradings(Hadith $hadith, array $gradings): void
    {
        // Replaced wholesale: a grading has no stable natural key, so matching
        // old to new would guess. They are cheap to rewrite and derived entirely
        // from raw_grade, which the source owns.
        $this->tally('gradings_removed', HadithGrading::query()->where('hadith_id', $hadith->id)->delete());

        foreach ($gradings as $grading) {
            $scholarId = null;

            if ($grading['attributed_to'] !== null) {
                $scholarId = $this->scholars->resolveByName($grading['attributed_to'])->id;
            }

            HadithGrading::query()->create([
                'hadith_id' => $hadith->id,
                'scholar_id' => $scholarId,
                'grade' => $grading['grade'],
                'scope' => $grading['scope'],
                'part_label' => $grading['part_label'],
                'raw_grade' => $grading['raw_grade'],
                'locale' => $grading['locale'],
            ]);

            $this->tally('gradings_created');
        }
    }

    /**
     * Books sort by their numeric prefix where they have one, so `35b` follows
     * `35`, and named books (`introduction`, `letter`) lead.
     */
    private function sortOrderFor(string $bookNumber): int
    {
        return preg_match('/^(\d+)/', $bookNumber, $m) === 1 ? (int) $m[1] : 0;
    }

    private function tally(string $key, int $by = 1): void
    {
        $this->counts[$key] = ($this->counts[$key] ?? 0) + $by;
    }
}
