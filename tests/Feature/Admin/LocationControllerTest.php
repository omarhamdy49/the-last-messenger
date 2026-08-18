<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Shared\Enums\LocationType;
use App\Domain\Shared\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'slug' => 'makkah',
            'type' => LocationType::CITY->value,
            'parent_id' => null,
            'name' => ['ar' => 'مكة المكرمة', 'en' => 'Makkah'],
            'description' => ['ar' => null],
            'latitude' => '21.3891',
            'longitude' => '39.8579',
            'aliases' => ['مكة', 'Mecca'],
            'is_active' => true,
            'display_order' => 0,
        ], $overrides);
    }

    // ------------------------------------------------------------ read paths

    public function test_index_requires_authentication(): void
    {
        $this->get(route('admin.locations.index'))->assertRedirect();
    }

    public function test_index_is_refused_for_a_non_admin(): void
    {
        // The admin guard redirects with a flash message rather than returning
        // 403 — that is this application's convention, asserted here as-is so a
        // future change to it is caught rather than silently accepted.
        $this->actingAs($this->createWriterUser());

        $this->get(route('admin.locations.index'))
            ->assertRedirect()
            ->assertSessionHasErrors();
    }

    public function test_an_admin_can_list_locations(): void
    {
        $this->actingAs($this->createAdminUser());
        Location::factory()->withNames(['ar' => 'المدينة المنورة'])->create(['slug' => 'al-madinah']);

        $this->get(route('admin.locations.index'))
            ->assertOk()
            ->assertSee('المدينة المنورة', false);
    }

    public function test_show_returns_404_for_an_unknown_id(): void
    {
        $this->actingAs($this->createAdminUser());

        $this->get(route('admin.locations.show', 999999))->assertNotFound();
    }

    public function test_edit_returns_404_for_an_unknown_id(): void
    {
        $this->actingAs($this->createAdminUser());

        $this->get(route('admin.locations.edit', 999999))->assertNotFound();
    }

    // ----------------------------------------------------------- write paths

    public function test_store_requires_authentication(): void
    {
        $this->post(route('admin.locations.store'), $this->validPayload())->assertRedirect();
    }

    public function test_store_is_refused_for_a_non_admin(): void
    {
        $this->actingAs($this->createWriterUser());

        $this->post(route('admin.locations.store'), $this->validPayload())
            ->assertRedirect()
            ->assertSessionHasErrors();

        $this->assertDatabaseMissing('locations', ['slug' => 'makkah']);
    }

    public function test_an_admin_can_create_a_location_with_all_five_names(): void
    {
        $this->actingAs($this->createAdminUser());

        $this->post(route('admin.locations.store'), $this->validPayload([
            'name' => [
                'ar' => 'مكة المكرمة', 'en' => 'Makkah',
                'fr' => 'La Mecque', 'tr' => 'Mekke', 'ur' => 'مکہ مکرمہ',
            ],
        ]))->assertRedirect(route('admin.locations.index'));

        $location = Location::query()->where('slug', 'makkah')->firstOrFail();

        $this->assertSame(LocationType::CITY, $location->type);
        $this->assertSame(['مكة', 'Mecca'], $location->aliasList());
        $this->assertSame(5, $location->translations()->count());
        $this->assertSame('مكة المكرمة', $location->name('ar'));
        $this->assertSame('Mekke', $location->name('tr'));
    }

    public function test_a_created_location_is_nested_under_its_parent(): void
    {
        $this->actingAs($this->createAdminUser());
        $parent = Location::factory()->create(['slug' => 'hijaz']);

        $this->post(route('admin.locations.store'), $this->validPayload(['parent_id' => $parent->id]))
            ->assertRedirect();

        $location = Location::query()->where('slug', 'makkah')->firstOrFail();

        $this->assertSame($parent->id, $location->parent_id);
        $this->assertSame(1, $location->depth);
    }

    public function test_store_rejects_a_missing_arabic_name(): void
    {
        $this->actingAs($this->createAdminUser());

        $this->post(route('admin.locations.store'), $this->validPayload(['name' => ['en' => 'Makkah']]))
            ->assertSessionHasErrors('name.ar');
    }

    public function test_store_rejects_a_duplicate_slug(): void
    {
        $this->actingAs($this->createAdminUser());
        Location::factory()->create(['slug' => 'makkah']);

        $this->post(route('admin.locations.store'), $this->validPayload())
            ->assertSessionHasErrors('slug');
    }

    public function test_store_rejects_a_malformed_slug(): void
    {
        $this->actingAs($this->createAdminUser());

        $this->post(route('admin.locations.store'), $this->validPayload(['slug' => 'Makkah Al Mukarramah']))
            ->assertSessionHasErrors('slug');
    }

    public function test_store_rejects_an_out_of_range_coordinate(): void
    {
        $this->actingAs($this->createAdminUser());

        $this->post(route('admin.locations.store'), $this->validPayload(['latitude' => '120']))
            ->assertSessionHasErrors('latitude');
    }

    public function test_store_rejects_an_alias_already_owned_by_another_place(): void
    {
        $this->actingAs($this->createAdminUser());
        Location::factory()->withAliases(['Mecca'])->create(['slug' => 'other']);

        $this->post(route('admin.locations.store'), $this->validPayload())
            ->assertSessionHasErrors('aliases');

        $this->assertDatabaseMissing('locations', ['slug' => 'makkah']);
    }

    public function test_an_admin_can_update_a_location(): void
    {
        $this->actingAs($this->createAdminUser());
        $location = Location::factory()->withNames(['ar' => 'قديم'])->create(['slug' => 'makkah']);

        $this->put(route('admin.locations.update', $location->id), $this->validPayload([
            'name' => ['ar' => 'مكة المكرمة'],
        ]))->assertRedirect(route('admin.locations.index'));

        $this->assertSame('مكة المكرمة', $location->fresh()->name('ar'));
    }

    public function test_update_allows_a_place_to_keep_its_own_slug(): void
    {
        $this->actingAs($this->createAdminUser());
        $location = Location::factory()->create(['slug' => 'makkah']);

        $this->put(route('admin.locations.update', $location->id), $this->validPayload())
            ->assertSessionHasNoErrors();
    }

    public function test_update_rejects_a_place_as_its_own_parent(): void
    {
        $this->actingAs($this->createAdminUser());
        $location = Location::factory()->create(['slug' => 'makkah']);

        $this->put(route('admin.locations.update', $location->id), $this->validPayload(['parent_id' => $location->id]))
            ->assertSessionHasErrors('parent_id');
    }

    public function test_update_rejects_a_parent_that_is_its_own_descendant(): void
    {
        $this->actingAs($this->createAdminUser());
        $parent = Location::factory()->create(['slug' => 'hijaz']);
        $child = Location::factory()->create(['slug' => 'makkah', 'parent_id' => $parent->id]);

        // Making the ancestor a child of its own descendant closes the loop.
        $this->put(route('admin.locations.update', $parent->id), $this->validPayload([
            'slug' => 'hijaz',
            'parent_id' => $child->id,
        ]))->assertSessionHasErrors('parent_id');

        $this->assertNull($parent->fresh()->parent_id);
    }

    public function test_update_returns_404_for_an_unknown_id(): void
    {
        $this->actingAs($this->createAdminUser());

        $this->put(route('admin.locations.update', 999999), $this->validPayload())->assertNotFound();
    }

    public function test_toggle_active_flips_the_flag(): void
    {
        $this->actingAs($this->createAdminUser());
        $location = Location::factory()->create(['is_active' => true]);

        $this->post(route('admin.locations.toggle-active', $location->id))->assertRedirect();

        $this->assertFalse($location->fresh()->is_active);
    }
}
