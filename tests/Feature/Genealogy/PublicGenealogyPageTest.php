<?php

declare(strict_types=1);

namespace Tests\Feature\Genealogy;

use App\Domain\Content\Enums\SiteSettingType;
use App\Domain\Content\Services\SiteSettingsService;
use App\Domain\Genealogy\Enums\RelationshipType;
use App\Domain\Genealogy\Models\Person;
use App\Domain\Genealogy\Models\Relationship;
use App\Domain\Genealogy\Services\GenealogyService;
use App\Http\Controllers\Site\GenealogyController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The public genealogy (family tree) page has its own 1-hour Cache::remember
 * wrapper independent of PersonRepository's own version-based cache — an
 * admin edit was silently invisible on the public page for up to an hour
 * because nothing ever forgot that outer cache key. These guard the fix.
 *
 * Also covers the "know more about {name}" link: it must only appear for
 * genealogy entries that are real companions (is_sahabi), since
 * SahabiRepository::findBySlug() is scoped to onlySahaba() and would 404
 * for anyone else (prophet, tribe, other family entries). is_sahabi
 * defaults to true at the DB level and is only derived from person_type by
 * GenealogyService::createPerson()/updatePerson() — tests that build a
 * Person directly (bypassing the service, matching the existing convention
 * in this test suite) must set it explicitly.
 *
 * Note: the person JSON is embedded in an HTML attribute via Blade's `{{ }}`,
 * which HTML-escapes quotes to `&quot;` — assertions match that literal
 * escaped form rather than raw JSON quotes.
 */
class PublicGenealogyPageTest extends TestCase
{
    use RefreshDatabase;

    private function makePerson(string $slug, array $translations, array $attributes = []): Person
    {
        $person = Person::create(array_merge([
            'slug' => $slug,
            'gender' => 'male',
            'person_type' => 'family',
            'is_sahabi' => false,
        ], $attributes));

        $person->syncTranslations(['name' => $translations]);

        return $person;
    }

    private function updatePayload(Person $person, array $overrides = []): array
    {
        return array_merge([
            'name' => ['ar' => $person->getTranslation('name', 'ar'), 'en' => $person->getTranslation('name', 'en')],
            'person_type' => $person->person_type->value,
            'gender' => $person->gender,
        ], $overrides);
    }

    public function test_admin_edit_reflects_on_public_page_without_waiting_for_cache_ttl(): void
    {
        $person = $this->makePerson('cache-test-person', ['ar' => 'الاسم القديم', 'en' => 'Old Name']);

        // Warm the public page's 1-hour cache with the original name.
        $this->get(route('public.genealogy'))->assertOk();

        $this->actingAs($this->createAdminUser())
            ->put(route('admin.genealogy.update', $person->id), $this->updatePayload($person, [
                'name' => ['ar' => 'الاسم الجديد', 'en' => 'New Name'],
            ]))
            ->assertRedirect(route('admin.genealogy.index'));

        $response = $this->get(route('public.genealogy'));

        // Default app locale is 'ar', so the embedded payload carries the
        // Arabic translation.
        $response->assertOk();
        $response->assertSee('&quot;name&quot;:&quot;الاسم الجديد&quot;', false);
        $response->assertDontSee('&quot;name&quot;:&quot;الاسم القديم&quot;', false);
    }

    public function test_admin_delete_reflects_on_public_page_without_waiting_for_cache_ttl(): void
    {
        $person = $this->makePerson('to-be-deleted', ['ar' => 'شخص سيُحذف', 'en' => 'Person To Delete']);

        $this->get(route('public.genealogy'))->assertOk();

        $this->actingAs($this->createAdminUser())
            ->delete(route('admin.genealogy.destroy', $person->id))
            ->assertRedirect(route('admin.genealogy.index'));

        $response = $this->get(route('public.genealogy'));

        $response->assertOk();
        $response->assertDontSee('&quot;slug&quot;:&quot;to-be-deleted&quot;', false);
    }

