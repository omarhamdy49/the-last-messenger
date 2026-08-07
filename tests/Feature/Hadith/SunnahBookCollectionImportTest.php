<?php

declare(strict_types=1);

namespace Tests\Feature\Hadith;

use App\Domain\Hadith\Enums\Grade;
use App\Domain\Hadith\Import\Sunnah\SunnahApiClient;
use App\Domain\Hadith\Import\Sunnah\SunnahBookData;
use App\Domain\Hadith\Import\Sunnah\SunnahChapterData;
use App\Domain\Hadith\Import\Sunnah\SunnahCollectionData;
use App\Domain\Hadith\Import\Sunnah\SunnahCollectionDefinition;
use App\Domain\Hadith\Import\Sunnah\SunnahCollectionMapper;
use App\Domain\Hadith\Import\Sunnah\SunnahCollectionSnapshot;
use App\Domain\Hadith\Import\Sunnah\SunnahHadithData;
use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithChapter;
use App\Domain\Hadith\Models\HadithCollection;
use App\Domain\Hadith\Repositories\HadithImportRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

final class SunnahBookCollectionImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_book_collection_import_maps_hierarchy_duplicates_grades_and_publication(): void
    {
        $mapped = $this->mapper()->map($this->definition(), $this->snapshot());
        $result = $this->repository()->importCollection($mapped);

        $this->assertSame(1, $result->books);
        $this->assertSame(1, $result->chapters);
        $this->assertSame(2, $result->createdHadiths);
        $this->assertSame(2, $result->publishedHadiths);
        $this->assertDatabaseCount('hadith_collections', 1);
        $this->assertDatabaseCount('hadith_books', 1);
        $this->assertDatabaseCount('hadith_chapters', 1);
        $this->assertDatabaseCount('hadiths', 2);

        $collection = HadithCollection::query()->firstOrFail();
        $book = HadithBook::query()->firstOrFail();
        $chapter = HadithChapter::query()->firstOrFail();
        $hadiths = Hadith::query()->orderBy('sort_order')->get();

        $this->assertTrue($collection->is_published);
        $this->assertTrue($book->is_published);
        $this->assertTrue($chapter->is_published);
        $this->assertSame('مقدمة', $chapter->getTranslation('intro', 'ar'));
        $this->assertNull($book->getRawOriginal('intro'));
        $this->assertNull($chapter->getRawOriginal('ending'));
        $this->assertSame(['1-8', '1-8-2'], $hadiths->pluck('reference_key')->all());
        $this->assertSame(['1', '2'], $hadiths->pluck('in_book_number')->all());
        $this->assertSame(Grade::Sahih, $hadiths[0]->grade);
        $this->assertSame('sahih', $hadiths[0]->getRawOriginal('grade'));
        $this->assertTrue($hadiths->every->is_published);
        $this->assertSame($chapter->id, $hadiths[0]->chapter_id);
    }

    public function test_book_collection_reimport_is_idempotent_and_enforces_publication(): void
    {
        $mapped = $this->mapper()->map($this->definition(), $this->snapshot());
        $repository = $this->repository();
        $repository->importCollection($mapped);

        HadithCollection::query()->update(['is_published' => false]);
        HadithBook::query()->update(['is_published' => false]);
        HadithChapter::query()->update(['is_published' => false]);
        Hadith::query()->update(['is_published' => false]);
        $result = $repository->importCollection($mapped);

        $this->assertSame(0, $result->createdHadiths);
        $this->assertSame(2, $result->updatedHadiths);
        $this->assertDatabaseCount('hadiths', 2);
        $this->assertSame(2, Hadith::query()->where('is_published', true)->count());
        $this->assertTrue(HadithCollection::query()->firstOrFail()->is_published);
        $this->assertTrue(HadithBook::query()->firstOrFail()->is_published);
        $this->assertTrue(HadithChapter::query()->firstOrFail()->is_published);
    }

    public function test_available_count_mismatch_fails_before_mapping(): void
    {
        $snapshot = new SunnahCollectionSnapshot(
            new SunnahCollectionData('test', 'اختبار', 'Test', null, null, 1),
            $this->snapshot()->books,
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('available-count mismatch');
        $this->mapper()->map($this->definition(), $snapshot);
    }

    public function test_unnumbered_structural_subchapter_uses_its_source_identity(): void
    {
        $chapter = SunnahChapterData::fromApi([
            'chapterId' => '22.10',
            'chapter' => [
                ['lang' => 'en', 'chapterNumber' => '', 'chapterTitle' => 'Subchapter', 'intro' => null, 'ending' => null],
                ['lang' => 'ar', 'chapterNumber' => '', 'chapterTitle' => 'باب فرعي', 'intro' => null, 'ending' => null],
            ],
        ]);

        $this->assertSame('22.10', $chapter->number);
        $this->assertSame('باب فرعي', $chapter->titleAr);
    }

    public function test_titleless_structural_chapter_does_not_invent_a_local_title(): void
    {
        $chapter = SunnahChapterData::fromApi([
            'chapterId' => '0.10',
            'chapter' => [
                ['lang' => 'en', 'chapterNumber' => '0', 'chapterTitle' => '', 'intro' => null, 'ending' => null],
                ['lang' => 'ar', 'chapterNumber' => '0', 'chapterTitle' => '', 'intro' => null, 'ending' => null],
            ],
        ]);
        $hadith = new SunnahHadithData('test', '1', '0.10', '1', 'نص', 'Text', [], []);
        $book = new SunnahBookData('1', 'كتاب', 'Book', 1, '1', '1', [$chapter], [$hadith]);
        $snapshot = new SunnahCollectionSnapshot(
            new SunnahCollectionData('test', 'اختبار', 'Test', null, null, 1),
            [$book],
        );
        $mapped = $this->mapper()->map(
            new SunnahCollectionDefinition('test', 'test', 'Test {hadithNumber}', 1, 1, true),
            $snapshot,
        );

        $this->assertSame('0.10', $chapter->sourceId);
        $this->assertSame([], $mapped->books[0]->chapters);
        $this->assertNull($mapped->books[0]->hadiths[0]->chapterSourceId);
    }

    public function test_chapter_titles_longer_than_the_legacy_limit_are_preserved(): void
    {
        $mapped = $this->mapper()->map($this->definition(), $this->snapshotWithLongChapterTitle());
        $this->repository()->importCollection($mapped);

        $this->assertSame(str_repeat('Long title ', 60), HadithChapter::query()->firstOrFail()->title_en);
    }

    public function test_broken_chapter_endpoint_falls_back_to_hadith_chapter_fields(): void
    {
        config([
            'sunnah.api.key' => 'test-key',
            'sunnah.api.base_url' => 'https://api.sunnah.test/v1/',
            'sunnah.api.retry_attempts' => 1,
            'sunnah.api.rate_limit.delay_between_requests' => 0,
        ]);
        Http::fake([
            '*/collections/test/books/1/hadiths*' => Http::response($this->page([[
                'collection' => 'test',
                'bookNumber' => '1',
                'chapterId' => '22.10',
                'hadithNumber' => '8',
                'hadith' => [
                    ['lang' => 'en', 'chapterNumber' => '', 'chapterTitle' => 'Subchapter', 'body' => 'Text', 'grades' => []],
                    ['lang' => 'ar', 'chapterNumber' => '', 'chapterTitle' => 'باب فرعي', 'body' => 'نص', 'grades' => []],
                ],
            ]])),
            '*/collections/test/books/1/chapters*' => Http::response([
                'error' => ['details' => 'Internal error', 'code' => 500],
            ]),
        ]);

        $book = $this->app->make(SunnahApiClient::class)->fetchBook('test', true, [
            'bookNumber' => '1',
            'book' => [
                ['lang' => 'en', 'name' => 'Test book'],
                ['lang' => 'ar', 'name' => 'كتاب الاختبار'],
            ],
            'numberOfHadith' => 1,
        ]);

        $this->assertCount(1, $book->chapters);
        $this->assertSame('22.10', $book->chapters[0]->sourceId);
        $this->assertSame('باب فرعي', $book->chapters[0]->titleAr);
    }

    public function test_corrupt_bukhari_6940_uses_verified_page_record_and_one_row_api_pages(): void
    {
        config([
            'sunnah.api.key' => 'test-key',
            'sunnah.api.base_url' => 'https://api.sunnah.test/v1/',
            'sunnah.api.retry_attempts' => 1,
            'sunnah.api.rate_limit.delay_between_requests' => 0,
        ]);
        Http::fake(function (Request $request) {
            if (str_contains($request->url(), '/chapters')) {
                return Http::response(['error' => ['code' => 500]]);
            }
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            if (($query['page'] ?? '1') === '1') {
                return Http::response(['error' => ['code' => 500]]);
            }

            return Http::response($this->page([[
                'collection' => 'bukhari',
                'bookNumber' => '89',
                'chapterId' => '1.00',
                'hadithNumber' => '6941',
                'hadith' => [
                    ['lang' => 'en', 'chapterNumber' => '1', 'chapterTitle' => 'Chapter one', 'body' => 'Second record', 'grades' => []],
                    ['lang' => 'ar', 'chapterNumber' => '1', 'chapterTitle' => 'الباب الأول', 'body' => 'السجل الثاني', 'grades' => []],
                ],
            ]]));
        });

        $book = $this->app->make(SunnahApiClient::class)->fetchBook('bukhari', true, [
            'bookNumber' => '89',
            'book' => [
                ['lang' => 'en', 'name' => 'Coercion'],
                ['lang' => 'ar', 'name' => 'كتاب الإكراه'],
            ],
            'numberOfHadith' => 2,
        ]);

        $this->assertCount(2, $book->hadiths);
        $this->assertSame('6940', $book->hadiths[0]->number);
        $this->assertStringStartsWith('Narrated Abi Huraira:', (string) $book->hadiths[0]->bodyEn);
        $this->assertSame('6941', $book->hadiths[1]->number);
        $this->assertCount(1, $book->chapters);
    }

    public function test_corrupt_bukhari_book_96_opening_uses_five_verified_page_records(): void
    {
        config([
            'sunnah.api.key' => 'test-key',
            'sunnah.api.base_url' => 'https://api.sunnah.test/v1/',
            'sunnah.api.retry_attempts' => 1,
            'sunnah.api.rate_limit.delay_between_requests' => 0,
        ]);
        Http::fake(function (Request $request) {
            if (str_contains($request->url(), '/chapters')) {
                return Http::response(['error' => ['code' => 500]]);
            }
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            if ((int) ($query['page'] ?? 1) < 6) {
                return Http::response(['error' => ['code' => 500]]);
            }

            return Http::response($this->page([[
                'collection' => 'bukhari',
                'bookNumber' => '96',
                'chapterId' => '1.00',
                'hadithNumber' => '7273',
                'hadith' => [
                    ['lang' => 'en', 'chapterNumber' => '1', 'chapterTitle' => 'Chapter one', 'body' => 'Sixth record', 'grades' => []],
                    ['lang' => 'ar', 'chapterNumber' => '1', 'chapterTitle' => 'الباب الأول', 'body' => 'السجل السادس', 'grades' => []],
                ],
            ]]));
        });

        $book = $this->app->make(SunnahApiClient::class)->fetchBook('bukhari', true, [
            'bookNumber' => '96',
            'book' => [
                ['lang' => 'en', 'name' => "Holding Fast to the Qur'an and Sunnah"],
                ['lang' => 'ar', 'name' => 'كتاب الاعتصام بالكتاب والسنة'],
            ],
            'numberOfHadith' => 6,
        ]);

        $this->assertCount(6, $book->hadiths);
        $this->assertSame(['7268', '7269', '7270', '7271', '7272'], array_map(
            static fn (SunnahHadithData $hadith): string => $hadith->number,
            array_slice($book->hadiths, 0, 5),
        ));
        $this->assertStringStartsWith('Narrated Tariq bin Shihab:', (string) $book->hadiths[0]->bodyEn);
        $this->assertSame('7273', $book->hadiths[5]->number);
        $this->assertCount(1, $book->chapters);
    }

    public function test_tirmidhi_book_25_batch_failure_recovers_entirely_from_one_row_api_pages(): void
    {
        config([
            'sunnah.api.key' => 'test-key',
            'sunnah.api.base_url' => 'https://api.sunnah.test/v1/',
            'sunnah.api.retry_attempts' => 1,
            'sunnah.api.rate_limit.delay_between_requests' => 0,
        ]);
        Http::fake(function (Request $request) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            if (str_contains($request->url(), '/chapters')) {
                return Http::response($this->page([]));
            }
            if (($query['limit'] ?? '100') !== '1') {
                return Http::response(['error' => ['code' => 500]]);
            }

            $page = (int) ($query['page'] ?? 1);
            if ($page === 33) {
                return Http::response(['error' => ['code' => 500]]);
            }

            return Http::response($this->page([[
                'collection' => 'tirmidhi',
                'bookNumber' => '25',
                'chapterId' => '0.00',
                'hadithNumber' => (string) (1787 + $page),
                'hadith' => [
                    ['lang' => 'en', 'body' => "Record {$page}", 'grades' => []],
                    ['lang' => 'ar', 'body' => "السجل {$page}", 'grades' => []],
                ],
            ]]));
        });

        $book = $this->app->make(SunnahApiClient::class)->fetchBook('tirmidhi', true, [
            'bookNumber' => '25',
            'book' => [
                ['lang' => 'en', 'name' => 'The Book on Food'],
                ['lang' => 'ar', 'name' => 'كتاب الأطعمة'],
            ],
            'numberOfHadith' => 34,
        ]);

        $this->assertCount(34, $book->hadiths);
        $this->assertSame('1788', $book->hadiths[0]->number);
        $this->assertSame('1819', $book->hadiths[32]->number);
        $this->assertStringStartsWith('Narrated Abu Hurairah:', (string) $book->hadiths[32]->bodyEn);
        Http::assertSentCount(36);
    }

    public function test_shamail_negative_eight_index_typo_is_normalized_to_official_8b_identity(): void
    {
        config([
            'sunnah.api.key' => 'test-key',
            'sunnah.api.base_url' => 'https://api.sunnah.test/v1/',
            'sunnah.api.retry_attempts' => 1,
            'sunnah.api.rate_limit.delay_between_requests' => 0,
        ]);
        Http::fake([
            '*/collections/shamail/books*' => Http::response($this->page([[
                'bookNumber' => '-8',
                'book' => [
                    ['lang' => 'en', 'name' => 'The Standard of Living of Rasoolullah'],
                    ['lang' => 'ar', 'name' => 'باب ماجاء في عيش رسول الله'],
                ],
                'numberOfHadith' => 2,
            ]])),
        ]);

        $books = $this->app->make(SunnahApiClient::class)->bookIndex('shamail');

        $this->assertSame('8b', $books[0]['bookNumber']);
        $this->assertSame(2, $books[0]['numberOfHadith']);
    }

    private function definition(): SunnahCollectionDefinition
    {
        return new SunnahCollectionDefinition('test', 'test', 'Test {hadithNumber}', 1, 2, true);
    }

    private function snapshot(): SunnahCollectionSnapshot
    {
        $chapter = new SunnahChapterData(
            sourceId: '1.00',
            number: '1',
            titleAr: 'باب الاختبار',
            titleEn: 'Test chapter',
            introAr: 'مقدمة',
            introEn: 'Introduction',
            endingAr: null,
            endingEn: null,
        );
        $hadiths = [
            $this->hadith('النص الأول', 'First text'),
            $this->hadith('النص الثاني', 'Second text'),
        ];
        $book = new SunnahBookData(
            number: '1',
            titleAr: 'كتاب الاختبار',
            titleEn: 'Test book',
            declaredHadiths: 2,
            firstHadithNumber: '8',
            lastHadithNumber: '8',
            chapters: [$chapter],
            hadiths: $hadiths,
        );

        return new SunnahCollectionSnapshot(
            new SunnahCollectionData('test', 'اختبار', 'Test', 'مقدمة المجموعة', 'Collection introduction', 2),
            [$book],
        );
    }

    private function snapshotWithLongChapterTitle(): SunnahCollectionSnapshot
    {
        $snapshot = $this->snapshot();
        $source = $snapshot->books[0];
        $chapter = $source->chapters[0];
        $longChapter = new SunnahChapterData(
            sourceId: $chapter->sourceId,
            number: $chapter->number,
            titleAr: $chapter->titleAr,
            titleEn: str_repeat('Long title ', 60),
            introAr: $chapter->introAr,
            introEn: $chapter->introEn,
            endingAr: null,
            endingEn: null,
        );
        $book = new SunnahBookData(
            number: $source->number,
            titleAr: $source->titleAr,
            titleEn: $source->titleEn,
            declaredHadiths: $source->declaredHadiths,
            firstHadithNumber: $source->firstHadithNumber,
            lastHadithNumber: $source->lastHadithNumber,
            chapters: [$longChapter],
            hadiths: $source->hadiths,
        );

        return new SunnahCollectionSnapshot($snapshot->collection, [$book]);
    }

    private function hadith(string $arabic, string $english): SunnahHadithData
    {
        return new SunnahHadithData(
            collection: 'test',
            bookNumber: '1',
            chapterId: '1.00',
            number: '8',
            bodyAr: $arabic,
            bodyEn: $english,
            gradesAr: ['صحيح'],
            gradesEn: ['Sahih (Tester)'],
        );
    }

    private function mapper(): SunnahCollectionMapper
    {
        return $this->app->make(SunnahCollectionMapper::class);
    }

    private function repository(): HadithImportRepositoryInterface
    {
        return $this->app->make(HadithImportRepositoryInterface::class);
    }

    /** @param list<array<string, mixed>> $rows @return array<string, mixed> */
    private function page(array $rows): array
    {
        return ['data' => $rows, 'total' => count($rows), 'limit' => 100, 'previous' => null, 'next' => null];
    }
}
