<?php

declare(strict_types=1);

namespace Tests\Feature\Genealogy;

use App\Domain\Genealogy\Enums\RelationshipType;
use App\Domain\Genealogy\Models\Person;
use App\Domain\Genealogy\Models\Relationship;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Admin curation of what appears in the public family tree: per-person
 * tree_display_order/hide_from_tree (descendants), and per-relationship
 * display_order/hide_from_tree (spouses) — see PersonRepository's
 * getDescendantsRecursive()/getSpouses() for how these are consumed.
 */
class GenealogyTreeCurationE2ETest extends TestCase
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

    public function test_admin_can_set_tree_display_order_and_hide_from_tree_on_a_person(): void
    {
        $this->actingAs($this->createAdminUser());

        $person = $this->makePerson('curated-person', ['ar' => 'شخص', 'en' => 'Person']);

        $response = $this->put(route('admin.genealogy.update', $person->id), $this->updatePayload($person, [
            'tree_display_order' => 3,
            'hide_from_tree' => '1',
        ]));

        $response->assertRedirect(route('admin.genealogy.index'));

        $person->refresh();
        $this->assertSame(3, $person->tree_display_order);
        $this->assertTrue($person->hide_from_tree);
    }

    public function test_tree_display_order_rejects_out_of_range_values(): void
    {
        $this->actingAs($this->createAdminUser());

        $person = $this->makePerson('bad-order-person', ['ar' => 'شخص', 'en' => 'Person']);

        $response = $this->put(route('admin.genealogy.update', $person->id), $this->updatePayload($person, [
            'tree_display_order' => -1,
        ]));

        $response->assertSessionHasErrors('tree_display_order');
    }

    public function test_guest_is_redirected_to_login_when_adding_a_relationship(): void
    {
        $person = $this->makePerson('guest-target', ['ar' => 'شخص', 'en' => 'Person']);
        $spouse = $this->makePerson('guest-spouse', ['ar' => 'زوجة', 'en' => 'Spouse'], ['gender' => 'female']);

        $response = $this->post(route('admin.genealogy.relationships.store', $person->id), [
            'spouse_id' => $spouse->id,
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseCount('relationships', 0);
    }

    public function test_admin_can_add_a_spouse_relationship(): void
    {
        $this->actingAs($this->createAdminUser());

        $person = $this->makePerson('husband', ['ar' => 'زوج', 'en' => 'Husband']);
        $spouse = $this->makePerson('wife', ['ar' => 'زوجة', 'en' => 'Wife'], ['gender' => 'female']);

        $response = $this->post(route('admin.genealogy.relationships.store', $person->id), [
            'spouse_id' => $spouse->id,
        ]);

        $response->assertRedirect(route('admin.genealogy.edit', $person->id));
        $this->assertDatabaseHas('relationships', [
            'person_id' => $person->id,
            'related_person_id' => $spouse->id,
            'relationship_type' => RelationshipType::SPOUSE->value,
        ]);
    }

    public function test_adding_self_as_spouse_is_rejected(): void
    {
        $this->actingAs($this->createAdminUser());

        $person = $this->makePerson('self-spouse', ['ar' => 'شخص', 'en' => 'Person']);

        $response = $this->post(route('admin.genealogy.relationships.store', $person->id), [
            'spouse_id' => $person->id,
        ]);

        $response->assertSessionHasErrors('spouse_id');
        $this->assertDatabaseCount('relationships', 0);
    }

    public function test_adding_a_duplicate_spouse_relationship_is_rejected(): void
    {
        $this->actingAs($this->createAdminUser());

        $person = $this->makePerson('husband-dup', ['ar' => 'زوج', 'en' => 'Husband']);
        $spouse = $this->makePerson('wife-dup', ['ar' => 'زوجة', 'en' => 'Wife'], ['gender' => 'female']);

        Relationship::create([
            'person_id' => $spouse->id,
            'related_person_id' => $person->id,
            'relationship_type' => RelationshipType::SPOUSE,
        ]);

        // Reverse direction from the existing row — must still be caught.
        $response = $this->post(route('admin.genealogy.relationships.store', $person->id), [
            'spouse_id' => $spouse->id,
        ]);

        $response->assertSessionHasErrors('spouse_id');
        $this->assertDatabaseCount('relationships', 1);
    }

    public function test_adding_a_relationship_for_a_nonexistent_spouse_is_rejected(): void
    {
        $this->actingAs($this->createAdminUser());

        $person = $this->makePerson('lonely-person', ['ar' => 'شخص', 'en' => 'Person']);

        $response = $this->post(route('admin.genealogy.relationships.store', $person->id), [
            'spouse_id' => 999999,
        ]);

        $response->assertSessionHasErrors('spouse_id');
    }

    public function test_admin_can_update_relationship_order_and_hide_flag(): void
    {
        $this->actingAs($this->createAdminUser());

        $person = $this->makePerson('order-husband', ['ar' => 'زوج', 'en' => 'Husband']);
        $spouse = $this->makePerson('order-wife', ['ar' => 'زوجة', 'en' => 'Wife'], ['gender' => 'female']);

        $relationship = Relationship::create([
            'person_id' => $person->id,
            'related_person_id' => $spouse->id,
            'relationship_type' => RelationshipType::SPOUSE,
        ]);

        $response = $this->put(route('admin.genealogy.relationships.update', $relationship->id), [
            'display_order' => 5,
            'hide_from_tree' => '1',
            'return_to' => $person->id,
        ]);

        $response->assertRedirect(route('admin.genealogy.edit', $person->id));

        $relationship->refresh();
        $this->assertSame(5, $relationship->display_order);
        $this->assertTrue($relationship->hide_from_tree);
    }

    public function test_updating_a_nonexistent_relationship_returns_404(): void
    {
        $this->actingAs($this->createAdminUser());

        $response = $this->put(route('admin.genealogy.relationships.update', 999999), [
            'display_order' => 1,
        ]);

        $response->assertNotFound();
    }

    public function test_admin_can_delete_a_relationship(): void
    {
        $this->actingAs($this->createAdminUser());

        $person = $this->makePerson('delete-husband', ['ar' => 'زوج', 'en' => 'Husband']);
        $spouse = $this->makePerson('delete-wife', ['ar' => 'زوجة', 'en' => 'Wife'], ['gender' => 'female']);

        $relationship = Relationship::create([
            'person_id' => $person->id,
            'related_person_id' => $spouse->id,
            'relationship_type' => RelationshipType::SPOUSE,
        ]);

        $response = $this->delete(route('admin.genealogy.relationships.destroy', $relationship->id), [
            'return_to' => $person->id,
        ]);

        $response->assertRedirect(route('admin.genealogy.edit', $person->id));
        $this->assertDatabaseMissing('relationships', ['id' => $relationship->id]);
    }

    public function test_deleting_a_nonexistent_relationship_returns_404(): void
    {
        $this->actingAs($this->createAdminUser());

        $response = $this->delete(route('admin.genealogy.relationships.destroy', 999999));

        $response->assertNotFound();
    }
}
