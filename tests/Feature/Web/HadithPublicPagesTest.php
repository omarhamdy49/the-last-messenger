<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithCollection;
use App\Domain\Hadith\Models\HadithCommentary;
use App\Domain\Hadith\Models\HadithGrading;
use App\Domain\Hadith\Models\HadithGradingLevel;
use App\Domain\Hadith\Models\HadithIsnad;
use App\Domain\Hadith\Models\HadithNarrator;
use App\Domain\Hadith\Models\HadithScholar;
use App\Domain\Hadith\Models\HadithTranslation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Public hadith pages: chapter (kitab) reader, parallel narrations comparison,
 * and the upgraded single hadith page (isnad + commentaries + gradings).
 */
final class HadithPublicPagesTest extends TestCase
{
    use RefreshDatabase;

    private function seedCollection(array $overrides = []): HadithCollection
    {
        return HadithCollection::create(array_merge([
            'name_ar' => 'صحيح الاختبار',
            'name_en' => 'صحيح الاختبار',
            'slug_ar' => 'sahih-test',
            'slug_en' => 'sahih-test-en',
            'abbreviation' => 'TST',
            'canonical_code' => 'test',
            'source_slug' => 'test',
            'total_hadiths' => 3,
        ], $overrides));
    }

    private function seedBook(HadithCollection $collection, int $number = 1, array $overrides = []): HadithBook
    {
        return HadithBook::create(array_merge([
            'collection_id' => $collection->id,
            'book_number' => $number,
            'title_ar' => "كتاب الإيمان {$number}",
            'title_en' => "كتاب الإيمان {$number}",
            'slug_ar' => "kitab-{$collection->id}-{$number}",
            'slug_en' => "book-{$collection->id}-{$number}",
        ], $overrides));
    }

    private function seedHadith(HadithBook $book, string $number, array $overrides = []): Hadith
    {
        $hadith = Hadith::create(array_merge([
            'book_id' => $book->id,
            'hadith_number' => $number,
            'grading_status' => 'sahih',
            'grading_agreement' => true,
        ], $overrides));

        HadithTranslation::create([
            'hadith_id' => $hadith->id,
            'language_code' => 'ar',
            'text' => 'إنما الأعمال بالنيات وإنما لكل امرئ ما نوى رقم '.$number,
        ]);

        return $hadith;
    }

    // ------------------------------------------------------------------
    // Chapter page
    // ------------------------------------------------------------------

    public function test_chapter_page_renders_with_hadiths(): void
    {
        $collection = $this->seedCollection();
        $book = $this->seedBook($collection, 1);
        $this->seedBook($collection, 2);
        $this->seedHadith($book, '1');
        $this->seedHadith($book, '2');

        $response = $this->get(route('public.hadith.chapter', ['slug' => 'sahih-test', 'chapter' => 1]));

        $response->assertOk()
            ->assertSee('كتاب الإيمان 1')
            ->assertSee('إنما الأعمال بالنيات', false);
    }

    public function test_chapter_page_returns_404_for_unknown_collection_slug(): void
    {
        $this->get(route('public.hadith.chapter', ['slug' => 'no-such-collection', 'chapter' => 1]))
            ->assertNotFound();
    }

    public function test_chapter_page_returns_404_for_unknown_chapter_number(): void
    {
        $collection = $this->seedCollection();
        $this->seedBook($collection, 1);

        $this->get(route('public.hadith.chapter', ['slug' => 'sahih-test', 'chapter' => 99]))
            ->assertNotFound();
    }

    public function test_chapter_page_applies_search_and_grading_filters(): void
    {
        $collection = $this->seedCollection();
        $book = $this->seedBook($collection, 1);
        $this->seedHadith($book, '1');
        $weak = $this->seedHadith($book, '2', ['grading_status' => 'daif']);

        HadithTranslation::where('hadith_id', $weak->id)->update([
            'text' => 'نص مختلف تماما عن الباقي',
        ]);

        // Grading filter: only the sahih hadith remains.
        $this->get(route('public.hadith.chapter', ['slug' => 'sahih-test', 'chapter' => 1, 'grading' => 'sahih']))
            ->assertOk()
            ->assertSee('إنما الأعمال بالنيات', false)
            ->assertDontSee('نص مختلف تماما', false);

        // Search-within-chapter: only the matching text remains.
        $this->get(route('public.hadith.chapter', ['slug' => 'sahih-test', 'chapter' => 1, 'q' => 'مختلف تماما']))
            ->assertOk()
            ->assertSee('نص مختلف تماما', false);
    }

