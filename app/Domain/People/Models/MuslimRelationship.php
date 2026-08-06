<?php

declare(strict_types=1);

namespace App\Domain\People\Models;

use App\Domain\People\Enums\RelationshipType;
use Database\Factories\MuslimRelationshipFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A non-descent edge between two people, stored ONCE.
 *
 * @property int $id
 * @property int $muslim_id
 * @property int $related_muslim_id
 * @property RelationshipType $relationship_type
 * @property string|null $notes
 * @property int $sort_order
 * @property bool $hide_from_tree
 */
class MuslimRelationship extends Model
{
    use HasFactory;

    protected $table = 'muslim_relationships';

    protected $fillable = [
        'muslim_id',
        'related_muslim_id',
        'relationship_type',
        'notes',
        'sort_order',
        'hide_from_tree',
    ];

    protected function casts(): array
    {
        return [
            'relationship_type' => RelationshipType::class,
            'hide_from_tree' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function muslim(): BelongsTo
    {
        return $this->belongsTo(Muslim::class, 'muslim_id');
    }

    public function relatedMuslim(): BelongsTo
    {
        return $this->belongsTo(Muslim::class, 'related_muslim_id');
    }

    /**
     * The other party, seen from $muslimId. Callers read a relationship with
     * `muslim_id = ? OR related_muslim_id = ?`, so either column may be the
     * one they already know.
     */
    public function counterpartIdFor(int $muslimId): int
    {
        return $this->muslim_id === $muslimId
            ? $this->related_muslim_id
            : $this->muslim_id;
    }

    /**
     * How this row reads from $muslimId's side. A row stored as
     * "A is B's uncle" renders as "nephew/niece" when read from B.
     */
    public function labelFor(int $muslimId, string $locale = 'ar'): string
    {
        return $this->muslim_id === $muslimId
            ? $this->relationship_type->label($locale)
            : $this->relationship_type->reverseLabel($locale);
    }

    /** Domain models live outside app/Models, so the factory is named explicitly. */
    protected static function newFactory(): MuslimRelationshipFactory
    {
        return MuslimRelationshipFactory::new();
    }
}
