<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Shared\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Deleting a place that content still points at must be refused, and the refusal
 * has to carry a number the editor can act on — "31 events" tells them what to
 * reassign; a bare "cannot delete" does not.
 *
 * The count comes from LocationUsageRegistry, not from relations on the model:
 * Shared may not import a domain module (constitution IV).
 */
class LocationDeleteGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_unreferenced_place_can_be_deleted(): void
    {
        $this->actingAs($this->createAdminUser());
        $location = Location::factory()->create();

        $this->delete(route('admin.locations.destroy', $location->id))
            ->assertRedirect(route('admin.locations.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('locations', ['id' => $location->id]);
    }

    public function test_a_referenced_place_cannot_be_deleted(): void
    {
        $this->actingAs($this->createAdminUser());
        $location = Location::factory()->create();
        SeerahEvent::factory()->count(3)->create(['location_id' => $location->id]);

        $this->delete(route('admin.locations.destroy', $location->id))
            ->assertRedirect(route('admin.locations.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('locations', ['id' => $location->id]);
    }

    public function test_the_refusal_names_how_many_records_block_it(): void
    {
        $this->actingAs($this->createAdminUser());
        $location = Location::factory()->create();
        SeerahEvent::factory()->count(3)->create(['location_id' => $location->id]);

        $response = $this->delete(route('admin.locations.destroy', $location->id));

        $this->assertStringContainsString('3', (string) session('error'));
    }

    public function test_a_json_client_gets_a_422_rather_than_a_redirect(): void
    {
        $this->actingAs($this->createAdminUser());
        $location = Location::factory()->create();
        SeerahEvent::factory()->create(['location_id' => $location->id]);

        $this->deleteJson(route('admin.locations.destroy', $location->id))
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['message', 'errors' => ['location']]);
    }

    public function test_deleting_an_unknown_place_returns_404(): void
    {
        $this->actingAs($this->createAdminUser());

        $this->delete(route('admin.locations.destroy', 999999))->assertNotFound();
    }

    public function test_delete_requires_authentication(): void
    {
        $location = Location::factory()->create();

        $this->delete(route('admin.locations.destroy', $location->id))->assertRedirect();

        $this->assertDatabaseHas('locations', ['id' => $location->id]);
    }

    public function test_delete_is_refused_for_a_non_admin(): void
    {
        $this->actingAs($this->createWriterUser());
        $location = Location::factory()->create();

        $this->delete(route('admin.locations.destroy', $location->id))->assertRedirect();

        $this->assertDatabaseHas('locations', ['id' => $location->id]);
    }

    public function test_bulk_delete_skips_referenced_places_and_reports_the_count(): void
    {
        $this->actingAs($this->createAdminUser());

        $free = Location::factory()->create(['slug' => 'free']);
        $used = Location::factory()->create(['slug' => 'used']);
        SeerahEvent::factory()->create(['location_id' => $used->id]);

        $this->post(route('admin.locations.bulk-delete'), ['ids' => [$free->id, $used->id]])
            ->assertRedirect(route('admin.locations.index'));

        // Constitution X: the skipped row is reported, never silently dropped.
        $this->assertDatabaseMissing('locations', ['id' => $free->id]);
        $this->assertDatabaseHas('locations', ['id' => $used->id]);
        $this->assertStringContainsString('1', (string) session('success'));
    }

    public function test_a_place_referenced_only_by_a_surah_is_also_protected(): void
    {
        $this->actingAs($this->createAdminUser());
        $location = Location::factory()->create();

        DB::table('quran_chapters')->insert([
            'id' => 1, 'revelation_place' => 'makkah', 'revelation_location_id' => $location->id,
            'revelation_order' => 5, 'name_arabic' => 'الفاتحة', 'name_simple' => 'Al-Fatihah',
            'name_complex' => 'Al-Fatihah', 'verses_count' => 7, 'first_page' => 1, 'last_page' => 1,
            'bismillah_pre' => true,
        ]);

        $this->delete(route('admin.locations.destroy', $location->id))->assertSessionHas('error');

        $this->assertDatabaseHas('locations', ['id' => $location->id]);
    }
}
