<?php

declare(strict_types=1);

namespace Tests\Feature\People;

use App\Domain\People\Enums\Generation;
use App\Domain\People\Models\IslamicRole;
use App\Domain\People\Models\Muslim;
use App\Domain\People\ValueObjects\MuslimProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MuslimAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return $this->createAdminUser();
    }

    public function test_admin_can_create_a_person_with_multiple_roles(): void
    {
        $sahabi = IslamicRole::factory()->sahabi()->create();
        $narrator = IslamicRole::factory()->narrator()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.sahaba.store'), [
                'slug' => 'abu-hurayrah',
                'name_ar' => 'أبو هريرة',
                'name_en' => 'Abu Hurayrah',
                'is_published' => '1',
                'roles' => [
                    ['role_id' => $sahabi->id, 'is_featured' => '1'],
                    ['role_id' => $narrator->id],
                ],
            ])
            ->assertRedirect();

        $muslim = Muslim::where('slug', 'abu-hurayrah')->firstOrFail();

        // One record, two roles — not two records.
        $this->assertSame(2, $muslim->roles()->count());
        $this->assertDatabaseCount('muslims', 1);
    }

    public function test_a_person_with_no_name_in_either_language_is_rejected(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.sahaba.store'), ['slug' => 'nameless', 'name_ar' => '', 'name_en' => ''])
            ->assertSessionHasErrors('name_ar');

        $this->assertDatabaseCount('muslims', 0);
    }

    public function test_editing_a_name_updates_the_search_column_without_a_reindex_step(): void
    {
        $muslim = Muslim::factory()->create(['name_ar' => 'قديم', 'slug' => 'to-rename']);

        $this->actingAs($this->admin())
            ->put(route('admin.sahaba.update', $muslim), [
                'slug' => 'to-rename',
                'name_ar' => 'الاسم الجديد',
                'name_en' => $muslim->name_en,
            ])
            ->assertRedirect();

        // FR-035: an editor's correction must be findable immediately.
        $this->assertStringContainsString('الاسم الجديد', (string) $muslim->fresh()->search_name_ar);
    }

    public function test_assigning_a_descendant_as_parent_is_rejected_in_the_admin(): void
    {
        $a = Muslim::factory()->create();
        $b = Muslim::factory()->create(['father_id' => $a->id]);
        $c = Muslim::factory()->create(['father_id' => $b->id]);

        // A→B→C; making C the father of A closes a deep loop.
        $this->actingAs($this->admin())
            ->put(route('admin.sahaba.update', $a), [
                'slug' => $a->slug,
                'name_ar' => $a->name_ar,
                'father_id' => $c->id,
            ])
            ->assertSessionHasErrors('father_id');

        $this->assertNull($a->fresh()->father_id);
    }

    public function test_the_picker_returns_unpublished_people_and_excludes_the_edited_record(): void
    {
        $self = Muslim::factory()->create(['name_ar' => 'عمر بن الخطاب', 'slug' => 'self']);
        $draft = Muslim::factory()->unpublished()->create(['name_ar' => 'عمر المخفي', 'slug' => 'draft']);

        $response = $this->actingAs($this->admin())
            ->getJson(route('admin.sahaba.picker', ['q' => 'عمر', 'exclude' => $self->id]))
            ->assertOk();

        $slugs = array_column($response->json('data'), 'slug');

        // An unpublished person is still a valid ancestor, so the picker must
        // offer them — while the edited record itself must not be selectable.
        $this->assertContains('draft', $slugs);
        $this->assertNotContains('self', $slugs);
    }

    public function test_the_picker_requires_authentication(): void
    {
        // 401 rather than 302: it is a JSON request, so Laravel returns
        // unauthorized instead of redirecting to the login page. This endpoint
        // is the only surface exposing unpublished people, so it is the one
        // that must not leak.
        $this->getJson(route('admin.sahaba.picker', ['q' => 'عمر']))
            ->assertUnauthorized();
    }

    public function test_unpublished_people_are_visible_in_the_admin_list_but_not_publicly(): void
    {
        Muslim::factory()->unpublished()->create(['slug' => 'hidden-person', 'name_ar' => 'مخفي']);

        $this->actingAs($this->admin())
            ->get(route('admin.sahaba.index'))
            ->assertOk()
            ->assertSee('مخفي', false);

        $this->get(route('public.sahaba.show', 'hidden-person'))->assertNotFound();
    }

    /**
     * The sidebar's Companions / Narrators / Scholars items are this one list
     * scoped by `?role=`, so the scope has to actually exclude other roles.
     */
    public function test_the_role_scope_shows_only_people_carrying_that_role(): void
    {
        $narrator = IslamicRole::factory()->narrator()->create();
        $scholar = IslamicRole::factory()->create(['slug' => 'scholar', 'name_ar' => 'عالم', 'name_en' => 'Scholar']);

        $onlyNarrator = Muslim::factory()->create(['slug' => 'a-narrator', 'name_ar' => 'راوي فقط']);
        $onlyScholar = Muslim::factory()->create(['slug' => 'a-scholar', 'name_ar' => 'عالم فقط']);
        $onlyNarrator->roles()->attach($narrator);
        $onlyScholar->roles()->attach($scholar);

        $this->actingAs($this->admin())
            ->get(route('admin.sahaba.index', ['role' => 'narrator']))
            ->assertOk()
            ->assertSee('راوي فقط', false)
            ->assertDontSee('عالم فقط', false);
    }

    /** A stale bookmark must not present an empty list as the truth. */
    public function test_an_unknown_role_scope_falls_back_to_the_full_list(): void
    {
        Muslim::factory()->create(['slug' => 'somebody', 'name_ar' => 'شخص ما']);

        $this->actingAs($this->admin())
            ->get(route('admin.sahaba.index', ['role' => 'no-such-role']))
            ->assertOk()
            ->assertSee('شخص ما', false);
    }

    public function test_the_islamic_roles_page_lists_each_role_with_its_people_count(): void
    {
        $sahabi = IslamicRole::factory()->sahabi()->create();
        Muslim::factory()->count(2)->create()->each(fn (Muslim $m) => $m->roles()->attach($sahabi));

        // No role assigned — the page has to surface these, they show on no
        // role-scoped list at all.
        Muslim::factory()->create(['slug' => 'unclassified']);

        $this->actingAs($this->admin())
            ->get(route('admin.sahaba.roles'))
            ->assertOk()
            ->assertSee($sahabi->slug)
            ->assertSee(__('sahaba.roles_page.unassigned_hint'), false);
    }

    /** The bulk toolbar posted `status`, which the handler never read. */
    public function test_bulk_publish_and_unpublish_change_the_published_flag(): void
    {
        $muslim = Muslim::factory()->unpublished()->create();

        $this->actingAs($this->admin())
            ->from(route('admin.sahaba.index'))
            ->post(route('admin.sahaba.bulk-status'), ['ids' => [$muslim->id], 'is_published' => '1'])
            ->assertRedirect(route('admin.sahaba.index'));
        $this->assertTrue((bool) $muslim->fresh()->is_published);

        $this->actingAs($this->admin())
            ->from(route('admin.sahaba.index'))
            ->post(route('admin.sahaba.bulk-status'), ['ids' => [$muslim->id], 'is_published' => '0'])
            ->assertRedirect(route('admin.sahaba.index'));
        $this->assertFalse((bool) $muslim->fresh()->is_published);
    }

    /**
     * Roles are seeded deliberately: the form's role checkboxes are the branch
     * that only runs when roles exist, and it is where the form broke before.
     */
    public function test_the_create_edit_and_show_screens_render(): void
    {
        $sahabi = IslamicRole::factory()->sahabi()->create();
        IslamicRole::factory()->narrator()->create();

        $muslim = Muslim::factory()->create();
        $muslim->roles()->attach($sahabi, ['is_featured' => true]);

        $this->actingAs($this->admin())->get(route('admin.sahaba.create'))
            ->assertOk()
            ->assertSee('role-'.$sahabi->slug);

        // The featured pivot has to survive a round trip into the form.
        $this->actingAs($this->admin())->get(route('admin.sahaba.edit', $muslim))
            ->assertOk()
            ->assertSee('role-featured-'.$sahabi->slug);

        $this->actingAs($this->admin())->get(route('admin.sahaba.show', $muslim))
            ->assertOk()
            ->assertSee($sahabi->name_ar, false);
    }

    /** Editing must not drop the roles a person already carries. */
    public function test_updating_a_person_keeps_their_featured_role_assignment(): void
    {
        $sahabi = IslamicRole::factory()->sahabi()->create();
        $muslim = Muslim::factory()->create(['slug' => 'keeps-roles']);

        $this->actingAs($this->admin())
            ->put(route('admin.sahaba.update', $muslim), [
                'slug' => 'keeps-roles',
                'name_ar' => 'اسم',
                'roles' => [['role_id' => $sahabi->id, 'is_featured' => '1']],
            ])
            ->assertRedirect();

        $pivot = $muslim->fresh()->roles()->firstOrFail()->pivot;
        $this->assertTrue((bool) $pivot->is_featured);
    }

    // ------------------------------------------------------------------
    // Extended profile (muslims.profile JSON, driven by MuslimProfile::fields())
    // ------------------------------------------------------------------

    public function test_extended_profile_fields_round_trip_through_the_form(): void
    {
        $muslim = Muslim::factory()->create(['slug' => 'with-profile']);

        $this->actingAs($this->admin())
            ->put(route('admin.sahaba.update', $muslim), [
                'slug' => 'with-profile',
                'name_ar' => 'اسم',
                'profile' => [
                    'tribe_ar' => 'من بني هاشم',
                    'tribe_en' => 'Of Banu Hashim',
                    'birth_year_ce' => '571',
                ],
            ])
            ->assertRedirect();

        $profile = $muslim->fresh()->profile;
        $this->assertSame('من بني هاشم', $profile->localized('tribe', 'ar'));
        $this->assertSame('Of Banu Hashim', $profile->localized('tribe', 'en'));
        // Coerced to int by the field's type, not stored as the posted string.
        $this->assertSame(571, $profile->get('birth_year_ce'));
    }

    /** Emptying an input must clear the value, not leave the previous one. */
    public function test_clearing_a_profile_field_removes_it(): void
    {
        $muslim = Muslim::factory()->create(['slug' => 'clear-profile']);
        $muslim->update(['profile' => MuslimProfile::fromArray(['tribe_ar' => 'قديم'])]);

        $this->actingAs($this->admin())
            ->put(route('admin.sahaba.update', $muslim), [
                'slug' => 'clear-profile',
                'name_ar' => 'اسم',
                'profile' => ['tribe_ar' => ''],
            ])
            ->assertRedirect();

        $this->assertTrue($muslim->fresh()->profile->isEmpty());
        // Empty stores NULL rather than `{}`, so "has no profile" stays truthful.
        $this->assertNull($muslim->fresh()->getRawOriginal('profile'));
    }

    /**
     * The prophetic-mission fields describe the start of revelation. They must
     * not be storable on anyone who does not carry the `prophet` role, even if
     * the values are posted directly.
     */
    public function test_mission_fields_are_dropped_for_a_person_without_the_prophet_role(): void
    {
        $sahabi = IslamicRole::factory()->sahabi()->create();
        $muslim = Muslim::factory()->create(['slug' => 'not-a-prophet']);

        $this->actingAs($this->admin())
            ->put(route('admin.sahaba.update', $muslim), [
                'slug' => 'not-a-prophet',
                'name_ar' => 'اسم',
                'roles' => [['role_id' => $sahabi->id]],
                'profile' => ['tribe_ar' => 'قريش', 'mission_age' => 40, 'mission_place_ar' => 'غار حراء'],
            ])
            ->assertRedirect();

        $profile = $muslim->fresh()->profile;
        $this->assertSame('قريش', $profile->localized('tribe', 'ar'));
        $this->assertNull($profile->get('mission_age'));
        $this->assertNull($profile->get('mission_place_ar'));
    }

    public function test_mission_fields_are_stored_for_the_prophet(): void
    {
        $prophetRole = IslamicRole::factory()->create([
            'slug' => 'prophet', 'name_ar' => 'نبي', 'name_en' => 'Prophet',
        ]);
        $muslim = Muslim::factory()->create(['slug' => 'the-prophet']);

        $this->actingAs($this->admin())
            ->put(route('admin.sahaba.update', $muslim), [
                'slug' => 'the-prophet',
                'name_ar' => 'محمد ﷺ',
                // Role assigned in the same submit — the restricted fields have
                // to be accepted, not rejected because the record is stale.
                'roles' => [['role_id' => $prophetRole->id]],
                'profile' => ['mission_place_ar' => 'غار حراء', 'mission_age' => 40],
            ])
            ->assertRedirect();

        $profile = $muslim->fresh()->profile;
        $this->assertSame('غار حراء', $profile->localized('mission_place', 'ar'));
        $this->assertSame(40, $profile->get('mission_age'));
    }

    /** A key not in the registry cannot invent itself a field. */
    public function test_an_undefined_profile_key_is_not_stored(): void
    {
        $muslim = Muslim::factory()->create(['slug' => 'no-junk']);

        $this->actingAs($this->admin())
            ->put(route('admin.sahaba.update', $muslim), [
                'slug' => 'no-junk',
                'name_ar' => 'اسم',
                'profile' => ['tribe_ar' => 'قريش', 'totally_made_up' => 'junk'],
            ])
            ->assertRedirect();

        $this->assertNull($muslim->fresh()->profile->get('totally_made_up'));
    }

    /** Free text is gone: الطبقة is a closed set now. */
    public function test_an_unknown_generation_is_rejected(): void
    {
        $muslim = Muslim::factory()->create(['slug' => 'gen-check']);

        $this->actingAs($this->admin())
            ->put(route('admin.sahaba.update', $muslim), [
                'slug' => 'gen-check',
                'name_ar' => 'اسم',
                'generation' => 'not-a-real-generation',
            ])
            ->assertSessionHasErrors('generation');

        $this->actingAs($this->admin())
            ->put(route('admin.sahaba.update', $muslim), [
                'slug' => 'gen-check',
                'name_ar' => 'اسم',
                'generation' => Generation::Successor->value,
            ])
            ->assertRedirect();

        $this->assertSame(Generation::Successor->value, $muslim->fresh()->generation);
    }

    /** The modal browser opens on the latest people, excluding the edited one. */
    public function test_the_browse_endpoint_paginates_and_excludes_the_edited_person(): void
    {
        $people = Muslim::factory()->count(12)->create();
        $excluded = $people->last();

        $response = $this->actingAs($this->admin())
            ->getJson(route('admin.sahaba.browse', ['exclude' => $excluded->id]))
            ->assertOk();

        $ids = array_column($response->json('data'), 'id');

        $this->assertCount(10, $ids);
        $this->assertNotContains($excluded->id, $ids);
        $this->assertSame(11, $response->json('meta.total'));
        // Newest first: an editor picking a parent usually wants a recent add.
        $this->assertSame($people[10]->id, $ids[0]);
    }

    public function test_the_browse_endpoint_requires_authentication(): void
    {
        $this->getJson(route('admin.sahaba.browse'))->assertUnauthorized();
    }
}
