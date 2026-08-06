<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\People\Enums\RelationshipType;
use App\Domain\People\Models\Muslim;
use App\Domain\People\Models\MuslimRelationship;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MuslimRelationship>
 */
final class MuslimRelationshipFactory extends Factory
{
    protected $model = MuslimRelationship::class;

    public function definition(): array
    {
        return [
            'muslim_id' => Muslim::factory(),
            'related_muslim_id' => Muslim::factory(),
            'relationship_type' => RelationshipType::Sibling->value,
            'sort_order' => 0,
            'hide_from_tree' => false,
        ];
    }

    public function ofType(RelationshipType $type): static
    {
        return $this->state(fn (): array => ['relationship_type' => $type->value]);
    }
}
