<?php

declare(strict_types=1);

namespace Tests\Feature\Hadith;

use App\Domain\Hadith\Enums\Grade;
use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithChapter;
use App\Domain\Hadith\Models\HadithCollection;
use App\Domain\Hadith\Models\HadithNarrator;
use App\Domain\People\Models\Muslim;
use App\Domain\Shared\Contracts\PersonLookup;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HadithModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_collection_exposes_declared_and_available_counts_separately(): void
    {
        // `total_hadith` is what the BOOK says it contains; the available count
        // is what a reader can open. Conflating them makes the site claim
        // content it does not serve.
        $collection = HadithCollection::factory()->create(['total_hadith' => 7563]);

        Hadith::factory()->count(2)->create(['collection_id' => $collection->id]);
        Hadith::factory()->unpublished()->create(['collection_id' => $collection->id]);

        $this->assertSame(7563, $collection->total_hadith);
        $this->assertSame(2, $collection->availableHadithsCount());
    }

    public function test_a_bookless_hadith_is_valid(): void
    {
        $collection = HadithCollection::factory()->bookless()->create();
        $hadith = Hadith::factory()->create(['collection_id' => $collection->id, 'book_id' => null]);

        $this->assertNull($hadith->book_id);
        $this->assertTrue($hadith->exists);
    }

    public function test_two_hadiths_may_share_a_displayed_number(): void
    {
        $collection = HadithCollection::factory()->create();

        Hadith::factory()->create([
            'collection_id' => $collection->id, 'number' => '1',
            'reference_key' => 'a', 'sort_order' => 1,
        ]);
        $second = Hadith::factory()->create([
            'collection_id' => $collection->id, 'number' => '1',
            'reference_key' => 'b', 'sort_order' => 2,
        ]);

        // Distinct rows, same printed number — and each is separately
        // addressable by its reference_key.
        $this->assertSame('1', $second->number);
        $this->assertSame(2, Hadith::where('collection_id', $collection->id)->count());
    }

    public function test_reference_key_is_unique_within_a_collection(): void
    {
        $collection = HadithCollection::factory()->create();
        Hadith::factory()->create(['collection_id' => $collection->id, 'reference_key' => 'dup', 'sort_order' => 1]);

        $this->expectException(QueryException::class);

        Hadith::factory()->create(['collection_id' => $collection->id, 'reference_key' => 'dup', 'sort_order' => 2]);
    }

    public function test_the_same_reference_key_may_repeat_across_collections(): void
    {
        $a = HadithCollection::factory()->create();
        $b = HadithCollection::factory()->create();

        Hadith::factory()->create(['collection_id' => $a->id, 'reference_key' => '1-1']);
        $second = Hadith::factory()->create(['collection_id' => $b->id, 'reference_key' => '1-1']);

        $this->assertSame('1-1', $second->reference_key);
    }

    public function test_grade_casts_to_the_enum_and_may_be_null(): void
    {
        $graded = Hadith::factory()->create();
        $ungraded = Hadith::factory()->ungraded()->create();

        $this->assertSame(Grade::Sahih, $graded->grade);
        $this->assertSame('صحيح', $graded->grade->label('ar'));
        // Null is a real outcome: conflicting or unmapped source claims.
        $this->assertNull($ungraded->grade);
    }

    public function test_a_record_without_arabic_is_stored_but_not_publishable(): void
    {
        $hadith = Hadith::factory()->withoutArabic()->create();

        $this->assertNull($hadith->text_ar);
        $this->assertFalse($hadith->isPublishable());
        $this->assertFalse($hadith->is_published);

        // And the database refuses to publish it even if the app tries.
        $this->expectException(QueryException::class);
        $hadith->update(['is_published' => true]);
    }

    public function test_public_visibility_requires_every_ancestor_published(): void
    {
        $collection = HadithCollection::factory()->create();
        $book = HadithBook::factory()->create(['collection_id' => $collection->id]);
        $chapter = HadithChapter::factory()->create(['book_id' => $book->id]);

        $visible = Hadith::factory()->create([
            'collection_id' => $collection->id, 'book_id' => $book->id, 'chapter_id' => $chapter->id,
        ]);

        $this->assertSame(1, Hadith::publiclyVisible()->count());

        // Unpublishing the BOOK alone must hide the hadith, even though the
        // hadith's own flag is untouched.
        $book->update(['is_published' => false]);
        $this->assertSame(0, Hadith::publiclyVisible()->count());
        $this->assertTrue($visible->fresh()->is_published);
    }

    public function test_a_bookless_hadith_is_publicly_visible_without_a_book(): void
    {
        $collection = HadithCollection::factory()->bookless()->create();
        Hadith::factory()->create(['collection_id' => $collection->id, 'book_id' => null]);

        // The visibility scope must not require a book that never existed.
        $this->assertSame(1, Hadith::publiclyVisible()->count());
    }

    public function test_only_one_narrator_may_be_primary(): void
    {
        $hadith = Hadith::factory()->create();
        $a = Muslim::factory()->create();
        $b = Muslim::factory()->create();

        HadithNarrator::create([
            'hadith_id' => $hadith->id, 'narrator_id' => $a->id,
            'position' => 1, 'is_primary' => true,
        ]);

        $this->expectException(QueryException::class);

        HadithNarrator::create([
            'hadith_id' => $hadith->id, 'narrator_id' => $b->id,
            'position' => 2, 'is_primary' => true,
        ]);
    }

    public function test_a_chain_keeps_source_order_and_the_source_spelling(): void
    {
        $hadith = Hadith::factory()->create();
        $a = Muslim::factory()->create();
        $b = Muslim::factory()->create();

        HadithNarrator::create([
            'hadith_id' => $hadith->id, 'narrator_id' => $b->id, 'position' => 2,
            'source_name_ar' => 'الثاني',
        ]);
        HadithNarrator::create([
            'hadith_id' => $hadith->id, 'narrator_id' => $a->id, 'position' => 1,
            'is_primary' => true, 'source_name_ar' => 'الأول',
        ]);

        $chain = $hadith->narrators()->get();

        $this->assertSame([1, 2], $chain->pluck('position')->all());
        $this->assertSame('الأول', $chain->first()->sourceName('ar'));
    }

    public function test_a_person_is_reached_through_the_shared_contract_not_a_relation(): void
    {
        // Hadith may hold a foreign key into `muslims` but must never import
        // the People module. PersonLookup is the seam.
        $muslim = Muslim::factory()->create(['name_ar' => 'أبو هريرة']);
        $hadith = Hadith::factory()->create();

        HadithNarrator::create([
            'hadith_id' => $hadith->id, 'narrator_id' => $muslim->id, 'position' => 1,
        ]);

        $reference = app(PersonLookup::class)->byId($muslim->id);

        $this->assertNotNull($reference);
        $this->assertSame('أبو هريرة', $reference->name('ar'));
        $this->assertFalse(method_exists(HadithNarrator::class, 'narrator'));
    }

    public function test_the_public_path_uses_the_colon_form(): void
    {
        $collection = HadithCollection::factory()->create(['slug' => 'bukhari']);
        $hadith = Hadith::factory()->create([
            'collection_id' => $collection->id, 'reference_key' => '1-1',
        ]);

        // A slash would be ambiguous with /hadith/{slug}/{bookNumber}.
        $this->assertSame('/hadith/bukhari:1-1', $hadith->publicPath());
    }

    public function test_book_and_chapter_intros_are_translatable_json(): void
    {
        $book = HadithBook::factory()->create(['intro' => ['ar' => 'مقدمة', 'en' => 'Intro']]);
        $chapter = HadithChapter::factory()->create([
            'book_id' => $book->id,
            'ending' => ['ar' => 'خاتمة', 'en' => 'Ending'],
        ]);

        $this->assertSame('مقدمة', $book->getTranslation('intro', 'ar'));
        $this->assertSame('Ending', $chapter->getTranslation('ending', 'en'));
    }

    public function test_a_book_title_falls_back_when_english_is_absent(): void
    {
        $book = HadithBook::factory()->create(['title_ar' => 'كتاب الإيمان', 'title_en' => null]);

        // Sources legitimately omit the English title; render Arabic rather
        // than a blank.
        $this->assertSame('كتاب الإيمان', $book->title('en'));
    }
}
