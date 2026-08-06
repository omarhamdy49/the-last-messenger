<?php

declare(strict_types=1);

namespace Tests\Feature\People;

use App\Domain\People\Exceptions\GenealogyCycleException;
use App\Domain\People\Models\Muslim;
use App\Domain\People\Services\GenealogyTreeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cycle prevention is the ONE rule in this schema with no database enforcement.
 * No CHECK constraint can express "this person is not already my ancestor" —
 * it requires a traversal. So it gets more tests than anything else, and the
 * deep case matters most: A→B→C→A satisfies every constraint in the database.
 */
class GenealogyCycleTest extends TestCase
{
    use RefreshDatabase;

    private GenealogyTreeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(GenealogyTreeService::class);
    }

    public function test_a_person_cannot_be_their_own_father(): void
    {
        $muslim = Muslim::factory()->create();

        $this->expectException(GenealogyCycleException::class);

        $this->service->assignParent($muslim, 'father_id', $muslim->id);
    }

    public function test_a_direct_cycle_is_rejected(): void
    {
        // A is B's father; making B A's father would close the loop.
        $a = Muslim::factory()->create();
        $b = Muslim::factory()->create(['father_id' => $a->id]);

        $this->expectException(GenealogyCycleException::class);

        $this->service->assignParent($a, 'father_id', $b->id);
    }

    public function test_a_deep_cycle_is_rejected(): void
    {
        // A → B → C. Making C the father of A closes a three-link loop that
        // every database constraint accepts.
        $a = Muslim::factory()->create();
        $b = Muslim::factory()->create(['father_id' => $a->id]);
        $c = Muslim::factory()->create(['father_id' => $b->id]);

        $this->expectException(GenealogyCycleException::class);

        $this->service->assignParent($a, 'father_id', $c->id);
    }

    public function test_a_cycle_through_the_mother_link_is_also_rejected(): void
    {
        // The ancestor walk follows BOTH father_id and mother_id; a guard that
        // only checked the paternal line would miss this entirely.
        $a = Muslim::factory()->create();
        $b = Muslim::factory()->create(['mother_id' => $a->id]);
        $c = Muslim::factory()->create(['father_id' => $b->id]);

        $this->expectException(GenealogyCycleException::class);

        $this->service->assignParent($a, 'mother_id', $c->id);
    }

    public function test_a_legitimate_parent_assignment_succeeds(): void
    {
        $child = Muslim::factory()->create();
        $father = Muslim::factory()->create();

        $updated = $this->service->assignParent($child, 'father_id', $father->id);

        $this->assertSame($father->id, $updated->father_id);
    }

    public function test_clearing_a_parent_is_allowed(): void
    {
        $father = Muslim::factory()->create();
        $child = Muslim::factory()->create(['father_id' => $father->id]);

        $updated = $this->service->assignParent($child, 'father_id', null);

        $this->assertNull($updated->father_id);
    }

    public function test_a_shared_ancestor_is_not_mistaken_for_a_cycle(): void
    {
        // Two siblings share a father. Making one the parent of an unrelated
        // person is fine — a naive "have I seen this id" guard would wrongly
        // reject it.
        $grandfather = Muslim::factory()->create();
        $parent = Muslim::factory()->create(['father_id' => $grandfather->id]);
        $unrelated = Muslim::factory()->create();

        $updated = $this->service->assignParent($unrelated, 'father_id', $parent->id);

        $this->assertSame($parent->id, $updated->father_id);
    }
}
