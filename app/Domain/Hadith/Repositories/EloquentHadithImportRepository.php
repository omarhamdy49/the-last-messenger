<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Repositories;

use App\Domain\Hadith\Import\Sunnah\FortyCollectionWriteResult;
use App\Domain\Hadith\Import\Sunnah\FortyImportState;
use App\Domain\Hadith\Import\Sunnah\MappedFortyCollection;
use App\Domain\Hadith\Import\Sunnah\MappedHadithData;
use App\Domain\Hadith\Import\Sunnah\MappedSunnahBook;
use App\Domain\Hadith\Import\Sunnah\MappedSunnahChapter;
use App\Domain\Hadith\Import\Sunnah\MappedSunnahCollection;
use App\Domain\Hadith\Import\Sunnah\SunnahCollectionData;
use App\Domain\Hadith\Import\Sunnah\SunnahCollectionDefinition;
use App\Domain\Hadith\Import\Sunnah\SunnahCollectionImportState;
use App\Domain\Hadith\Import\Sunnah\SunnahCollectionWriteResult;
use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithChapter;
use App\Domain\Hadith\Models\HadithCollection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class EloquentHadithImportRepository implements HadithImportRepositoryInterface
{
    private const FORTY_SLUGS = ['nawawi40', 'qudsi40', 'shahwaliullah40'];

    public function fortyState(): FortyImportState
    {
        $counts = HadithCollection::query()
            ->select(['hadith_collections.id', 'hadith_collections.slug'])
            ->selectSub(
                Hadith::query()
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('hadiths.collection_id', 'hadith_collections.id'),
                'hadith_count',
            )
            ->whereIn('slug', self::FORTY_SLUGS)
            ->pluck('hadith_count', 'slug')
            ->map(static fn (mixed $count): int => (int) $count)
            ->all();

        return new FortyImportState($counts);
    }

    public function importFortyCollection(MappedFortyCollection $collection): FortyCollectionWriteResult
    {
        return DB::transaction(function () use ($collection): FortyCollectionWriteResult {
            $model = $this->upsertCollection($collection);
            $this->assertBookless((int) $model->id, $collection->slug);

            $existing = Hadith::query()
                ->where('collection_id', $model->id)
                ->select(['id', 'reference_key'])
                ->pluck('id', 'reference_key');
            $sourceKeys = array_map(
                static fn (MappedHadithData $hadith): string => $hadith->referenceKey,
                $collection->hadiths,
            );
            $extra = array_diff($existing->keys()->all(), $sourceKeys);
            if ($extra !== []) {
                throw new RuntimeException(
                    "{$collection->slug} contains local references absent from the API snapshot: ".implode(', ', $extra).'.'
                );
            }

            $rows = array_map(
                fn (MappedHadithData $hadith): array => $this->hadithRow((int) $model->id, $hadith),
                $collection->hadiths,
            );
            Hadith::query()->upsert(
                $rows,
                ['collection_id', 'reference_key'],
                [
                    'book_id', 'chapter_id', 'number', 'in_book_number', 'sort_order',
                    'grade', 'raw_grade', 'text_ar', 'text_en', 'search_ar', 'search_en',
                    'narrated_by', 'canonical_reference', 'in_book_reference',
                    'translation_reference', 'is_published', 'updated_at',
                ],
            );

            $total = Hadith::query()->where('collection_id', $model->id)->count();
            if ($total !== $collection->expectedHadiths) {
                throw new RuntimeException(
                    "{$collection->slug} import parity failed: expected {$collection->expectedHadiths}, stored {$total}."
                );
            }

            $updated = $existing->count();

            return new FortyCollectionWriteResult(
                slug: $collection->slug,
                created: $collection->expectedHadiths - $updated,
                updated: $updated,
                total: $total,
            );
        });
    }

    public function collectionState(string $slug): SunnahCollectionImportState
    {
        $collection = HadithCollection::query()->where('slug', $slug)->first();
        if ($collection === null) {
            return new SunnahCollectionImportState(0, 0, 0);
        }

        return new SunnahCollectionImportState(
            books: HadithBook::query()->where('collection_id', $collection->id)->count(),
            chapters: HadithChapter::query()
                ->whereIn('book_id', HadithBook::query()->select('id')->where('collection_id', $collection->id))
                ->count(),
            hadiths: Hadith::query()->where('collection_id', $collection->id)->count(),
        );
    }

    public function importCollection(MappedSunnahCollection $source): SunnahCollectionWriteResult
    {
        return DB::transaction(function () use ($source): SunnahCollectionWriteResult {
            $collection = $this->upsertBookCollection($source);
            $collectionId = (int) $collection->id;
            $sourceBookNumbers = array_map(
                static fn (MappedSunnahBook $book): string => $book->number,
                $source->books,
            );
            $extraBooks = HadithBook::query()
                ->where('collection_id', $collectionId)
                ->whereNotIn('book_number', $sourceBookNumbers)
                ->pluck('book_number')
                ->all();
            if ($extraBooks !== []) {
                throw new RuntimeException(
                    "{$source->slug} contains local books absent from the API snapshot: ".implode(', ', $extraBooks).'.'
                );
            }

            $existingReferences = Hadith::query()
                ->where('collection_id', $collectionId)
                ->pluck('id', 'reference_key');
            $sourceReferences = [];
            foreach ($source->books as $book) {
                foreach ($book->hadiths as $hadith) {
                    $sourceReferences[] = $hadith->referenceKey;
                }
            }
            $extraReferences = array_diff($existingReferences->keys()->all(), $sourceReferences);
            if ($extraReferences !== []) {
                throw new RuntimeException(
                    "{$source->slug} contains local references absent from the API snapshot: "
                    .implode(', ', $extraReferences).'.'
                );
            }

            $rows = [];
            foreach ($source->books as $book) {
                $bookModel = $this->upsertBook($collectionId, $book);
                $chapterIds = $this->upsertChapters((int) $bookModel->id, $source->slug, $book);
                foreach ($book->hadiths as $hadith) {
                    $rows[] = $this->mappedHadithRow(
                        collectionId: $collectionId,
                        bookId: (int) $bookModel->id,
                        chapterId: $hadith->chapterSourceId === null
                            ? null
                            : ($chapterIds[$hadith->chapterSourceId] ?? throw new RuntimeException(
                                "Missing stored chapter {$hadith->chapterSourceId} for {$source->slug}:{$hadith->referenceKey}."
                            )),
                        hadith: $hadith,
                    );
                }
            }

            foreach (array_chunk($rows, 500) as $chunk) {
                Hadith::query()->upsert(
                    $chunk,
                    ['collection_id', 'reference_key'],
                    [
                        'book_id', 'chapter_id', 'number', 'in_book_number', 'sort_order',
                        'grade', 'raw_grade', 'text_ar', 'text_en', 'search_ar', 'search_en',
                        'narrated_by', 'canonical_reference', 'in_book_reference',
                        'translation_reference', 'is_published', 'updated_at',
                    ],
                );
            }

            $total = Hadith::query()->where('collection_id', $collectionId)->count();
            if ($total !== $source->expectedHadiths) {
                throw new RuntimeException(
                    "{$source->slug} import parity failed: expected {$source->expectedHadiths}, stored {$total}."
                );
            }

            return new SunnahCollectionWriteResult(
                slug: $source->slug,
                books: HadithBook::query()->where('collection_id', $collectionId)->count(),
                chapters: HadithChapter::query()
                    ->whereIn('book_id', HadithBook::query()->select('id')->where('collection_id', $collectionId))
                    ->count(),
                createdHadiths: $source->expectedHadiths - $existingReferences->count(),
                updatedHadiths: $existingReferences->count(),
                totalHadiths: $total,
                publishedHadiths: Hadith::query()
                    ->where('collection_id', $collectionId)
                    ->where('is_published', true)
                    ->count(),
            );
        });
    }

    public function prepareStreamingCollection(
        SunnahCollectionDefinition $definition,
        SunnahCollectionData $source,
        array $bookNumbers,
    ): int {
        return DB::transaction(function () use ($definition, $source, $bookNumbers): int {
            $collection = HadithCollection::query()->updateOrCreate(
                ['slug' => $definition->slug],
                [
                    'reference_template' => $definition->referenceTemplate,
                    'title_ar' => $source->titleAr,
                    'title_en' => $source->titleEn,
                    'intro_ar' => $source->introAr,
                    'intro_en' => $source->introEn,
                    'total_hadith' => $definition->expectedHadiths,
                    'has_books' => true,
                    'has_chapters' => $definition->hasChapters,
                    'sort_order' => $definition->sortOrder,
                    'is_published' => false,
                ],
            );
            $extraBooks = HadithBook::query()
                ->where('collection_id', $collection->id)
                ->whereNotIn('book_number', $bookNumbers)
                ->pluck('book_number')
                ->all();
            if ($extraBooks !== []) {
                throw new RuntimeException(
                    "{$definition->slug} contains local books absent from the API snapshot: ".implode(', ', $extraBooks).'.'
                );
            }

            return (int) $collection->id;
        });
    }

    public function importStreamingBook(int $collectionId, string $slug, MappedSunnahBook $book): array
    {
        return DB::transaction(function () use ($collectionId, $slug, $book): array {
            $bookModel = $this->upsertBook($collectionId, $book);
            $chapterIds = $this->upsertChapters((int) $bookModel->id, $slug, $book);
            $existingReferences = Hadith::query()
                ->where('collection_id', $collectionId)
                ->where('book_id', $bookModel->id)
                ->pluck('id', 'reference_key');
            $sourceReferences = array_map(
                static fn (MappedHadithData $hadith): string => $hadith->referenceKey,
                $book->hadiths,
            );
            $extraReferences = array_diff($existingReferences->keys()->all(), $sourceReferences);
            if ($extraReferences !== []) {
                throw new RuntimeException(
                    "{$slug} book {$book->number} contains local references absent from the API snapshot: "
                    .implode(', ', $extraReferences).'.'
                );
            }

            $rows = [];
            foreach ($book->hadiths as $hadith) {
                $rows[] = $this->mappedHadithRow(
                    collectionId: $collectionId,
                    bookId: (int) $bookModel->id,
                    chapterId: $hadith->chapterSourceId === null
                        ? null
                        : ($chapterIds[$hadith->chapterSourceId] ?? throw new RuntimeException(
                            "Missing stored chapter {$hadith->chapterSourceId} for {$slug}:{$hadith->referenceKey}."
                        )),
                    hadith: $hadith,
                );
            }
            foreach (array_chunk($rows, 500) as $chunk) {
                Hadith::query()->upsert(
                    $chunk,
                    ['collection_id', 'reference_key'],
                    [
                        'book_id', 'chapter_id', 'number', 'in_book_number', 'sort_order',
                        'grade', 'raw_grade', 'text_ar', 'text_en', 'search_ar', 'search_en',
                        'narrated_by', 'canonical_reference', 'in_book_reference',
                        'translation_reference', 'is_published', 'updated_at',
                    ],
                );
            }

            return [
                'created' => count($book->hadiths) - $existingReferences->count(),
                'updated' => $existingReferences->count(),
            ];
        });
    }

    public function finishStreamingCollection(
        int $collectionId,
        string $slug,
        int $expectedBooks,
        int $expectedHadiths,
    ): SunnahCollectionWriteResult {
        return DB::transaction(function () use ($collectionId, $slug, $expectedBooks, $expectedHadiths): SunnahCollectionWriteResult {
            $books = HadithBook::query()->where('collection_id', $collectionId)->count();
            $hadiths = Hadith::query()->where('collection_id', $collectionId)->count();
            if ($books !== $expectedBooks || $hadiths !== $expectedHadiths) {
                throw new RuntimeException(
                    "{$slug} final parity failed: expected {$expectedBooks} books/{$expectedHadiths} Hadiths, "
                    ."stored {$books} books/{$hadiths} Hadiths."
                );
            }

            HadithCollection::query()->whereKey($collectionId)->update(['is_published' => true]);
            $chapters = HadithChapter::query()
                ->whereIn('book_id', HadithBook::query()->select('id')->where('collection_id', $collectionId))
                ->count();
            $published = Hadith::query()
                ->where('collection_id', $collectionId)
                ->where('is_published', true)
                ->count();

            return new SunnahCollectionWriteResult(
                slug: $slug,
                books: $books,
                chapters: $chapters,
                createdHadiths: 0,
                updatedHadiths: $hadiths,
                totalHadiths: $hadiths,
                publishedHadiths: $published,
            );
        });
    }

    private function upsertCollection(MappedFortyCollection $source): HadithCollection
    {
        $collection = HadithCollection::query()->where('slug', $source->slug)->first();
        $attributes = [
            'reference_template' => $source->referenceTemplate,
            'title_ar' => $source->titleAr,
            'title_en' => $source->titleEn,
            'total_hadith' => $source->expectedHadiths,
            'has_books' => false,
            'has_chapters' => false,
            'sort_order' => $source->sortOrder,
            'is_published' => true,
        ];

        if ($collection === null) {
            return HadithCollection::query()->create($attributes + [
                'slug' => $source->slug,
                'is_featured' => false,
            ]);
        }

        $collection->update($attributes);

        return $collection->refresh();
    }

    private function upsertBookCollection(MappedSunnahCollection $source): HadithCollection
    {
        return HadithCollection::query()->updateOrCreate(
            ['slug' => $source->slug],
            [
                'reference_template' => $source->referenceTemplate,
                'title_ar' => $source->titleAr,
                'title_en' => $source->titleEn,
                'intro_ar' => $source->introAr,
                'intro_en' => $source->introEn,
                'total_hadith' => $source->expectedHadiths,
                'has_books' => true,
                'has_chapters' => $source->hasChapters,
                'sort_order' => $source->sortOrder,
                'is_published' => true,
            ],
        );
    }

    private function upsertBook(int $collectionId, MappedSunnahBook $source): HadithBook
    {
        $book = HadithBook::query()->updateOrCreate(
            ['collection_id' => $collectionId, 'book_number' => $source->number],
            [
                'title_ar' => $source->titleAr,
                'title_en' => $source->titleEn,
                'first_hadith_number' => $source->firstHadithNumber,
                'last_hadith_number' => $source->lastHadithNumber,
                'sort_order' => $source->sortOrder,
                'is_published' => true,
            ],
        );
        DB::table('hadith_books')->where('id', $book->id)->update(['intro' => null]);

        return $book->refresh();
    }

    /** @return array<string, int> */
    private function upsertChapters(int $bookId, string $slug, MappedSunnahBook $book): array
    {
        $sourceOrders = array_map(
            static fn (MappedSunnahChapter $chapter): int => $chapter->sortOrder,
            $book->chapters,
        );
        $extraOrders = HadithChapter::query()
            ->where('book_id', $bookId)
            ->whereNotIn('sort_order', $sourceOrders)
            ->pluck('sort_order')
            ->all();
        if ($extraOrders !== []) {
            throw new RuntimeException(
                "{$slug} book {$book->number} contains local chapters absent from the API snapshot."
            );
        }

        $ids = [];
        foreach ($book->chapters as $chapter) {
            $model = HadithChapter::query()->updateOrCreate(
                ['book_id' => $bookId, 'sort_order' => $chapter->sortOrder],
                [
                    'chapter_number' => $chapter->number,
                    'title_ar' => $chapter->titleAr,
                    'title_en' => $chapter->titleEn,
                    'is_published' => true,
                ],
            );
            DB::table('hadith_chapters')->where('id', $model->id)->update([
                'intro' => $this->localizedJson($chapter->introAr, $chapter->introEn),
                'ending' => $this->localizedJson($chapter->endingAr, $chapter->endingEn),
            ]);
            $ids[$chapter->sourceId] = (int) $model->id;
        }

        return $ids;
    }

    private function localizedJson(?string $arabic, ?string $english): ?string
    {
        $value = array_filter(['ar' => $arabic, 'en' => $english], static fn (?string $text): bool => $text !== null);

        return $value !== [] ? json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE) : null;
    }

    private function assertBookless(int $collectionId, string $slug): void
    {
        if (DB::table('hadith_books')->where('collection_id', $collectionId)->exists()) {
            throw new RuntimeException("{$slug} has local books but the target mapping is bookless.");
        }
        if (Hadith::query()->where('collection_id', $collectionId)->whereNotNull('book_id')->exists()) {
            throw new RuntimeException("{$slug} has book-linked hadiths but the target mapping is bookless.");
        }
    }

    /** @return array<string, mixed> */
    private function hadithRow(int $collectionId, MappedHadithData $hadith): array
    {
        return [
            'collection_id' => $collectionId,
            'book_id' => null,
            'chapter_id' => null,
            'reference_key' => $hadith->referenceKey,
            'number' => $hadith->number,
            'in_book_number' => null,
            'sort_order' => $hadith->sortOrder,
            'grade' => $hadith->grade?->value,
            'raw_grade' => $hadith->rawGrade,
            'text_ar' => $hadith->textAr,
            'text_en' => $hadith->textEn,
            'search_ar' => $hadith->searchAr,
            'search_en' => $hadith->searchEn,
            'narrated_by' => null,
            'canonical_reference' => $hadith->canonicalReference,
            'in_book_reference' => null,
            'translation_reference' => null,
            'is_published' => $hadith->textAr !== null && $hadith->searchAr !== null,
        ];
    }

    /** @return array<string, mixed> */
    private function mappedHadithRow(
        int $collectionId,
        int $bookId,
        ?int $chapterId,
        MappedHadithData $hadith,
    ): array {
        return [
            'collection_id' => $collectionId,
            'book_id' => $bookId,
            'chapter_id' => $chapterId,
            'reference_key' => $hadith->referenceKey,
            'number' => $hadith->number,
            'in_book_number' => $hadith->inBookNumber,
            'sort_order' => $hadith->sortOrder,
            'grade' => $hadith->grade?->value,
            'raw_grade' => $hadith->rawGrade,
            'text_ar' => $hadith->textAr,
            'text_en' => $hadith->textEn,
            'search_ar' => $hadith->searchAr,
            'search_en' => $hadith->searchEn,
            'narrated_by' => null,
            'canonical_reference' => $hadith->canonicalReference,
            'in_book_reference' => $hadith->inBookReference,
            'translation_reference' => null,
            'is_published' => $hadith->textAr !== null && $hadith->searchAr !== null,
        ];
    }
}
