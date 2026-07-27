<?php

declare(strict_types=1);

namespace App\Domain\Genealogy\Models;

use App\Domain\Genealogy\Enums\RelationshipType;
use App\Domain\Sahaba\Models\Sahabi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Relationship Model
 * Represents family relationships between people
 */
class Relationship extends Model
{
    protected $fillable = [
        'person_id',
        'related_person_id',
        'relationship_type',
        'notes',
        'display_order',
        'hide_from_tree',
    ];

    protected $casts = [
        'relationship_type' => RelationshipType::class,
        'display_order' => 'integer',
        'hide_from_tree' => 'boolean',
    ];

    public $timestamps = true;

    /**
     * Get the primary person
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Sahabi::class, 'person_id');
    }

    /**
     * Get the related person
     */
    public function relatedPerson(): BelongsTo
    {
        return $this->belongsTo(Sahabi::class, 'related_person_id');
    }

    /**
     * Get the inverse relationship type
     */
    public function getInverseRelationshipType(): ?RelationshipType
    {
        return match ($this->relationship_type) {
            RelationshipType::SPOUSE => RelationshipType::SPOUSE,
            RelationshipType::SIBLING => RelationshipType::SIBLING,
            RelationshipType::COUSIN => RelationshipType::COUSIN,
            RelationshipType::UNCLE,
            RelationshipType::AUNT => null,
            default => null,
        };
    }
}
