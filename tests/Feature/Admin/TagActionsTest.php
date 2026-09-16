<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Blog\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TagActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_delete_form_redirects_with_feedback_and_deletes_only_selected_tags(): void
    {
        $selected = Tag::create(['slug' => 'selected', 'type' => 'general']);
        $other = Tag::create(['slug' => 'other', 'type' => 'general']);
        $this->actingAs($this->createAdminUser(role: 'super_admin'))
            ->from(route('admin.tags.unused'))
            ->post(route('admin.tags.bulk-delete'), ['ids' => [$selected->id]])
            ->assertRedirect(route('admin.tags.unused'))->assertSessionHas('success');

        $this->assertDatabaseMissing('tags', ['id' => $selected->id]);
        $this->assertDatabaseHas('tags', ['id' => $other->id]);
    }

    public function test_bulk_delete_still_returns_json_for_ajax_callers(): void
    {
        $tag = Tag::create(['slug' => 'ajax', 'type' => 'general']);
        $this->actingAs($this->createAdminUser(role: 'super_admin'))
            ->postJson(route('admin.tags.bulk-delete'), ['ids' => [$tag->id]])
            ->assertOk()->assertJson(['success' => true, 'count' => 1]);
    }

    public function test_tag_forms_redirect_with_feedback_after_delete(): void
    {
        $tag = Tag::create(['slug' => 'form-tag', 'type' => 'article']);
        $this->actingAs($this->createAdminUser(role: 'super_admin'))
            ->delete(route('admin.tags.destroy', $tag))
            ->assertRedirect(route('admin.tags.index'))->assertSessionHas('success');
        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }

    public function test_merge_modal_inventory_contains_display_names(): void
    {
        $tag = Tag::create(['slug' => 'readable-tag', 'type' => 'article']);
        $this->actingAs($this->createAdminUser(role: 'super_admin'))
            ->getJson(route('admin.tags.index'))
            ->assertOk()->assertJsonPath('tags.0.id', $tag->id)
            ->assertJsonPath('tags.0.name', 'readable-tag');
    }

    public function test_merge_modal_payload_preserves_all_content_links(): void
    {
        $first = Tag::create(['slug' => 'source-one', 'type' => 'article']);
        $second = Tag::create(['slug' => 'source-two', 'type' => 'article']);
        $target = Tag::create(['slug' => 'target', 'type' => 'article']);
        foreach ([[$first->id, 'article', 1], [$second->id, 'media', 2], [$target->id, 'article', 1]] as [$tagId, $type, $id]) {
            DB::table('taggables')->insert([
                'tag_id' => $tagId, 'taggable_type' => $type, 'taggable_id' => $id,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $this->actingAs($this->createAdminUser(role: 'super_admin'))
            ->postJson(route('admin.tags.merge'), [
                'source_tag_ids' => [$first->id, $second->id],
                'merge_mode' => 'existing', 'target_tag_id' => $target->id,
            ])->assertOk()->assertJson(['success' => true, 'count' => 2]);

        $this->assertDatabaseCount('tags', 1);
        $this->assertDatabaseCount('taggables', 2);
        $this->assertDatabaseHas('taggables', ['tag_id' => $target->id, 'taggable_type' => 'media', 'taggable_id' => 2]);
    }

    public function test_merge_modal_can_create_a_new_destination(): void
    {
        $source = Tag::create(['slug' => 'old-tag', 'type' => 'article']);
        $this->actingAs($this->createAdminUser(role: 'super_admin'))
            ->postJson(route('admin.tags.merge'), [
                'source_tag_ids' => [$source->id], 'merge_mode' => 'new',
                'new_tag_name' => 'وسم موحد',
            ])->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseCount('tags', 1);
        $this->assertDatabaseMissing('tags', ['id' => $source->id]);
        $this->assertSame('وسم موحد', Tag::firstOrFail()->getTranslation('name'));
    }

    public function test_merge_cannot_delete_its_destination(): void
    {
        $tag = Tag::create(['slug' => 'same-tag', 'type' => 'article']);
        $this->actingAs($this->createAdminUser(role: 'super_admin'))
            ->postJson(route('admin.tags.merge'), [
                'source_tag_ids' => [$tag->id], 'merge_mode' => 'existing', 'target_tag_id' => $tag->id,
            ])->assertUnprocessable()->assertJsonValidationErrors('target_tag_id');
        $this->assertDatabaseHas('tags', ['id' => $tag->id]);
    }
}
