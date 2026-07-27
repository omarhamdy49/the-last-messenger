<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Sahaba\Models\Sahabi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bulk actions on the admin Sahaba data table: featured toggle and
 * publish/unpublish. Publish/unpublish writes `is_ready` on
 * sahabi_translations, which is per-locale — these guard that a bulk action
 * updates every locale row for the selected companions, not just one.
 */
class SahabiBulkActionsTest extends TestCase
{
    use RefreshDatabase;

    private function makeSahabi(string $slug, array $translations = ['ar' => 'صحابي', 'en' => 'Companion']): Sahabi
    {
        $sahabi = Sahabi::create([
            'slug' => $slug,
            'gender' => 'male',
            'is_sahabi' => true,
            'person_type' => 'sahabi',
            'is_featured' => false,
        ]);

        $sahabi->syncTranslations(['name' => $translations]);
        $sahabi->translations()->update(['is_ready' => false]);

        return $sahabi;
    }

    public function test_bulk_set_featured_marks_selected_companions(): void
    {
        $this->actingAs($this->createAdminUser());

        $a = $this->makeSahabi('a');
        $b = $this->makeSahabi('b');
        $untouched = $this->makeSahabi('c');

        $response = $this->post(route('admin.sahaba.bulk-featured'), [
            'ids' => [$a->id, $b->id],
            'is_featured' => true,
        ]);

        $response->assertRedirect(route('admin.sahaba.index'));
        $this->assertTrue($a->fresh()->is_featured);
        $this->assertTrue($b->fresh()->is_featured);
        $this->assertFalse($untouched->fresh()->is_featured);
    }

    public function test_bulk_publish_sets_is_ready_across_every_locale(): void
    {
        $this->actingAs($this->createAdminUser());

        $sahabi = $this->makeSahabi('multi-locale');
        $this->assertSame(2, $sahabi->translations()->count());
        $this->assertTrue($sahabi->translations()->where('is_ready', false)->exists());

        $response = $this->post(route('admin.sahaba.bulk-status'), [
            'ids' => [$sahabi->id],
            'status' => 'published',
        ]);

        $response->assertRedirect(route('admin.sahaba.index'));
        $this->assertSame(0, $sahabi->translations()->where('is_ready', false)->count());
        $this->assertSame(2, $sahabi->translations()->where('is_ready', true)->count());
    }

    public function test_bulk_unpublish_clears_is_ready_across_every_locale(): void
    {
        $this->actingAs($this->createAdminUser());

        $sahabi = $this->makeSahabi('to-unpublish');
        $sahabi->translations()->update(['is_ready' => true]);

        $response = $this->post(route('admin.sahaba.bulk-status'), [
            'ids' => [$sahabi->id],
            'status' => 'unpublished',
        ]);

        $response->assertRedirect(route('admin.sahaba.index'));
        $this->assertSame(0, $sahabi->translations()->where('is_ready', true)->count());
    }

    public function test_bulk_status_rejects_invalid_status_value(): void
    {
        $this->actingAs($this->createAdminUser());

        $sahabi = $this->makeSahabi('invalid-status');

        $response = $this->post(route('admin.sahaba.bulk-status'), [
            'ids' => [$sahabi->id],
            'status' => 'archived',
        ]);

        $response->assertSessionHasErrors('status');
    }

    public function test_guest_cannot_bulk_update_featured(): void
    {
        $sahabi = $this->makeSahabi('guest-blocked');

        $response = $this->post(route('admin.sahaba.bulk-featured'), [
            'ids' => [$sahabi->id],
            'is_featured' => true,
        ]);

        $response->assertRedirect(route('login'));
        $this->assertFalse($sahabi->fresh()->is_featured);
    }
}
