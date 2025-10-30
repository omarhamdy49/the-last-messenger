<?php

declare(strict_types=1);

namespace App\Domain\Genealogy\Models;

use App\Domain\Genealogy\Enums\RelationshipType;
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
    ];

    protected $casts = [
        'relationship_type' => RelationshipType::class,
    ];

    public $timestamps = true;

    /**
     * Get the primary person
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_id');
    }

    /**
     * Get the related person
     */
    public function relatedPerson(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'related_person_id');
    }

    /**
     * Get the inverse relationship type
     */
    public function getInverseRelationshipType(): ?RelationshipType
    {
        return match ($this->relationship_type) {
            RelationshipType::FATHER => RelationshipType::SON,
            RelationshipType::MOTHER => RelationshipType::DAUGHTER,
            RelationshipType::SON => RelationshipType::FATHER,
            RelationshipType::DAUGHTER => RelationshipType::MOTHER,
            RelationshipType::BROTHER => RelationshipType::BROTHER,
            RelationshipType::SISTER => RelationshipType::SISTER,
            RelationshipType::HUSBAND => RelationshipType::WIFE,
            RelationshipType::WIFE => RelationshipType::HUSBAND,
            RelationshipType::GRANDFATHER => RelationshipType::GRANDSON,
            RelationshipType::GRANDMOTHER => RelationshipType::GRANDDAUGHTER,
            RelationshipType::GRANDSON => RelationshipType::GRANDFATHER,
            RelationshipType::GRANDDAUGHTER => RelationshipType::GRANDMOTHER,
            RelationshipType::UNCLE => RelationshipType::NEPHEW,
            RelationshipType::AUNT => RelationshipType::NIECE,
            RelationshipType::NEPHEW => RelationshipType::UNCLE,
            RelationshipType::NIECE => RelationshipType::AUNT,
            RelationshipType::COUSIN => RelationshipType::COUSIN,
            default => null,
        };
    }
}
