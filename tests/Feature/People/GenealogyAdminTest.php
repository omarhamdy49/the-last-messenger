<?php

declare(strict_types=1);

namespace Tests\Feature\People;

use App\Domain\People\Enums\RelationshipType;
use App\Domain\People\Models\IslamicRole;
use App\Domain\People\Models\Muslim;
use App\Domain\People\Models\MuslimRelationship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenealogyAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return $this->createAdminUser();
    }

    // ------------------------------------------------------------------
    // Explorer
    // ------------------------------------------------------------------

    public function test_the_explorer_shows_ancestors_and_descendants_of_the_chosen_root(): void
    {
        $grandfather = Muslim::factory()->create(['slug' => 'gf', 'name_ar' => 'الجد']);
        $father = Muslim::factory()->create(['slug' => 'f', 'name_ar' => 'الأب', 'father_id' => $grandfather->id]);
        $child = Muslim::factory()->create(['slug' => 'c', 'name_ar' => 'الابن', 'father_id' => $father->id]);

        $this->actingAs($this->admin())
            ->get(route('admin.genealogy.index', ['root' => 'f']))
            ->assertOk()
            ->assertSee('الجد', false)     // ancestor
            ->assertSee('الابن', false);   // descendant

        $this->assertNotNull($child->fresh()->father_id);
    }

    /**
     * An editor who cannot see draft relatives cannot tell an incomplete tree
     * from a finished one, so the admin explorer must include them.
     */
    public function test_the_explorer_includes_unpublished_relatives(): void
    {
        $root = Muslim::factory()->create(['slug' => 'root-person']);
        Muslim::factory()->unpublished()->create([
            'slug' => 'draft-child', 'name_ar' => 'ابن مسوّدة', 'father_id' => $root->id,
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.genealogy.index', ['root' => 'root-person']))
            ->assertOk()
            ->assertSee('ابن مسوّدة', false);
    }

    public function test_a_deeper_lineage_than_the_requested_depth_is_reported_not_silently_truncated(): void
    {
        $current = Muslim::factory()->create(['slug' => 'deep-root']);
        for ($i = 0; $i < 5; $i++) {
            $current = Muslim::factory()->create(['father_id' => $current->id]);
        }

        $this->actingAs($this->admin())
            ->get(route('admin.genealogy.index', ['root' => $current->slug, 'depth' => 2]))
            ->assertOk()
            ->assertSee(__('sahaba.genealogy.depth_capped', ['depth' => 2]), false);
    }

    public function test_an_unknown_root_slug_falls_back_instead_of_erroring(): void
    {
        Muslim::factory()->create(['slug' => 'somebody', 'name_ar' => 'شخص']);

        $this->actingAs($this->admin())
            ->get(route('admin.genealogy.index', ['root' => 'no-such-person']))
            ->assertOk();
    }

    public function test_the_explorer_requires_an_admin(): void
    {
        $this->get(route('admin.genealogy.index'))->assertRedirect(route('login'));
    }

    // ------------------------------------------------------------------
    // Descent
    // ------------------------------------------------------------------

    public function test_an_admin_can_assign_a_parent(): void
    {
        $child = Muslim::factory()->create();
        $father = Muslim::factory()->create();

        $this->actingAs($this->admin())
            ->put(route('admin.genealogy.update', $child), [
                'father_id' => $father->id,
                'mother_id' => '',
                'tree_display_order' => 3,
                'hide_from_tree' => '1',
            ])
            ->assertRedirect(route('admin.genealogy.show', $child));

        $child = $child->fresh();
        $this->assertSame($father->id, $child->father_id);
        $this->assertNull($child->mother_id);
        $this->assertSame(3, $child->tree_display_order);
        $this->assertTrue((bool) $child->hide_from_tree);
    }

    /** An empty picker means "no parent", not "leave the old one alone". */
    public function test_clearing_a_parent_nulls_the_column(): void
    {
        $father = Muslim::factory()->create();
        $child = Muslim::factory()->create(['father_id' => $father->id]);

        $this->actingAs($this->admin())
            ->put(route('admin.genealogy.update', $child), [
                'father_id' => '',
                'mother_id' => '',
            ])
            ->assertRedirect();

        $this->assertNull($child->fresh()->father_id);
    }

    public function test_assigning_a_descendant_as_a_parent_is_rejected_with_a_field_error(): void
    {
        $a = Muslim::factory()->create();
        $b = Muslim::factory()->create(['father_id' => $a->id]);
        $c = Muslim::factory()->create(['father_id' => $b->id]);

        // A→B→C; making C the father of A would close the loop.
        $this->actingAs($this->admin())
            ->put(route('admin.genealogy.update', $a), ['father_id' => $c->id])
            ->assertSessionHasErrors('father_id');

        $this->assertNull($a->fresh()->father_id);
    }

    public function test_a_person_cannot_be_their_own_parent(): void
    {
        $muslim = Muslim::factory()->create();

        $this->actingAs($this->admin())
            ->put(route('admin.genealogy.update', $muslim), ['father_id' => $muslim->id])
            ->assertSessionHasErrors('father_id');

        $this->assertNull($muslim->fresh()->father_id);
    }

    // ------------------------------------------------------------------
    // Non-descent edges
    // ------------------------------------------------------------------

    public function test_an_admin_can_add_a_relationship(): void
    {
        $muslim = Muslim::factory()->create();
        $spouse = Muslim::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.genealogy.relationships.store', $muslim), [
                'related_muslim_id' => $spouse->id,
                'relationship_type' => 'spouse',
                'notes' => 'ملاحظة',
                'sort_order' => 2,
            ])
            ->assertRedirect(route('admin.genealogy.show', $muslim));

        $this->assertDatabaseCount('muslim_relationships', 1);
        $row = MuslimRelationship::firstOrFail();
        $this->assertSame(RelationshipType::Spouse, $row->relationship_type);
        $this->assertSame('ملاحظة', $row->notes);
    }

    /**
     * The unique index cannot catch this on its own: (A,B,sibling) and
     * (B,A,sibling) are distinct rows to MySQL. Entering the same edge from the
     * other end must be a field error, not a 500.
     */
    public function test_the_same_relationship_entered_from_the_other_side_is_rejected(): void
    {
        $a = Muslim::factory()->create();
        $b = Muslim::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.genealogy.relationships.store', $a), [
                'related_muslim_id' => $b->id,
                'relationship_type' => 'sibling',
            ])->assertRedirect();

        $this->actingAs($this->admin())
            ->post(route('admin.genealogy.relationships.store', $b), [
                'related_muslim_id' => $a->id,
                'relationship_type' => 'sibling',
            ])->assertSessionHasErrors('relationship_type');

        $this->assertDatabaseCount('muslim_relationships', 1);
    }

    public function test_a_person_cannot_be_related_to_themselves(): void
    {
        $muslim = Muslim::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.genealogy.relationships.store', $muslim), [
                'related_muslim_id' => $muslim->id,
                'relationship_type' => 'sibling',
            ])
            ->assertSessionHasErrors('related_muslim_id');

        $this->assertDatabaseCount('muslim_relationships', 0);
    }

    public function test_an_admin_can_edit_a_relationship(): void
    {
        $muslim = Muslim::factory()->create();
        $other = Muslim::factory()->create();

        $row = MuslimRelationship::create([
            'muslim_id' => $muslim->id,
            'related_muslim_id' => $other->id,
            'relationship_type' => RelationshipType::Cousin,
        ]);

        $this->actingAs($this->admin())
            ->put(route('admin.genealogy.relationships.update', [$muslim, $row]), [
                'related_muslim_id' => $other->id,
                'relationship_type' => 'sibling',
                'notes' => 'صححنا النوع',
                'hide_from_tree' => '1',
            ])
            ->assertRedirect(route('admin.genealogy.show', $muslim));

        $row = $row->fresh();
        $this->assertSame(RelationshipType::Sibling, $row->relationship_type);
        $this->assertSame('صححنا النوع', $row->notes);
        $this->assertTrue((bool) $row->hide_from_tree);
    }

    /**
     * Changing the type re-normalises pair order — otherwise a row stored as
     * (higher, lower) as an uncle edge would stay that way as a sibling edge,
     * and the unique index would stop catching the mirror duplicate.
     */
    public function test_changing_the_type_renormalises_the_stored_pair_order(): void
    {
        $lower = Muslim::factory()->create();
        $higher = Muslim::factory()->create();

        // Stored high-then-low, which is legal for the directional uncle type.
        $row = MuslimRelationship::create([
            'muslim_id' => $higher->id,
            'related_muslim_id' => $lower->id,
            'relationship_type' => RelationshipType::Uncle,
        ]);

        $this->actingAs($this->admin())
            ->put(route('admin.genealogy.relationships.update', [$higher, $row]), [
                'related_muslim_id' => $lower->id,
                'relationship_type' => 'sibling',
            ])
            ->assertRedirect();

        $row = $row->fresh();
        $this->assertSame($lower->id, $row->muslim_id);
        $this->assertSame($higher->id, $row->related_muslim_id);
    }

    public function test_an_admin_can_delete_a_relationship(): void
    {
        $muslim = Muslim::factory()->create();
        $other = Muslim::factory()->create();

        $row = MuslimRelationship::create([
            'muslim_id' => $muslim->id,
            'related_muslim_id' => $other->id,
            'relationship_type' => RelationshipType::Sibling,
        ]);

        $this->actingAs($this->admin())
            ->delete(route('admin.genealogy.relationships.destroy', [$muslim, $row]))
            ->assertRedirect(route('admin.genealogy.show', $muslim));

        $this->assertDatabaseCount('muslim_relationships', 0);
    }

    /** Either column may be the known side, so both must be accepted. */
    public function test_a_relationship_can_be_deleted_from_the_far_side_of_the_row(): void
    {
        $a = Muslim::factory()->create();
        $b = Muslim::factory()->create();

        $row = MuslimRelationship::create([
            'muslim_id' => $a->id,
            'related_muslim_id' => $b->id,
            'relationship_type' => RelationshipType::Sibling,
        ]);

        $this->actingAs($this->admin())
            ->delete(route('admin.genealogy.relationships.destroy', [$b, $row]))
            ->assertRedirect();

        $this->assertDatabaseCount('muslim_relationships', 0);
    }

    public function test_a_relationship_reached_through_an_unrelated_person_is_not_found(): void
    {
        $a = Muslim::factory()->create();
        $b = Muslim::factory()->create();
        $bystander = Muslim::factory()->create();

        $row = MuslimRelationship::create([
            'muslim_id' => $a->id,
            'related_muslim_id' => $b->id,
            'relationship_type' => RelationshipType::Sibling,
        ]);

        $this->actingAs($this->admin())
            ->delete(route('admin.genealogy.relationships.destroy', [$bystander, $row]))
            ->assertNotFound();

        $this->assertDatabaseCount('muslim_relationships', 1);
    }

    // ------------------------------------------------------------------
    // Person page
    // ------------------------------------------------------------------

    public function test_the_person_page_shows_parents_children_and_relations(): void
    {
        $father = Muslim::factory()->create(['name_ar' => 'الوالد']);
        $muslim = Muslim::factory()->create(['father_id' => $father->id]);
        Muslim::factory()->create(['name_ar' => 'الولد', 'father_id' => $muslim->id]);
        $sibling = Muslim::factory()->create(['name_ar' => 'الأخ']);

        MuslimRelationship::create([
            'muslim_id' => $muslim->id,
            'related_muslim_id' => $sibling->id,
            'relationship_type' => RelationshipType::Sibling,
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.genealogy.show', $muslim))
            ->assertOk()
            ->assertSee('الوالد', false)
            ->assertSee('الولد', false)
            ->assertSee('الأخ', false);
    }

    /**
     * The tree must root at the Prophet ﷺ, resolved through the `prophet` role
     * rather than a slug, so renaming the record cannot silently re-root it.
     */
    public function test_the_explorer_roots_at_whoever_carries_the_prophet_role(): void
    {
        $prophetRole = IslamicRole::factory()->create([
            'slug' => 'prophet', 'name_ar' => 'نبي', 'name_en' => 'Prophet',
        ]);

        // A person with more recorded children, which the old fallback would
        // have picked instead.
        $busy = Muslim::factory()->create(['slug' => 'busy', 'name_ar' => 'كثير الأبناء']);
        Muslim::factory()->count(3)->create(['father_id' => $busy->id]);

        $prophet = Muslim::factory()->create(['slug' => 'the-prophet', 'name_ar' => 'محمد ﷺ']);
        $prophet->roles()->attach($prophetRole);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.genealogy.index'))
            ->assertOk();

        $this->assertSame($prophet->id, $response->original->getData()['root']->id);
    }

    /** An uncle row reads as nephew from the other end; the page must say so. */
    public function test_the_person_page_labels_a_reversed_row_from_the_reading_side(): void
    {
        $uncle = Muslim::factory()->create();
        $nephew = Muslim::factory()->create();

        MuslimRelationship::create([
            'muslim_id' => $uncle->id,
            'related_muslim_id' => $nephew->id,
            'relationship_type' => RelationshipType::Uncle,
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.genealogy.show', $nephew))
            ->assertOk()
            ->assertSee(RelationshipType::Uncle->reverseLabel('ar'), false);
    }
}
