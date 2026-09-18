<?php

declare(strict_types=1);

namespace Tests\Feature\People;

use App\Domain\People\Models\IslamicRole;
use App\Domain\People\Models\Muslim;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The flags an editor sets from the admin, and the surfaces that read them.
 *
 * These exist because the flags were the failure mode: every one of them is a
 * column or a pivot column that decides public visibility, and the previous
 * admin either did not render the control at all (`sort_order`) or rendered it
 * only in one place (`is_featured`), so a person could be saved into a state
 * nothing on screen explained.
 */
class MuslimAdminFlagsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return $this->createAdminUser();
    }

    public function test_toggling_published_writes_the_posted_state_not_a_flip(): void
    {
        $muslim = Muslim::factory()->create(['is_published' => false]);

        $this->actingAs($this->admin())
            ->patch(route('admin.sahaba.toggle', [$muslim, 'published']), ['value' => '1'])
            ->assertRedirect();

        $this->assertTrue((bool) $muslim->fresh()->is_published);

        // Posting the same target again must be a no-op, not a toggle back:
        // that is what makes a double submit safe.
        $this->actingAs($this->admin())
            ->patch(route('admin.sahaba.toggle', [$muslim, 'published']), ['value' => '1']);

        $this->assertTrue((bool) $muslim->fresh()->is_published);
    }

    public function test_toggling_featured_writes_the_role_pivot(): void
    {
        $sahabi = IslamicRole::factory()->sahabi()->create();
        $muslim = Muslim::factory()->create();
        $muslim->roles()->attach($sahabi->id, ['is_featured' => false, 'sort_order' => 0]);

        $this->actingAs($this->admin())
            ->patch(route('admin.sahaba.toggle', [$muslim, 'featured']), ['value' => '1'])
            ->assertRedirect();

        $this->assertDatabaseHas('muslims_roles', [
            'muslim_id' => $muslim->id,
            'role_id' => $sahabi->id,
            'is_featured' => 1,
        ]);

        $this->actingAs($this->admin())
            ->patch(route('admin.sahaba.toggle', [$muslim, 'featured']), ['value' => '0']);

        $this->assertDatabaseHas('muslims_roles', [
            'muslim_id' => $muslim->id,
            'role_id' => $sahabi->id,
            'is_featured' => 0,
        ]);
    }

    public function test_toggling_tree_visibility_writes_hide_from_tree(): void
    {
        $muslim = Muslim::factory()->create(['hide_from_tree' => false]);

        $this->actingAs($this->admin())
            ->patch(route('admin.sahaba.toggle', [$muslim, 'tree']), ['value' => '1'])
            ->assertRedirect();

        $this->assertTrue((bool) $muslim->fresh()->hide_from_tree);
    }

    public function test_an_unknown_flag_is_not_routable(): void
    {
        $muslim = Muslim::factory()->create();

        // The route constrains {flag}, so a typo 404s rather than reaching the
        // controller's match() and throwing an unhandled UnhandledMatchError.
        $this->actingAs($this->admin())
            ->patch("/su/portal/v1/admin/sahaba/{$muslim->id}/toggle/deleted", ['value' => '1'])
            ->assertNotFound();
    }

    public function test_the_form_persists_per_role_sort_order(): void
    {
        $sahabi = IslamicRole::factory()->sahabi()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.sahaba.store'), [
                'slug' => 'ordered-companion',
                'name_ar' => 'صحابي',
                'roles' => [
                    ['role_id' => $sahabi->id, 'is_featured' => '1', 'sort_order' => '7'],
                ],
            ])
            ->assertRedirect();

        $muslim = Muslim::where('slug', 'ordered-companion')->firstOrFail();

        // Previously the request validated sort_order but no input rendered it,
        // so every assignment silently saved 0.
        $this->assertDatabaseHas('muslims_roles', [
            'muslim_id' => $muslim->id,
            'role_id' => $sahabi->id,
            'sort_order' => 7,
        ]);
    }

    public function test_the_status_filter_isolates_drafts(): void
    {
        // Names that cannot collide with the page's own chrome — "منشور" and
        // "مسودة" are the literal status labels the new toggles and stat cards
        // render, so assertDontSee() on them would fail for the wrong reason.
        $live = Muslim::factory()->create(['is_published' => true, 'name_ar' => 'زيد بن ثابت الأنصاري']);
        $draft = Muslim::factory()->create(['is_published' => false, 'name_ar' => 'قيس بن سعد العبادي']);

        $this->actingAs($this->admin())
            ->get(route('admin.sahaba.index', ['status' => 'draft']))
            ->assertOk()
            ->assertSee($draft->name_ar)
            ->assertDontSee($live->name_ar);

        $this->actingAs($this->admin())
            ->get(route('admin.sahaba.index', ['status' => 'published']))
            ->assertOk()
            ->assertSee($live->name_ar)
            ->assertDontSee($draft->name_ar);
    }

    public function test_the_index_counts_published_drafts_and_featured(): void
    {
        $sahabi = IslamicRole::factory()->sahabi()->create();

        $featured = Muslim::factory()->create(['is_published' => true]);
        $featured->roles()->attach($sahabi->id, ['is_featured' => true, 'sort_order' => 0]);

        Muslim::factory()->create(['is_published' => true]);
        Muslim::factory()->create(['is_published' => false]);

        $stats = $this->actingAs($this->admin())
            ->get(route('admin.sahaba.index'))
            ->assertOk()
            ->viewData('stats');

        $this->assertSame(3, $stats['total']);
        $this->assertSame(2, $stats['published']);
        $this->assertSame(1, $stats['draft']);
        $this->assertSame(1, $stats['featured']);
    }

    public function test_the_create_and_edit_screens_render_every_flag_control(): void
    {
        IslamicRole::factory()->sahabi()->create();
        $muslim = Muslim::factory()->create();

        foreach ([route('admin.sahaba.create'), route('admin.sahaba.edit', $muslim)] as $url) {
            $response = $this->actingAs($this->admin())->get($url)->assertOk();

            foreach (['field-published', 'field-hide-from-tree', 'field-tree-order', 'role-sort-sahabi', 'role-featured-sahabi'] as $testId) {
                $response->assertSee('data-testid="'.$testId.'"', false);
            }
        }
    }

    public function test_the_profile_page_offers_the_toggles_and_explains_what_is_hidden(): void
    {
        $sahabi = IslamicRole::factory()->sahabi()->create();
        $muslim = Muslim::factory()->create(['is_published' => false]);
        $muslim->roles()->attach($sahabi->id, ['is_featured' => false, 'sort_order' => 0]);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.sahaba.show', $muslim))
            ->assertOk();

        foreach (['toggle-published', 'toggle-featured', 'toggle-tree'] as $testId) {
            $response->assertSee('data-testid="'.$testId.'"', false);
        }

        // An unpublished, unfeatured companion is live nowhere, and the report
        // has to say so rather than leave the editor to infer it.
        $response->assertSee('data-testid="visibility-report"', false);
        $response->assertSee('0/4');
        $response->assertSee(__('sahaba.visibility.needs_published'));
    }
}
