<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Seerah\Models\SeerahEventType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeerahEventTypeAndBulkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_seed_migration_populates_event_types(): void
    {
        // The 2026_06_18 seed migration backfills the 11 canonical types.
        $this->assertSame(11, SeerahEventType::query()->count());
        $this->assertDatabaseHas('seerah_event_types', ['slug' => 'battle']);
        $this->assertDatabaseMissing('seerah_events', ['id' => 0]);
    }

    public function test_admin_can_bulk_update_status_and_bulk_delete_events(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        $events = SeerahEvent::factory()->count(3)->create(['status' => 'draft']);
        $ids = $events->pluck('id')->all();

        // Bulk publish
        $this->post(route('admin.seerah-events.bulk-status'), ['ids' => $ids, 'status' => 'published'])
            ->assertRedirect(route('admin.seerah-events.index'));

        foreach ($ids as $id) {
            $this->assertDatabaseHas('seerah_events', ['id' => $id, 'status' => 'published']);
        }

        // Invalid status is rejected (422 -> redirect back with errors on web)
        $this->from(route('admin.seerah-events.index'))
            ->post(route('admin.seerah-events.bulk-status'), ['ids' => $ids, 'status' => 'archived'])
            ->assertSessionHasErrors('status');

        // Bulk delete — post string ids to mirror real browser form submission
        // (guards the strict_types int-cast in the controller).
        $this->post(route('admin.seerah-events.bulk-delete'), ['ids' => array_map('strval', $ids)])
            ->assertRedirect(route('admin.seerah-events.index'));

        foreach ($ids as $id) {
            $this->assertDatabaseMissing('seerah_events', ['id' => $id]);
        }
    }

    public function test_admin_can_crud_and_toggle_event_types(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        // Create
        $this->post(route('admin.seerah-event-types.store'), [
            'slug' => 'expedition',
            'name' => ['ar' => 'سرية', 'en' => 'Expedition'],
            'icon' => 'flag',
            'color' => 'red',
            'gradient' => 'from-red-500 to-orange-500',
            'is_active' => '1',
            'display_order' => 12,
        ])->assertRedirect(route('admin.seerah-event-types.index'));

        $type = SeerahEventType::query()->where('slug', 'expedition')->firstOrFail();
        $this->assertSame('سرية', $type->getTranslation('name', 'ar'));
        $this->assertTrue($type->is_active);

        // Update (keep it active)
        $this->put(route('admin.seerah-event-types.update', $type->id), [
            'slug' => 'expedition',
            'name' => ['ar' => 'سرية عسكرية', 'en' => 'Military Expedition'],
            'display_order' => 5,
            'is_active' => '1',
        ])->assertRedirect(route('admin.seerah-event-types.index'));

        $type->refresh();
        $this->assertSame('سرية عسكرية', $type->getTranslation('name', 'ar'));
        $this->assertSame(5, $type->display_order);

        // Toggle active
        $this->post(route('admin.seerah-event-types.toggle-active', $type->id))
            ->assertRedirect(route('admin.seerah-event-types.index'));
        $this->assertFalse($type->fresh()->is_active);

        // Show
        $this->get(route('admin.seerah-event-types.show', $type->id))->assertOk()->assertSee('سرية عسكرية');

        // Delete (no events linked)
        $this->delete(route('admin.seerah-event-types.destroy', $type->id))
            ->assertRedirect(route('admin.seerah-event-types.index'));
        $this->assertDatabaseMissing('seerah_event_types', ['id' => $type->id]);
    }

    public function test_event_type_in_use_cannot_be_deleted(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        $type = SeerahEventType::query()->where('slug', 'battle')->firstOrFail();
        SeerahEvent::factory()->create(['seerah_event_type_id' => $type->id]);

        $this->delete(route('admin.seerah-event-types.destroy', $type->id))
            ->assertRedirect(route('admin.seerah-event-types.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('seerah_event_types', ['id' => $type->id]);
    }

    public function test_event_type_store_requires_arabic_name(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        $this->from(route('admin.seerah-event-types.create'))
            ->post(route('admin.seerah-event-types.store'), [
                'slug' => 'no-name',
                'name' => ['en' => 'No Arabic'],
            ])
            ->assertSessionHasErrors('name.ar');

        $this->assertDatabaseMissing('seerah_event_types', ['slug' => 'no-name']);
    }

    public function test_non_admin_cannot_create_event_type(): void
    {
        $writer = $this->createWriterUser();
        $this->actingAs($writer);

        // The admin portal gate redirects non-admins before the form request runs.
        $response = $this->post(route('admin.seerah-event-types.store'), [
            'slug' => 'forbidden',
            'name' => ['ar' => 'ممنوع'],
        ]);

        $this->assertContains($response->getStatusCode(), [302, 403]);
        $this->assertDatabaseMissing('seerah_event_types', ['slug' => 'forbidden']);
    }
}
