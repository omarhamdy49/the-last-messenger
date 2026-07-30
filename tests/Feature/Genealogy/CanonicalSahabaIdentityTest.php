<?php

declare(strict_types=1);

namespace Tests\Feature\Genealogy;

use App\Domain\Genealogy\Models\Person;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CanonicalSahabaIdentityTest extends TestCase
{
    use RefreshDatabase;

    public function test_genealogy_uses_sahaba_without_a_people_table(): void
    {
        $father = Person::create([
            'slug' => 'canonical-father',
            'gender' => 'male',
            'person_type' => 'family',
            'is_sahabi' => false,
        ]);

        $child = Person::create([
            'slug' => 'canonical-child',
            'gender' => 'male',
            'person_type' => 'sahabi',
            'is_sahabi' => true,
            'father_id' => $father->id,
        ]);

        $this->assertFalse(Schema::hasTable('people'));
        $this->assertFalse(Schema::hasTable('person_translations'));
        $this->assertSame('sahaba', $child->getTable());
        $this->assertTrue($child->father->is($father));
        $this->assertTrue($father->childrenAsFather->contains($child));
    }
}
