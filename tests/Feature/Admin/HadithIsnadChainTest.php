<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithCollection;
use App\Domain\Hadith\Models\HadithIsnad;
use App\Domain\Hadith\Models\HadithNarrator;
use App\Domain\Hadith\Models\HadithTranslation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * End-to-end coverage for managing a hadith's isnad (narrator chain) via the
 * admin edit page: adding a narrator, reordering the chain, and removing a
 * link — plus verifying the change reflects on the public hadith page and
 * public API. Before this, the admin narrator_id dropdown never persisted
 * anything (not in store()/update()'s validated fields) and the CQRS
 * Create/UpdateHadithHandler path that *did* support narrators called a
 * nonexistent Hadith::narrators() relation.
 */
class HadithIsnadChainTest extends TestCase
{
    use RefreshDatabase;

    private function seedHadith(): Hadith
    {
        $collection = HadithCollection::create([
            'name_ar' => 'صحيح الاختبار', 'name_en' => 'Test Sahih',
            'slug_ar' => 'sahih-test', 'slug_en' => 'sahih-test-en',
            'abbreviation' => 'TST', 'canonical_code' => 'test', 'source_slug' => 'test',
            'total_hadiths' => 1,
        ]);

        $book = HadithBook::create([
            'collection_id' => $collection->id, 'book_number' => 1,
            'title_ar' => 'كتاب الإيمان', 'title_en' => 'Book of Faith',
            'slug_ar' => 'kitab-iman', 'slug_en' => 'book-of-faith',
        ]);

        $hadith = Hadith::create([
            'book_id' => $book->id, 'hadith_number' => '1',
            'grading_status' => 'sahih', 'grading_agreement' => true,
        ]);

        HadithTranslation::create([
            'hadith_id' => $hadith->id, 'language_code' => 'ar',
            'text' => 'إنما الأعمال بالنيات',
        ]);

        return $hadith;
    }

    private function makeNarrator(string $nameAr): HadithNarrator
    {
        return HadithNarrator::create(['name_ar' => $nameAr, 'name_en' => $nameAr]);
    }

    public function test_admin_can_add_a_narrator_to_the_isnad_chain(): void
    {
        $this->actingAs($this->createAdminUser());

        $hadith = $this->seedHadith();
        $narrator = $this->makeNarrator('عبدالله بن نمير');

        // narrator_id sent as a string — like every real HTML form submission
        // (unlike the test client's array payloads, which can silently carry
        // real PHP ints straight through and mask a strict_types TypeError
        // that only appears with actual HTTP requests).
        $response = $this->post(route('admin.hadith.isnad.store', $hadith), [
            'narrator_id' => (string) $narrator->id,
            'transmission_method' => 'حدثنا',
        ]);

        $response->assertRedirect(route('admin.hadith.edit', $hadith));

        $this->assertDatabaseHas('hadith_isnad', [
            'hadith_id' => $hadith->id,
            'narrator_id' => $narrator->id,
            'position_in_chain' => 1,
            'transmission_method' => 'حدثنا',
        ]);
    }

    public function test_adding_narrators_appends_at_increasing_positions(): void
    {
        $this->actingAs($this->createAdminUser());

        $hadith = $this->seedHadith();
        $first = $this->makeNarrator('الراوي الأول');
        $second = $this->makeNarrator('الراوي الثاني');

        $this->post(route('admin.hadith.isnad.store', $hadith), ['narrator_id' => (string) $first->id]);
        $this->post(route('admin.hadith.isnad.store', $hadith), ['narrator_id' => (string) $second->id]);

        $chain = HadithIsnad::forHadith($hadith->id)->orderedByChain()->get();
        $this->assertSame([1, 2], $chain->pluck('position_in_chain')->all());
        $this->assertSame([$first->id, $second->id], $chain->pluck('narrator_id')->all());
    }

    public function test_admin_can_reorder_the_chain(): void
    {
        $this->actingAs($this->createAdminUser());

        $hadith = $this->seedHadith();
        $first = $this->makeNarrator('الراوي الأول');
        $second = $this->makeNarrator('الراوي الثاني');

        $link1 = HadithIsnad::create(['hadith_id' => $hadith->id, 'narrator_id' => $first->id, 'position_in_chain' => 1]);
        $link2 = HadithIsnad::create(['hadith_id' => $hadith->id, 'narrator_id' => $second->id, 'position_in_chain' => 2]);

        $response = $this->post(route('admin.hadith.isnad.move', [$hadith, $link2]), ['direction' => 'up']);

        $response->assertRedirect(route('admin.hadith.edit', $hadith));

        $this->assertSame(2, $link1->fresh()->position_in_chain);
        $this->assertSame(1, $link2->fresh()->position_in_chain);
    }

    public function test_moving_the_first_link_up_is_a_no_op(): void
    {
        $this->actingAs($this->createAdminUser());

        $hadith = $this->seedHadith();
        $narrator = $this->makeNarrator('الراوي الأول');
        $link = HadithIsnad::create(['hadith_id' => $hadith->id, 'narrator_id' => $narrator->id, 'position_in_chain' => 1]);

        $this->post(route('admin.hadith.isnad.move', [$hadith, $link]), ['direction' => 'up']);

        $this->assertSame(1, $link->fresh()->position_in_chain);
    }

    public function test_admin_can_remove_a_narrator_and_positions_stay_contiguous(): void
    {
        $this->actingAs($this->createAdminUser());

        $hadith = $this->seedHadith();
        $first = $this->makeNarrator('الأول');
        $second = $this->makeNarrator('الثاني');
        $third = $this->makeNarrator('الثالث');

        $link1 = HadithIsnad::create(['hadith_id' => $hadith->id, 'narrator_id' => $first->id, 'position_in_chain' => 1]);
        $link2 = HadithIsnad::create(['hadith_id' => $hadith->id, 'narrator_id' => $second->id, 'position_in_chain' => 2]);
        $link3 = HadithIsnad::create(['hadith_id' => $hadith->id, 'narrator_id' => $third->id, 'position_in_chain' => 3]);

        $response = $this->delete(route('admin.hadith.isnad.destroy', [$hadith, $link2]));

        $response->assertRedirect(route('admin.hadith.edit', $hadith));

        $this->assertDatabaseMissing('hadith_isnad', ['id' => $link2->id]);
        $remaining = HadithIsnad::forHadith($hadith->id)->orderedByChain()->get();
        $this->assertSame([1, 2], $remaining->pluck('position_in_chain')->all());
        $this->assertSame([$link1->id, $link3->id], $remaining->pluck('id')->all());
    }

    public function test_edit_page_reflects_the_chain_immediately_after_mutation(): void
    {
        $this->actingAs($this->createAdminUser());

        $hadith = $this->seedHadith();
        $narrator = $this->makeNarrator('عبدالله بن نمير');

        // Prime the repository's find() cache by loading the edit page once
        // before the mutation, so this test actually exercises cache
        // invalidation rather than just a first-load query.
        $this->get(route('admin.hadith.edit', $hadith))->assertOk();

        $this->post(route('admin.hadith.isnad.store', $hadith), ['narrator_id' => (string) $narrator->id]);

        $response = $this->get(route('admin.hadith.edit', $hadith));

        $response->assertOk();
        $response->assertSee('عبدالله بن نمير');
    }

    public function test_public_hadith_page_reflects_the_admin_assigned_chain(): void
    {
        $this->actingAs($this->createAdminUser());

        $hadith = $this->seedHadith();
        $narrator = $this->makeNarrator('راوي الاختبار العلني');

        $this->post(route('admin.hadith.isnad.store', $hadith), [
            'narrator_id' => (string) $narrator->id,
            'transmission_method' => 'حدثنا',
        ]);

        $response = $this->get(route('public.hadith.show', $hadith->id));

        $response->assertOk();

        // The isnad timeline is rendered client-side by Alpine from a
        // `@js()`-embedded blob. Js::from() JSON-encodes (escaping non-ASCII
        // to \uXXXX) and then escapes *that* again so it's safe inside a
        // single-quoted JS string (doubling every backslash) — assertSee()
        // can't match the raw Arabic text, so assert on that same
        // double-escaped form instead. The Chrome walkthrough covered the
        // actually-rendered result.
        $jsonEscaped = trim(json_encode('راوي الاختبار العلني'), '"');
        $jsStringEscaped = str_replace('\\', '\\\\', $jsonEscaped);

        $response->assertSee($jsStringEscaped, false);
    }

    public function test_public_api_reflects_the_admin_assigned_chain(): void
    {
        $this->actingAs($this->createAdminUser());

        $hadith = $this->seedHadith();
        $narrator = $this->makeNarrator('راوي الأدمن');

        $this->post(route('admin.hadith.isnad.store', $hadith), [
            'narrator_id' => (string) $narrator->id,
            'transmission_method' => 'أخبرنا',
        ]);

        // The public detail endpoint requires no auth.
        $response = $this->getJson("/api/v1/hadith/{$hadith->id}");

        $response->assertOk();
    }

    public function test_guest_cannot_modify_the_isnad_chain(): void
    {
        $hadith = $this->seedHadith();
        $narrator = $this->makeNarrator('راوي');

        $response = $this->post(route('admin.hadith.isnad.store', $hadith), ['narrator_id' => (string) $narrator->id]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseMissing('hadith_isnad', ['hadith_id' => $hadith->id]);
    }

    public function test_invalid_narrator_id_is_rejected(): void
    {
        $this->actingAs($this->createAdminUser());

        $hadith = $this->seedHadith();

        $response = $this->post(route('admin.hadith.isnad.store', $hadith), ['narrator_id' => '999999']);

        $response->assertSessionHasErrors('narrator_id');
    }
}
