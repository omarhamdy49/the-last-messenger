<?php

declare(strict_types=1);

namespace Tests\Feature\People;

use App\Domain\People\Enums\RelationshipType;
use App\Domain\People\Models\Muslim;
use App\Domain\People\Models\MuslimRelationship;
use App\Domain\People\Services\GenealogyTreeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenealogyTreeTest extends TestCase
{
    use RefreshDatabase;

    private GenealogyTreeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(GenealogyTreeService::class);
    }

    public function test_ancestors_are_walked_upward_through_both_parents(): void
    {
        $grandfather = Muslim::factory()->create();
        $grandmother = Muslim::factory()->create();
        $father = Muslim::factory()->create([
            'father_id' => $grandfather->id,
            'mother_id' => $grandmother->id,
        ]);
        $child = Muslim::factory()->create(['father_id' => $father->id]);

        $result = $this->service->ancestors($child);
        $ids = array_column($result['nodes'], 'id');

        $this->assertEqualsCanonicalizing(
            [$father->id, $grandfather->id, $grandmother->id],
            array_map('intval', $ids)
        );
        $this->assertSame(2, $result['depth_reached']);
        $this->assertFalse($result['depth_capped']);
    }

    public function test_descendants_are_walked_downward(): void
    {
        $root = Muslim::factory()->create();
        $child = Muslim::factory()->create(['father_id' => $root->id]);
        $grandchild = Muslim::factory()->create(['mother_id' => $child->id]);

        $result = $this->service->descendants($root);
        $ids = array_map('intval', array_column($result['nodes'], 'id'));

        $this->assertEqualsCanonicalizing([$child->id, $grandchild->id], $ids);
        $this->assertSame(2, $result['depth_reached']);
    }

    public function test_siblings_are_ordered_by_tree_display_order(): void
    {
        $father = Muslim::factory()->create();
        $third = Muslim::factory()->create(['father_id' => $father->id, 'tree_display_order' => 3]);
        $first = Muslim::factory()->create(['father_id' => $father->id, 'tree_display_order' => 1]);
        $second = Muslim::factory()->create(['father_id' => $father->id, 'tree_display_order' => 2]);

        $result = $this->service->descendants($father);
        $ids = array_map('intval', array_column($result['nodes'], 'id'));

        $this->assertSame([$first->id, $second->id, $third->id], $ids);
    }

    public function test_the_depth_cap_is_honoured_and_reported(): void
    {
        // Six generations, asked for three. A capped tree that reported itself
        // as complete would be indistinguishable from a real one.
        $current = Muslim::factory()->create();
        for ($i = 0; $i < 6; $i++) {
            $current = Muslim::factory()->create(['father_id' => $current->id]);
        }

        $result = $this->service->ancestors($current, 3);

        $this->assertSame(3, $result['depth_reached']);
        $this->assertTrue($result['depth_capped']);
        $this->assertCount(3, $result['nodes']);
    }

    public function test_relations_are_readable_from_both_sides_of_one_stored_row(): void
    {
        $a = Muslim::factory()->create();
        $b = Muslim::factory()->create();

        MuslimRelationship::create([
            'muslim_id' => $a->id,
            'related_muslim_id' => $b->id,
            'relationship_type' => RelationshipType::Spouse,
        ]);

        $this->assertCount(1, $this->service->relations($a));
        $this->assertCount(1, $this->service->relations($b));
        $this->assertSame($b->slug, $this->service->relations($a)[0]['slug']);
        $this->assertSame($a->slug, $this->service->relations($b)[0]['slug']);
    }

    public function test_an_uncle_row_reads_as_nephew_from_the_other_side(): void
    {
        $uncle = Muslim::factory()->create();
        $nephew = Muslim::factory()->create();

        MuslimRelationship::create([
            'muslim_id' => $uncle->id,
            'related_muslim_id' => $nephew->id,
            'relationship_type' => RelationshipType::Uncle,
        ]);

        $this->assertSame('عم/خال', $this->service->relations($uncle)[0]['label']);
        $this->assertSame('ابن/ابنة الأخ أو الأخت', $this->service->relations($nephew)[0]['label']);
    }

    public function test_unpublished_and_hidden_relations_are_suppressed_in_the_render(): void
    {
        $root = Muslim::factory()->create();
        $unpublished = Muslim::factory()->unpublished()->create();
        $hidden = Muslim::factory()->create();

        MuslimRelationship::create([
            'muslim_id' => $root->id,
            'related_muslim_id' => $unpublished->id,
            'relationship_type' => RelationshipType::Sibling,
        ]);
        MuslimRelationship::create([
            'muslim_id' => $root->id,
            'related_muslim_id' => $hidden->id,
            'relationship_type' => RelationshipType::Cousin,
            'hide_from_tree' => true,
        ]);

        $this->assertCount(0, $this->service->relations($root));
        // ...but both are still visible to an editor.
        $this->assertCount(2, $this->service->relations($root, 'ar', publishedOnly: false));
    }
}