    public function test_chapter_page_rejects_invalid_per_page_and_grading_values(): void
    {
        $collection = $this->seedCollection();
        $book = $this->seedBook($collection, 1);
        $this->seedHadith($book, '1');

        $this->get(route('public.hadith.chapter', [
            'slug' => 'sahih-test', 'chapter' => 1, 'per_page' => 5000, 'grading' => 'bogus',
        ]))->assertOk();
    }

    // ------------------------------------------------------------------
    // Parallel narrations page
    // ------------------------------------------------------------------

    public function test_narrations_page_renders_parallel_versions(): void
    {
        $hash = hash('sha256', 'same-text');

        $collectionA = $this->seedCollection();
        $collectionB = $this->seedCollection([
            'name_ar' => 'سنن الاختبار',
            'name_en' => 'سنن الاختبار',
            'slug_ar' => 'sunan-test',
            'slug_en' => 'sunan-test-en',
            'abbreviation' => 'TS2',
            'canonical_code' => 'test2',
            'source_slug' => 'test2',
        ]);

        $bookA = $this->seedBook($collectionA, 1);
        $bookB = $this->seedBook($collectionB, 1);

        $base = $this->seedHadith($bookA, '10', ['text_hash' => $hash]);
        $this->seedHadith($bookB, '20', ['text_hash' => $hash, 'grading_status' => 'hasan']);

        $response = $this->get(route('public.hadith.narrations', $base->id));

        $response->assertOk()
            ->assertSee('صحيح الاختبار')
            ->assertSee('سنن الاختبار');
    }

    public function test_narrations_page_shows_unique_state_when_no_parallels(): void
    {
        $collection = $this->seedCollection();
        $book = $this->seedBook($collection, 1);
        $hadith = $this->seedHadith($book, '1', ['text_hash' => hash('sha256', 'unique-text')]);

        $this->get(route('public.hadith.narrations', $hadith->id))
            ->assertOk()
            ->assertSee(__('hadith.public.narrations_empty_title'));
    }

    public function test_narrations_page_returns_404_for_missing_hadith(): void
    {
        $this->get(route('public.hadith.narrations', 999999))->assertNotFound();
    }

    // ------------------------------------------------------------------
    // Single hadith page
    // ------------------------------------------------------------------

    public function test_show_page_renders_with_isnad_commentaries_and_gradings(): void
    {
        $collection = $this->seedCollection();
        $book = $this->seedBook($collection, 1);
        $hadith = $this->seedHadith($book, '1');

        $narrator = HadithNarrator::create([
            'name_ar' => 'أبو هريرة',
            'name_en' => 'أبو هريرة',
            'reliability_grade' => 'صحابي',
            'death_year_hijri' => 59,
        ]);
        HadithIsnad::create([
            'hadith_id' => $hadith->id,
            'narrator_id' => $narrator->id,
            'position_in_chain' => 1,
            'transmission_method' => 'عن',
        ]);

        $scholar = HadithScholar::create([
            'name_ar' => 'الإمام النووي',
            'name_en' => 'الإمام النووي',
        ]);
        HadithCommentary::create([
            'hadith_id' => $hadith->id,
            'scholar_id' => $scholar->id,
            'language_code' => 'ar',
            'author' => 'الإمام النووي',
            'commentary_text' => 'هذا الحديث أصل عظيم من أصول الإسلام.',
            'verified' => true,
        ]);

        $level = HadithGradingLevel::create([
            'grade_ar' => 'صحيح',
            'grade_en' => 'Sahih',
            'rank' => 1,
        ]);
        HadithGrading::create([
            'hadith_id' => $hadith->id,
            'scholar_id' => $scholar->id,
            'grading_level_id' => $level->id,
        ]);

        $response = $this->get(route('public.hadith.show', $hadith->id));

        $response->assertOk()
            ->assertSee(__('hadith.public.single_commentaries_heading'))
            ->assertSee('الإمام النووي')
            ->assertSee('هذا الحديث أصل عظيم', false)
            ->assertSee(__('hadith.public.single_gradings_heading'));
    }

    public function test_show_page_links_to_narrations_when_parallels_exist(): void
    {
        $hash = hash('sha256', 'shared');

        $collection = $this->seedCollection();
        $bookA = $this->seedBook($collection, 1);
        $bookB = $this->seedBook($collection, 2);

        $base = $this->seedHadith($bookA, '1', ['text_hash' => $hash]);
        $this->seedHadith($bookB, '2', ['text_hash' => $hash]);

        $this->get(route('public.hadith.show', $base->id))
            ->assertOk()
            ->assertSee(route('public.hadith.narrations', $base->id), false);
    }

    public function test_show_page_still_returns_404_for_missing_hadith(): void
    {
        $this->get(route('public.hadith.show', 999999))->assertNotFound();
    }
}
