<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Shared;

use App\Domain\Shared\Exceptions\BusinessRuleViolationException;
use App\Domain\Shared\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The hierarchy has to stay a tree. A cycle makes `depth` uncomputable and every
 * ancestor walk non-terminating, so it is rejected on write rather than tolerated
 * and worked around on read.
 */
class LocationHierarchyTest extends TestCase
{
    use RefreshDatabase;

    public function test_depth_is_zero_for_a_root(): void
    {
        $root = Location::factory()->create(['parent_id' => null]);

        $this->assertSame(0, $root->depth);
    }

    public function test_depth_is_derived_from_the_parent_chain(): void
    {
        $peninsula = Location::factory()->create(['parent_id' => null]);
        $region = Location::factory()->create(['parent_id' => $peninsula->id]);
        $city = Location::factory()->create(['parent_id' => $region->id]);
        $mosque = Location::factory()->create(['parent_id' => $city->id]);

        $this->assertSame(1, $region->depth);
        $this->assertSame(2, $city->depth);
        $this->assertSame(3, $mosque->depth);
    }

    public function test_depth_is_recomputed_when_the_parent_changes(): void
    {
        $root = Location::factory()->create(['parent_id' => null]);
        $child = Location::factory()->create(['parent_id' => $root->id]);
        $grandchild = Location::factory()->create(['parent_id' => $child->id]);

        $this->assertSame(2, $grandchild->depth);

        $grandchild->parent_id = $root->id;
        $grandchild->save();

        $this->assertSame(1, $grandchild->fresh()->depth);
    }

    public function test_a_location_cannot_be_its_own_parent(): void
    {
        $location = Location::factory()->create();

        $this->expectException(BusinessRuleViolationException::class);

        $location->parent_id = $location->id;
        $location->save();
    }

    public function test_a_two_node_cycle_is_rejected(): void
    {
        $parent = Location::factory()->create(['parent_id' => null]);
        $child = Location::factory()->create(['parent_id' => $parent->id]);

        // Making the parent a child of its own child closes the loop.
        $this->expectException(BusinessRuleViolationException::class);

        $parent->parent_id = $child->id;
        $parent->save();
    }

    public function test_a_three_node_cycle_is_rejected(): void
    {
        $a = Location::factory()->create(['parent_id' => null]);
        $b = Location::factory()->create(['parent_id' => $a->id]);
        $c = Location::factory()->create(['parent_id' => $b->id]);

        $this->expectException(BusinessRuleViolationException::class);

        $a->parent_id = $c->id;
        $a->save();
    }

    public function test_a_legitimate_reparent_is_allowed(): void
    {
        $a = Location::factory()->create(['parent_id' => null]);
        $b = Location::factory()->create(['parent_id' => null]);
        $child = Location::factory()->create(['parent_id' => $a->id]);

        $child->parent_id = $b->id;
        $child->save();

        $this->assertSame($b->id, $child->fresh()->parent_id);
        $this->assertSame(1, $child->fresh()->depth);
    }
}
