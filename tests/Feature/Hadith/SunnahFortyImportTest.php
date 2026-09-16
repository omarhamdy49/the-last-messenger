<?php

declare(strict_types=1);

namespace Tests\Feature\Hadith;

use App\Domain\Hadith\Enums\Grade;
use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithCollection;
use App\Domain\Hadith\Services\HadithBrowseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class SunnahFortyImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'sunnah.api.key' => 'test-key',
            'sunnah.api.base_url' => 'https://api.sunnah.test/v1/',
            'sunnah.api.retry_attempts' => 1,
            'sunnah.api.retry_delay' => 0,
            'sunnah.api.rate_limit.delay_between_requests' => 0,
        ]);
    }

    public function test_dry_run_validates_all_three_books_without_writing(): void
    {
        $this->fakeFortyApi();

        $this->artisan('hadith:import', ['--collection' => 'forty', '--dry-run' => true])
            ->expectsOutputToContain('Source rows: 122')
            ->expectsOutputToContain('Dry run — nothing written.')
            ->assertSuccessful();

        $this->assertDatabaseCount('hadith_collections', 0);
        $this->assertDatabaseCount('hadiths', 0);
    }

    public function test_import_maps_the_aggregate_to_three_bookless_published_collections(): void
    {
        $this->fakeFortyApi();
        $browse = app(HadithBrowseService::class);
        $this->assertCount(0, $browse->collections());
        $this->assertSame(0, $browse->totalVisible());

        $this->artisan('hadith:import', ['--collection' => 'forty', '--force' => true])
            ->expectsOutputToContain('Import completed; collections and imported rows are published.')
            ->assertSuccessful();

        $this->assertDatabaseCount('hadith_collections', 3);
        $this->assertDatabaseCount('hadith_books', 0);
        $this->assertDatabaseCount('hadith_chapters', 0);
        $this->assertDatabaseCount('hadiths', 122);
        $this->assertDatabaseCount('hadith_narrators', 0);
        $this->assertCount(3, $browse->collections());
        $this->assertSame(122, $browse->totalVisible());

        $collection = HadithCollection::query()->where('slug', 'nawawi40')->firstOrFail();
        $hadith = Hadith::query()
            ->where('collection_id', $collection->id)
            ->where('reference_key', '1-1')
            ->firstOrFail();

        $this->assertFalse($collection->has_books);
        $this->assertFalse($collection->has_chapters);
        $this->assertTrue($collection->is_published);
        $this->assertSame(42, $collection->total_hadith);
        $this->assertSame('Hadith 1, 40 Hadith an-Nawawi', $hadith->canonical_reference);
        $this->assertSame('نص الحديث 1', $hadith->text_ar);
        $this->assertSame('English hadith 1', $hadith->text_en);
        $this->assertSame(Grade::Sahih, $hadith->grade);
        $this->assertSame('sahih', $hadith->getRawOriginal('grade'));
        $this->assertSame('Sahih (Test grader)', $hadith->raw_grade);
        $this->assertTrue($hadith->is_published);
        $this->assertNull($hadith->book_id);
        $this->assertNull($hadith->chapter_id);
        $this->assertNull($hadith->narrated_by);
    }

    public function test_reimport_is_idempotent_and_enforces_published_state(): void
    {
        $this->fakeFortyApi();
        $arguments = ['--collection' => 'forty', '--force' => true];

        $this->artisan('hadith:import', $arguments)->assertSuccessful();
        $hadith = Hadith::query()->orderBy('id')->firstOrFail();
        $collection = $hadith->collection;
        $hadith->update(['is_published' => false]);
        $collection->update(['is_published' => false]);

        $this->artisan('hadith:import', $arguments)->assertSuccessful();

        $this->assertDatabaseCount('hadith_collections', 3);
        $this->assertDatabaseCount('hadiths', 122);
        $this->assertTrue($hadith->fresh()->is_published);
        $this->assertTrue($collection->fresh()->is_published);
    }

    public function test_incomplete_api_book_fails_before_any_collection_is_written(): void
    {
        $this->fakeFortyApi(bookThreeCount: 39);

        $this->artisan('hadith:import', ['--collection' => 'forty', '--force' => true])
            ->expectsOutputToContain('shahwaliullah40 count mismatch')
            ->assertFailed();

        $this->assertDatabaseCount('hadith_collections', 0);
        $this->assertDatabaseCount('hadiths', 0);
    }

    private function fakeFortyApi(int $bookThreeCount = 40): void
    {
        $books = [
            $this->book('1', 'الأربعون النووية', 'Forty Hadith of an-Nawawi', 42),
            $this->book('2', 'الأحاديث القدسية', 'Forty Hadith Qudsi', 40),
            $this->book('3', 'أربعون شاه ولي الله', 'Forty Hadith of Shah Waliullah', 40),
        ];

        Http::fake(function (Request $request) use ($books, $bookThreeCount) {
            $path = parse_url($request->url(), PHP_URL_PATH);
            if (str_ends_with((string) $path, '/collections/forty/books')) {
                return Http::response($this->page($books), 200);
            }

            if (preg_match('~/collections/forty/books/(\d+)/hadiths$~', (string) $path, $match) === 1) {
                $bookNumber = $match[1];
                $count = $bookNumber === '1' ? 42 : ($bookNumber === '2' ? 40 : $bookThreeCount);
                $rows = [];
                for ($number = 1; $number <= $count; $number++) {
                    $rows[] = $this->hadith($bookNumber, (string) $number);
                }

                return Http::response($this->page($rows), 200);
            }

            return Http::response(['message' => 'Not found'], 404);
        });
    }

    /** @return array<string, mixed> */
    private function book(string $number, string $titleAr, string $titleEn, int $count): array
    {
        return [
            'bookNumber' => $number,
            'book' => [
                ['lang' => 'en', 'name' => $titleEn],
                ['lang' => 'ar', 'name' => $titleAr],
            ],
            'hadithStartNumber' => 1,
            'hadithEndNumber' => $count,
            'numberOfHadith' => $count,
        ];
    }

    /** @return array<string, mixed> */
    private function hadith(string $bookNumber, string $number): array
    {
        return [
            'collection' => 'forty',
            'bookNumber' => $bookNumber,
            'chapterId' => '0.00',
            'hadithNumber' => $number,
            'hadith' => [
                [
                    'lang' => 'en', 'chapterNumber' => '', 'chapterTitle' => '',
                    'urn' => 100000 + (int) $number, 'body' => "English hadith {$number}",
                    'grades' => [['graded_by' => 'Test grader', 'grade' => 'Sahih']],
                ],
                [
                    'lang' => 'ar', 'chapterNumber' => '', 'chapterTitle' => '',
                    'urn' => 1000 + (int) $number, 'body' => "نص الحديث {$number}",
                    'grades' => [['graded_by' => 'المحقق', 'grade' => 'صحيح']],
                ],
            ],
        ];
    }

    /** @param list<array<string, mixed>> $rows @return array<string, mixed> */
    private function page(array $rows): array
    {
        return [
            'data' => $rows,
            'total' => count($rows),
            'limit' => 100,
            'previous' => null,
            'next' => null,
        ];
    }
}
