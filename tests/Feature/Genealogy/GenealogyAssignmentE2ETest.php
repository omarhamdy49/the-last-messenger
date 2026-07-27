<?php

declare(strict_types=1);

namespace Tests\Feature\Genealogy;

use App\Domain\Genealogy\Models\Person;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * End-to-end coverage for assigning/updating a person's father/mother
 * through the admin Genealogy screen, and verifying the assignment reflects
 * correctly on the public API and the public family-tree page.
 *
 * Also guards the self-parent and cycle-prevention validation added
 * alongside these tests: without it, PersonRepository::getDescendantsRecursive()
 * (which the public genealogy tree page calls) would recurse forever on a
 * corrupted tree.
 */
class GenealogyAssignmentE2ETest extends TestCase
{
    use RefreshDatabase;

    private function makePerson(string $slug, array $translations, array $attributes = []): Person
    {
        $person = Person::create(array_merge([
            'slug' => $slug,
            'gender' => 'male',
            'person_type' => 'family',
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

    public function test_admin_can_assign_father_and_mother(): void
    {
        $this->actingAs($this->createAdminUser());

        $father = $this->makePerson('father-x', ['ar' => 'الأب', 'en' => 'Father X'], ['gender' => 'male']);
        $mother = $this->makePerson('mother-x', ['ar' => 'الأم', 'en' => 'Mother X'], ['gender' => 'female']);
        $child = $this->makePerson('child-x', ['ar' => 'الابن', 'en' => 'Child X']);

        $response = $this->put(route('admin.genealogy.update', $child->id), $this->updatePayload($child, [
            'father_id' => $father->id,
            'mother_id' => $mother->id,
        ]));

        $response->assertRedirect(route('admin.genealogy.index'));

        $child->refresh();
        $this->assertSame($father->id, $child->father_id);
        $this->assertSame($mother->id, $child->mother_id);
    }

    public function test_admin_can_change_an_existing_father_assignment(): void
    {
        $this->actingAs($this->createAdminUser());

        $oldFather = $this->makePerson('old-father', ['ar' => 'الأب القديم', 'en' => 'Old Father']);
        $newFather = $this->makePerson('new-father', ['ar' => 'الأب الجديد', 'en' => 'New Father']);
        $child = $this->makePerson('reassigned-child', ['ar' => 'الابن', 'en' => 'Child'], ['father_id' => $oldFather->id]);

        $response = $this->put(route('admin.genealogy.update', $child->id), $this->updatePayload($child, [
            'father_id' => $newFather->id,
        ]));

        $response->assertRedirect(route('admin.genealogy.index'));
        $this->assertSame($newFather->id, $child->fresh()->father_id);
    }

    public function test_self_parent_assignment_is_rejected(): void
    {
        $this->actingAs($this->createAdminUser());

        $person = $this->makePerson('self-ref', ['ar' => 'شخص', 'en' => 'Person']);

        $response = $this->put(route('admin.genealogy.update', $person->id), $this->updatePayload($person, [
            'father_id' => $person->id,
        ]));

        $response->assertSessionHasErrors('father_id');
        $this->assertNull($person->fresh()->father_id);
    }

    public function test_assigning_a_descendant_as_parent_is_rejected_as_a_cycle(): void
    {
        $this->actingAs($this->createAdminUser());

        $grandparent = $this->makePerson('gp', ['ar' => 'الجد', 'en' => 'Grandparent']);
        $parent = $this->makePerson('p', ['ar' => 'الأب', 'en' => 'Parent'], ['father_id' => $grandparent->id]);
        $child = $this->makePerson('c', ['ar' => 'الابن', 'en' => 'Child'], ['father_id' => $parent->id]);

        // grandparent -> parent -> child already exists. Assigning the
        // grandchild as the grandparent's father would close the cycle.
        $response = $this->put(route('admin.genealogy.update', $grandparent->id), $this->updatePayload($grandparent, [
            'father_id' => $child->id,
        ]));

        $response->assertSessionHasErrors('father_id');
        $this->assertNull($grandparent->fresh()->father_id);
    }

    public function test_direct_child_cannot_be_assigned_as_parent(): void
    {
        $this->actingAs($this->createAdminUser());

        $parent = $this->makePerson('direct-parent', ['ar' => 'الأب', 'en' => 'Parent']);
        $child = $this->makePerson('direct-child', ['ar' => 'الابن', 'en' => 'Child'], ['father_id' => $parent->id]);

        $response = $this->put(route('admin.genealogy.update', $parent->id), $this->updatePayload($parent, [
            'mother_id' => $child->id,
        ]));

        $response->assertSessionHasErrors('mother_id');
    }

    public function test_public_api_reflects_the_assigned_parents(): void
    {
        $father = $this->makePerson('api-father', ['ar' => 'الأب', 'en' => 'Api Father']);
        $child = $this->makePerson('api-child', ['ar' => 'الابن', 'en' => 'Api Child'], [
            'father_id' => $father->id,
            'is_sahabi' => true,
        ]);

        $response = $this->getJson("/api/v1/sahaba/{$child->slug}");

        $response->assertOk();
        $response->assertJsonPath('data.father.id', $father->id);
        $response->assertJsonPath('data.father.slug', $father->slug);
    }

    public function test_public_genealogy_tree_page_renders_the_assigned_child(): void
    {
        // The app default locale is 'ar', so the public page renders the
        // Arabic translation — assert against that, not the English one.
        $root = $this->makePerson('tree-root', ['ar' => 'الجد الأكبر', 'en' => 'Root Ancestor'], ['person_type' => 'prophet']);
        $child = $this->makePerson('tree-child', ['ar' => 'الحفيد المُعيَّن', 'en' => 'Tree Descendant'], ['father_id' => $root->id]);

        $response = $this->get(route('public.genealogy'));

        $response->assertOk();
        $response->assertSee('الحفيد المُعيَّن');
    }
}