    public function test_sahabi_genealogy_entry_has_a_working_profile_link(): void
    {
        $person = $this->makePerson('sahabi-with-profile', ['ar' => 'صحابي الاختبار', 'en' => 'A Companion'], [
            'person_type' => 'sahabi',
            'is_sahabi' => true,
        ]);

        $response = $this->get(route('public.genealogy'));
        $response->assertOk();
        $response->assertSee(
            '&quot;slug&quot;:&quot;sahabi-with-profile&quot;,&quot;name&quot;:&quot;صحابي الاختبار&quot;',
            false,
        );
        $response->assertSee('&quot;type&quot;:&quot;sahabi&quot;,&quot;typeLabel&quot;', false);
        $response->assertSee('&quot;hasProfile&quot;:true', false);

        // The link this unlocks must actually resolve, not 404.
        $this->get(route('public.sahaba.show', $person->slug))->assertOk();
    }

    public function test_non_sahabi_genealogy_entry_has_no_profile_link(): void
    {
        $person = $this->makePerson('tribe-entry-no-profile', ['ar' => 'مدخل قبيلة', 'en' => 'A Tribe Entry'], [
            'person_type' => 'tribe',
            'is_sahabi' => false,
        ]);

        $response = $this->get(route('public.genealogy'));
        $response->assertOk();
        $response->assertSee(
            '&quot;slug&quot;:&quot;tribe-entry-no-profile&quot;,&quot;name&quot;:&quot;مدخل قبيلة&quot;',
            false,
        );
        $response->assertSee('&quot;hasProfile&quot;:false', false);

        // Confirms the gate is load-bearing: this route really would 404.
        $this->get(route('public.sahaba.show', $person->slug))->assertNotFound();
    }

    public function test_arabic_page_does_not_present_an_english_biography_as_arabic(): void
    {
        $person = $this->makePerson('english-biography-only', ['ar' => 'صاحب السيرة', 'en' => 'Biography Owner']);
        $person->syncTranslations(['biography' => ['en' => 'English biography must not be mislabeled as Arabic.']]);

        $response = $this->get(route('public.genealogy'));

        $response->assertOk();
        $response->assertDontSee('English biography must not be mislabeled as Arabic.');
    }

    public function test_people_endpoint_paginates_beyond_the_initial_page(): void
    {
        for ($i = 1; $i <= 35; $i++) {
            $this->makePerson("bulk-person-{$i}", ['ar' => "شخص رقم {$i}", 'en' => "Bulk Person {$i}"]);
        }

        $pageOne = $this->getJson(route('public.genealogy.people', ['page' => 1]));
        $pageOne->assertOk();
        $this->assertCount(30, $pageOne->json('data'));
        $this->assertTrue($pageOne->json('hasMore'));
        $this->assertSame(2, $pageOne->json('nextPage'));

        $pageTwo = $this->getJson(route('public.genealogy.people', ['page' => 2]));
        $pageTwo->assertOk();
        $this->assertCount(5, $pageTwo->json('data'));
        $this->assertFalse($pageTwo->json('hasMore'));
        $this->assertNull($pageTwo->json('nextPage'));
    }

    public function test_people_endpoint_searches_across_the_full_dataset(): void
    {
        $this->makePerson('findable-person', ['ar' => 'اسم فريد للبحث', 'en' => 'Findable Person']);
        $this->makePerson('other-person', ['ar' => 'شخص آخر', 'en' => 'Other Person']);

        $response = $this->getJson(route('public.genealogy.people', ['q' => 'فريد']));

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name');
        $this->assertTrue($names->contains('اسم فريد للبحث'));
        $this->assertFalse($names->contains('شخص آخر'));
    }

    public function test_people_endpoint_rejects_an_overlong_query(): void
    {
        $response = $this->getJson(route('public.genealogy.people', ['q' => str_repeat('a', 101)]));

        $response->assertStatus(422);
    }

