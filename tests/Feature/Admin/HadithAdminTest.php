<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithChapter;
use App\Domain\Hadith\Models\HadithCollection;
use App\Domain\Hadith\Models\HadithNarrator;
use App\Domain\People\Models\Muslim;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Hadith module shipped a data layer and an importer but no panel, so 37,957
 * imported hadiths were readable by visitors and uneditable by editors. These
 * cover the CRUD, the filters, and the two things that fail silently:
 *
 *  - the search index columns going stale when the text is edited
 *  - ON DELETE RESTRICT surfacing as a 500 instead of an explanation
 */
class HadithAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return $this->createAdminUser(role: 'super_admin');
    }

    private function collection(array $attributes = []): HadithCollection
    {
        return HadithCollection::factory()->create($attributes);
    }

    /**
     * What the form actually posts. `number` and `sort_order` are NOT NULL with no
     * database default, so a payload missing them is a 500 rather than a blank —
     * the tests must send the same shape the browser does.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function hadithPayload(array $overrides = []): array
    {
        return array_merge([
            'reference_key' => '1-1',
            'number' => '1',
            'sort_order' => 0,
            'text_ar' => 'نص الحديث',
        ], $overrides);
    }

    // ── listing + filters ────────────────────────────────────────────────

    public function test_the_collections_index_loads(): void
    {
        $this->collection(['title_ar' => 'صحيح البخاري']);

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.hadith-collections.index'))
            ->assertOk()
            ->assertSee('صحيح البخاري', false);
    }

    public function test_a_bookless_collection_shows_direct_narrations_in_admin(): void
    {
        $collection = HadithCollection::factory()->bookless()->create(['slug' => 'shahwaliullah40']);
        Hadith::factory()->create(['collection_id' => $collection->id, 'text_ar' => 'حديث بلا أبواب']);

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.hadith-collections.show', $collection))
            ->assertOk()->assertSee('حديث بلا أبواب')
            ->assertDontSee('باب جديد');
    }

    public function test_deleting_a_hadith_preserves_the_collection_and_other_narrations(): void
    {
        $collection = HadithCollection::factory()->bookless()->create();
        $hadith = Hadith::factory()->create(['collection_id' => $collection->id]);
        $other = Hadith::factory()->create(['collection_id' => $collection->id]);
        $narrator = Muslim::factory()->create();
        HadithNarrator::create([
            'hadith_id' => $hadith->id, 'narrator_id' => $narrator->id,
            'position' => 1, 'is_primary' => true,
        ]);

        $this->actingAs($this->admin(), 'admin')
            ->delete(route('admin.hadiths.destroy', $hadith))
            ->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseMissing('hadiths', ['id' => $hadith->id]);
        $this->assertDatabaseHas('hadiths', ['id' => $other->id]);
        $this->assertDatabaseHas('hadith_collections', ['id' => $collection->id]);
        $this->assertDatabaseMissing('hadith_narrators', ['hadith_id' => $hadith->id]);
        $this->assertDatabaseHas('muslims', ['id' => $narrator->id]);
    }

    public function test_the_hadiths_index_loads_and_filters_by_collection(): void
    {
        $bukhari = $this->collection(['title_ar' => 'البخاري']);
        $muslim = $this->collection(['title_ar' => 'مسلم']);

        Hadith::factory()->create(['collection_id' => $bukhari->id, 'text_ar' => 'حديث البخاري هنا']);
        Hadith::factory()->create(['collection_id' => $muslim->id, 'text_ar' => 'حديث مسلم هنا']);

        $response = $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.hadiths.index', ['collection' => $bukhari->id]))
            ->assertOk();

        $response->assertSee('حديث البخاري هنا', false);
        $response->assertDontSee('حديث مسلم هنا', false);
    }

    public function test_the_hadiths_index_displays_available_english_text_when_arabic_is_missing(): void
    {
        Hadith::factory()->create([
            'collection_id' => $this->collection()->id,
            'text_ar' => null,
            'text_en' => 'The available English narration',
            'is_published' => false,
        ]);

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.hadiths.index'))
            ->assertOk()->assertSee('The available English narration');
    }

    public function test_hadiths_can_be_filtered_to_the_ungraded_ones(): void
    {
        $collection = $this->collection();
        Hadith::factory()->create(['collection_id' => $collection->id, 'grade' => 'sahih', 'text_ar' => 'مصحح']);
        Hadith::factory()->create(['collection_id' => $collection->id, 'grade' => null, 'text_ar' => 'بلا درجة']);

        $response = $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.hadiths.index', ['grade' => 'ungraded']))
            ->assertOk();

        $response->assertSee('بلا درجة', false);
        $response->assertDontSee('مصحح', false);
    }

    // ── CRUD ─────────────────────────────────────────────────────────────

    public function test_a_collection_can_be_created_and_updated(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.hadith-collections.store'), [
                'slug' => 'test-collection',
                'title_ar' => 'كتاب تجريبي',
                'is_published' => '1',
            ])
            ->assertRedirect();

        $collection = HadithCollection::where('slug', 'test-collection')->firstOrFail();
        $this->assertSame('كتاب تجريبي', $collection->title_ar);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.hadith-collections.update', $collection), [
                'slug' => 'test-collection',
                'title_ar' => 'كتاب معدَّل',
            ])
            ->assertRedirect();

        $this->assertSame('كتاب معدَّل', $collection->fresh()->title_ar);
    }

    public function test_a_duplicate_slug_is_rejected(): void
    {
        $this->collection(['slug' => 'bukhari']);

        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.hadith-collections.store'), ['slug' => 'bukhari', 'title_ar' => 'مكرر'])
            ->assertSessionHasErrors('slug');
    }

    public function test_a_hadith_can_be_created(): void
    {
        $collection = $this->collection();

        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.hadiths.store'), $this->hadithPayload([
                'collection_id' => $collection->id,
                'text_ar' => 'إنما الأعمال بالنيات',
                'is_published' => '1',
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('hadiths', ['collection_id' => $collection->id, 'reference_key' => '1-1']);
    }

    /**
     * reference_key is the addressable half of /hadith/{collection}:{key}, so a
     * duplicate inside one collection would shadow an existing hadith.
     */
    public function test_a_duplicate_reference_key_in_the_same_collection_is_rejected(): void
    {
        $collection = $this->collection();
        Hadith::factory()->create(['collection_id' => $collection->id, 'reference_key' => '1-1']);

        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.hadiths.store'), $this->hadithPayload(['collection_id' => $collection->id]))
            ->assertSessionHasErrors('reference_key');
    }

    public function test_the_same_reference_key_is_allowed_in_a_different_collection(): void
    {
        $a = $this->collection();
        $b = $this->collection();
        Hadith::factory()->create(['collection_id' => $a->id, 'reference_key' => '1-1']);

        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.hadiths.store'), $this->hadithPayload(['collection_id' => $b->id]))
            ->assertSessionHasNoErrors();
    }

    // ── the silent failures ──────────────────────────────────────────────

    /**
     * `search_ar` is what public search reads; `text_ar` is only displayed. An
     * edit that changed the text without rebuilding the index left the hadith
     * findable by its OLD wording, with the page still rendering correctly.
     */
    public function test_editing_the_text_rebuilds_the_search_index(): void
    {
        $collection = $this->collection();
        $hadith = Hadith::factory()->create([
            'collection_id' => $collection->id,
            'text_ar' => 'النص القديم',
            'search_ar' => 'النص القديم',
        ]);

        $this->actingAs($this->admin(), 'admin')
            ->put(route('admin.hadiths.update', $hadith), $this->hadithPayload([
                'collection_id' => $collection->id,
                'reference_key' => $hadith->reference_key,
                'text_ar' => 'إنما الأعمال بالنيات',
            ]))
            ->assertRedirect();

        $fresh = $hadith->fresh();

        $this->assertSame('إنما الأعمال بالنيات', $fresh->text_ar);
        $this->assertNotSame('النص القديم', $fresh->search_ar, 'the search index kept the old wording');
        $this->assertStringContainsString('الاعمال', (string) $fresh->search_ar);
    }

    /**
     * Every hadith FK is ON DELETE RESTRICT. Without the guard the database
     * throws and the editor sees a 500 rather than being told why.
     */
    public function test_a_collection_with_hadiths_cannot_be_deleted(): void
    {
        $collection = $this->collection();
        Hadith::factory()->create(['collection_id' => $collection->id]);

        $this->actingAs($this->admin(), 'admin')
            ->delete(route('admin.hadith-collections.destroy', $collection))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('hadith_collections', ['id' => $collection->id]);
    }

    public function test_an_empty_collection_can_be_deleted(): void
    {
        $collection = $this->collection();

        $this->actingAs($this->admin(), 'admin')
            ->delete(route('admin.hadith-collections.destroy', $collection))
            ->assertRedirect();

        $this->assertDatabaseMissing('hadith_collections', ['id' => $collection->id]);
    }

    /** A book from another collection would make the tree incoherent. */
    public function test_a_book_outside_the_chosen_collection_is_rejected(): void
    {
        $a = $this->collection();
        $b = $this->collection();
        $bookInB = HadithBook::factory()->create(['collection_id' => $b->id]);

        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.hadiths.store'), $this->hadithPayload([
                'collection_id' => $a->id,
                'book_id' => $bookInB->id,
                'reference_key' => '9-9',
            ]))
            ->assertSessionHasErrors('book_id');
    }

    public function test_a_chapter_without_a_book_is_rejected(): void
    {
        $collection = $this->collection();
        $book = HadithBook::factory()->create(['collection_id' => $collection->id]);
        $chapter = HadithChapter::factory()->create(['book_id' => $book->id]);

        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.hadiths.store'), $this->hadithPayload([
                'collection_id' => $collection->id,
                'chapter_id' => $chapter->id,
                'reference_key' => '8-8',
            ]))
            ->assertSessionHasErrors('chapter_id');
    }

    public function test_toggling_status_flips_it(): void
    {
        $collection = $this->collection(['is_published' => false]);

        $this->actingAs($this->admin(), 'admin')
            ->patch(route('admin.hadith-collections.toggle-status', $collection))
            ->assertRedirect();

        $this->assertTrue($collection->fresh()->is_published);
    }

    // ── access ───────────────────────────────────────────────────────────

    public function test_a_guest_cannot_reach_the_module(): void
    {
        $this->get(route('admin.hadiths.index'))->assertRedirect();
        $this->get(route('admin.hadith-collections.index'))->assertRedirect();
    }
}
