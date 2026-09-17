<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Seerah\Models\SeerahEventType;
use Database\Seeders\SeerahEventTypeSeeder;
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

    public function test_seeder_populates_event_types_idempotently(): void
    {
        $this->seed(SeerahEventTypeSeeder::class);
        $this->seed(SeerahEventTypeSeeder::class);

        $types = SeerahEventType::query()->get();

        $this->assertCount(11, $types);
        $this->assertDatabaseHas('seerah_event_types', ['slug' => 'battle']);

        foreach ($types as $type) {
            $this->assertNotEmpty($type->locale['ar'] ?? null, "{$type->slug} has no Arabic name");
            $this->assertNotEmpty($type->metadata['icon'] ?? null, "{$type->slug} has no icon");
        }
    }

    public function test_admin_can_bulk_update_status_and_bulk_delete_events(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        $events = SeerahEvent::factory()->count(3)->draft()->create();
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
            'locale' => ['ar' => 'سرية', 'en' => 'Expedition'],
            'metadata' => ['icon' => 'flag', 'color' => 'red', 'gradient' => 'from-red-500 to-orange-500'],
            'is_active' => '1',
        ])->assertRedirect(route('admin.seerah-event-types.index'));

        $type = SeerahEventType::query()->where('slug', 'expedition')->firstOrFail();
        $this->assertSame('سرية', $type->name('ar'));
        $this->assertSame('Expedition', $type->getTranslation('name', 'en'));
        $this->assertSame('flag', $type->metadata['icon']);
        $this->assertTrue($type->is_active);

        // Update (keep it active)
        $this->put(route('admin.seerah-event-types.update', $type->id), [
            'slug' => 'expedition',
            'locale' => ['ar' => 'سرية عسكرية', 'en' => 'Military Expedition'],
            'metadata' => ['icon' => 'swords'],
            'is_active' => '1',
        ])->assertRedirect(route('admin.seerah-event-types.index'));

        $type->refresh();
        $this->assertSame('سرية عسكرية', $type->name('ar'));
        $this->assertSame('swords', $type->metadata['icon']);

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

        $this->seed(SeerahEventTypeSeeder::class);
        $type = SeerahEventType::query()->where('slug', 'battle')->firstOrFail();
        SeerahEvent::factory()->create(['event_type' => $type->id]);

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
                'locale' => ['en' => 'No Arabic'],
            ])
            ->assertSessionHasErrors('locale.ar');

        $this->assertDatabaseMissing('seerah_event_types', ['slug' => 'no-name']);
    }

    public function test_event_type_store_rejects_removed_fields(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        foreach (['name' => ['ar' => 'قديم'], 'display_order' => 3] as $field => $value) {
            $this->from(route('admin.seerah-event-types.create'))
                ->post(route('admin.seerah-event-types.store'), [
                    'slug' => 'legacy',
                    'locale' => ['ar' => 'قديم'],
                    $field => $value,
                ])
                ->assertSessionHasErrors($field);
        }

        $this->assertDatabaseMissing('seerah_event_types', ['slug' => 'legacy']);
    }

    public function test_non_admin_cannot_create_event_type(): void
    {
        $writer = $this->createWriterUser();
        $this->actingAs($writer);

        // The admin portal gate redirects non-admins before the form request runs.
        $response = $this->post(route('admin.seerah-event-types.store'), [
            'slug' => 'forbidden',
            'locale' => ['ar' => 'ممنوع'],
        ]);

        $this->assertContains($response->getStatusCode(), [302, 403]);
        $this->assertDatabaseMissing('seerah_event_types', ['slug' => 'forbidden']);
    }
}