    private function makeProphetWithSpouse(bool $reverseDirection = false): array
    {
        $prophet = $this->makePerson('tree-root', ['ar' => 'الجد الأكبر', 'en' => 'Root Ancestor'], [
            'person_type' => 'prophet',
        ]);
        $wife = $this->makePerson('a-wife', ['ar' => 'زوجة الاختبار', 'en' => 'Test Wife'], [
            'gender' => 'female',
            'person_type' => 'sahabi',
            'is_sahabi' => true,
        ]);
        // Not father_id/mother_id — marriage is a `relationships` row, the same
        // table/type the admin genealogy screen and SahabiExporter already use.
        // The pivot has no privileged "husband"/"wife" column, so either row
        // direction must resolve identically.
        Relationship::create($reverseDirection ? [
            'person_id' => $wife->id,
            'related_person_id' => $prophet->id,
            'relationship_type' => RelationshipType::SPOUSE,
        ] : [
            'person_id' => $prophet->id,
            'related_person_id' => $wife->id,
            'relationship_type' => RelationshipType::SPOUSE,
        ]);

        // Give the prophet at least one child so hasTree is true and the tree renders.
        $this->makePerson('a-child', ['ar' => 'ابن الاختبار', 'en' => 'Test Child'], [
            'father_id' => $prophet->id,
        ]);

        return [$prophet, $wife];
    }

    public function test_prophet_spouse_appears_in_tree_payload_and_wives_filter(): void
    {
        $this->makeProphetWithSpouse();

        $response = $this->get(route('public.genealogy'));

        $response->assertOk();
        $response->assertSee(
            '&quot;slug&quot;:&quot;a-wife&quot;,&quot;name&quot;:&quot;زوجة الاختبار&quot;',
            false,
        );
        // Rendered in the dedicated spouses row above the tree, not just the JSON blob.
        $response->assertSee('زوجة الاختبار', false);
    }

    public function test_prophet_spouse_resolves_regardless_of_relationship_row_direction(): void
    {
        $this->makeProphetWithSpouse(reverseDirection: true);

        $this->get(route('public.genealogy'))->assertSee(
            '&quot;slug&quot;:&quot;a-wife&quot;,&quot;name&quot;:&quot;زوجة الاختبار&quot;',
            false,
        );
    }

    /**
     * These assert against GenealogyService/PersonRepository directly rather
     * than scraping the rendered page: the page's embedded $peopleJson is a
     * flat "everyone" list (fed by mapPeople()'s unfiltered latest-30 query)
     * independent of tree/spouse curation, so a hidden person can correctly
     * disappear from the tree/spouses row while still legitimately
     * appearing elsewhere on the same page — asserting "not present
     * anywhere in the HTML" would test the wrong thing.
     */
    public function test_child_hidden_from_tree_is_excluded_from_the_family_tree(): void
    {
        $prophet = $this->makePerson('curation-root', ['ar' => 'الجد', 'en' => 'Root'], ['person_type' => 'prophet']);
        $this->makePerson('visible-child', ['ar' => 'ابن ظاهر', 'en' => 'Visible Child'], ['father_id' => $prophet->id]);
        $this->makePerson('hidden-child', ['ar' => 'ابن مخفي', 'en' => 'Hidden Child'], [
            'father_id' => $prophet->id,
            'hide_from_tree' => true,
        ]);

        $tree = app(GenealogyService::class)->getFamilyTree($prophet->id, 1);
        $childSlugs = collect($tree['descendants'])->map(fn (array $d) => $d['person']->slug)->all();

        $this->assertContains('visible-child', $childSlugs);
        $this->assertNotContains('hidden-child', $childSlugs);
    }

    public function test_descendants_are_ordered_by_tree_display_order(): void
    {
        $prophet = $this->makePerson('order-root', ['ar' => 'الجد', 'en' => 'Root'], ['person_type' => 'prophet']);
        $this->makePerson('second-child', ['ar' => 'الثاني', 'en' => 'Second'], [
            'father_id' => $prophet->id,
            'tree_display_order' => 2,
        ]);
        $this->makePerson('first-child', ['ar' => 'الأول', 'en' => 'First'], [
            'father_id' => $prophet->id,
            'tree_display_order' => 1,
        ]);
        $this->makePerson('unordered-child', ['ar' => 'غير مرتب', 'en' => 'Unordered'], [
            'father_id' => $prophet->id,
        ]);

        $tree = app(GenealogyService::class)->getFamilyTree($prophet->id, 1);
        $childSlugs = collect($tree['descendants'])->map(fn (array $d) => $d['person']->slug)->all();

        // Explicit orders sort first (ascending), then unordered (null) last.
        $this->assertSame(['first-child', 'second-child', 'unordered-child'], $childSlugs);
    }

