<?php

declare(strict_types=1);

namespace Tests\Feature\People;

use App\Domain\People\Models\IslamicRole;
use App\Domain\People\Models\Muslim;
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
}
