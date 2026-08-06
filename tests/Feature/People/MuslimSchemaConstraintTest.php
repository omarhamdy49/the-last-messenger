<?php

declare(strict_types=1);

namespace Tests\Feature\People;

use App\Domain\People\Enums\RelationshipType;
use App\Domain\People\Models\IslamicRole;
use App\Domain\People\Models\Muslim;
use App\Domain\People\Models\MuslimRelationship;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The database-level guarantees, asserted rather than assumed (constitution VI).
 *
 * Every constraint here is MySQL-specific — CHECK constraints, RESTRICT foreign
 * keys — and the suite runs on MySQL, so there is no excuse for taking them on
 * faith.
 */
class MuslimSchemaConstraintTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_person_with_only_an_arabic_name_is_allowed(): void
    {
        $muslim = Muslim::factory()->arabicOnly()->create();

        $this->assertNotNull($muslim->name_ar);
        $this->assertNull($muslim->name_en);
    }

    public function test_a_person_with_only_an_english_name_is_allowed(): void
    {
        $muslim = Muslim::factory()->create(['name_ar' => null]);

        $this->assertNull($muslim->name_ar);
        $this->assertNotNull($muslim->name_en);
    }

    public function test_a_person_with_no_name_at_all_is_rejected(): void
    {
        $this->expectException(QueryException::class);

        // Straight to the query builder: the model layer would not exercise the
        // CHECK, and the CHECK is what is under test.
        DB::table('muslims')->insert([
            'slug' => 'nameless',
            'name_ar' => null,
            'name_en' => null,
            'is_published' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_deleting_a_person_who_is_a_parent_is_restricted(): void
    {
        $father = Muslim::factory()->create();
        Muslim::factory()->create(['father_id' => $father->id]);

        $this->expectException(QueryException::class);

        // forceDelete, not delete(): soft deletion is an UPDATE and would never
        // reach the foreign key. The RESTRICT exists to stop a real removal
        // silently orphaning a subtree.
        $father->forceDelete();
    }

    public function test_a_person_cannot_be_their_own_sibling(): void
    {
        $muslim = Muslim::factory()->create();

        $this->expectException(QueryException::class);

        DB::table('muslim_relationships')->insert([
            'muslim_id' => $muslim->id,
            'related_muslim_id' => $muslim->id,
            'relationship_type' => RelationshipType::Sibling->value,
            'sort_order' => 0,
            'hide_from_tree' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_the_same_relationship_cannot_be_stored_twice(): void
    {
        $a = Muslim::factory()->create();
        $b = Muslim::factory()->create();

        MuslimRelationship::create([
            'muslim_id' => $a->id,
            'related_muslim_id' => $b->id,
            'relationship_type' => RelationshipType::Spouse,
        ]);

        $this->expectException(QueryException::class);

        MuslimRelationship::create([
            'muslim_id' => $a->id,
            'related_muslim_id' => $b->id,
            'relationship_type' => RelationshipType::Spouse,
        ]);
    }

    public function test_pre_hijra_birth_years_are_stored_as_negative_numbers(): void
    {
        // The column is SIGNED for exactly this: a companion born before the
        // Hijra is the common case. An unsigned column would silently clamp.
        $muslim = Muslim::factory()->create(['birth_year_hijri' => -53]);

        $this->assertSame(-53, $muslim->fresh()->birth_year_hijri);
    }

    public function test_a_person_holds_a_given_role_only_once(): void
    {
        $muslim = Muslim::factory()->create();
        $role = IslamicRole::factory()->sahabi()->create();

        $muslim->roles()->attach($role->id);

        $this->expectException(QueryException::class);

        $muslim->roles()->attach($role->id);
    }
}