    public function test_max_children_per_level_setting_caps_tree_children(): void
    {
        app(SiteSettingsService::class)->set(
            'genealogy_tree_max_children_per_level',
            '1',
            ['group' => 'genealogy_tree', 'type' => SiteSettingType::TEXT->value],
        );

        $prophet = $this->makePerson('cap-root', ['ar' => 'الجد', 'en' => 'Root'], ['person_type' => 'prophet']);
        $this->makePerson('cap-child-a', ['ar' => 'أ', 'en' => 'A'], ['father_id' => $prophet->id, 'tree_display_order' => 1]);
        $this->makePerson('cap-child-b', ['ar' => 'ب', 'en' => 'B'], ['father_id' => $prophet->id, 'tree_display_order' => 2]);

        $payload = $this->invokeBuildPayload();

        $this->assertSame(1, count($payload['tree']['children']));
        $this->assertSame('cap-child-a', $payload['tree']['children'][0]['slug']);
    }

    public function test_hidden_spouse_relationship_is_excluded_from_the_spouse_list(): void
    {
        [$prophet] = $this->makeProphetWithSpouse();

        $hiddenWife = $this->makePerson('hidden-wife', ['ar' => 'زوجة مخفية', 'en' => 'Hidden Wife'], [
            'gender' => 'female',
            'person_type' => 'sahabi',
            'is_sahabi' => true,
        ]);
        Relationship::create([
            'person_id' => $prophet->id,
            'related_person_id' => $hiddenWife->id,
            'relationship_type' => RelationshipType::SPOUSE,
            'hide_from_tree' => true,
        ]);

        $spouseSlugs = app(GenealogyService::class)->getSpouses($prophet->id)->pluck('slug')->all();

        $this->assertContains('a-wife', $spouseSlugs);
        $this->assertNotContains('hidden-wife', $spouseSlugs);
    }

    public function test_spouses_are_ordered_by_display_order(): void
    {
        [$prophet] = $this->makeProphetWithSpouse();

        // "a-wife" (created above) has no explicit display_order (nulls
        // sort last); an admin promoting this second wife with an explicit
        // low order pulls it ahead of the unordered one.
        $secondWife = $this->makePerson('second-wife', ['ar' => 'زوجة ثانية', 'en' => 'Second Wife'], [
            'gender' => 'female',
            'person_type' => 'sahabi',
            'is_sahabi' => true,
        ]);
        Relationship::create([
            'person_id' => $prophet->id,
            'related_person_id' => $secondWife->id,
            'relationship_type' => RelationshipType::SPOUSE,
            'display_order' => 0,
        ]);

        $spouseSlugs = app(GenealogyService::class)->getSpouses($prophet->id)->pluck('slug')->all();

        $this->assertSame(['second-wife', 'a-wife'], $spouseSlugs);
    }

    public function test_max_spouses_shown_setting_caps_spouse_list(): void
    {
        [$prophet] = $this->makeProphetWithSpouse();

        $this->makePerson('second-wife-cap', ['ar' => 'زوجة ثانية', 'en' => 'Second Wife'], [
            'gender' => 'female',
            'person_type' => 'sahabi',
            'is_sahabi' => true,
        ]);
        Relationship::create([
            'person_id' => $prophet->id,
            'related_person_id' => Person::where('slug', 'second-wife-cap')->sole()->id,
            'relationship_type' => RelationshipType::SPOUSE,
        ]);

        $spouseSlugs = app(GenealogyService::class)->getSpouses($prophet->id, maxCount: 1)->pluck('slug')->all();

        $this->assertCount(1, $spouseSlugs);
        $this->assertSame(['a-wife'], $spouseSlugs);
    }

    /**
     * Runs the same payload assembly the public controller caches, via
     * reflection — the method is private and intentionally not part of the
     * controller's public surface, but is the only way to assert on the
     * assembled tree (with settings-driven caps applied) without scraping
     * rendered HTML.
     */
    private function invokeBuildPayload(): array
    {
        $controller = app(GenealogyController::class);
        $method = new \ReflectionMethod($controller, 'buildPayload');

        return $method->invoke($controller, 'ar');
    }
}
